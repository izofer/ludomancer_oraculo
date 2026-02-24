<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Plan;

class TransactionController extends Controller
{
    /**
     * Devuelve el historial de pagos del usuario logueado en la web.
     */
    public function getUserHistory(Request $request)
    {
        $user = $request->user();
        
        $transactions = $user->transactions()->paginate(10);

        // Limpiamos la respuesta para que el frontend reciba exactamente lo que necesita
        $transactions->getCollection()->transform(function ($transaction) {
            return [
                'id' => $transaction->id,
                'fecha' => $transaction->created_at->format('Y-m-d H:i'),
                'plan' => $transaction->plan_name,
                'estado' => $transaction->status,
                'metodo' => ucfirst($transaction->payment_method),
                'pago' => '$' . number_format($transaction->amount_usd, 2) . ' USD'
            ];
        });

        return response()->json([
            'mensaje' => 'Historial de tesorería recuperado.',
            'data' => $transactions
        ], 200);
    }

    /**
     * Función interna/Webhook para procesar un pago exitoso y DAR EL PODER.
     * (Esta función será llamada por Stripe/PayPal/Wompi cuando el pago se confirme)
     */
    public function processSuccessfulPayment($user, $amount, $currency, $amountUsd, $gateway_id, $method, $planName, $daysToAdd, $planId)
    {
        // 1. Registramos el pago en el Libro Mayor
        Transaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'amount_usd' => $amountUsd,
            'payment_method' => $method,
            'gateway_transaction_id' => $gateway_id,
            'status' => 'completed',
            'plan_name' => $planName,
            'days_added' => $daysToAdd
        ]);

        // 2. LA MAGIA: Calculamos la nueva fecha de expiración
        $fechaActualExpiracion = $user->licencia_expira_el;
        $hoy = now();

        if (empty($fechaActualExpiracion) || $fechaActualExpiracion < $hoy) {
            // Si no tenía licencia o estaba vencida, se cuenta desde HOY
            $nuevaExpiracion = $hoy->addDays($daysToAdd);
        } else {
            // Si aún tenía días activos, se le SUMAN a los que ya tenía
            $nuevaExpiracion = Carbon::parse($fechaActualExpiracion)->addDays($daysToAdd);
        }

        // 3. Actualizamos al usuario
        $user->update([
            'status' => 'activo',
            'current_plan_id' => $planId,
            'licencia_adquirida_el' => $hoy,
            'licencia_expira_el' => $nuevaExpiracion
        ]);

        return true;
    }

    public function createPaymentLink(Request $request)
    {
        // 1. Validamos que el frontend nos envíe un ID de plan válido
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        $user = $request->user();
        $plan = Plan::where('id', $request->plan_id)->where('is_active', true)->firstOrFail();

        $mpAccessToken = env('MERCADOPAGO_ACCESS_TOKEN');

        // 2. EL CABALLO DE TROYA: Guardamos ambos IDs en un formato JSON (Texto)
        $referenciaOculta = json_encode([
            'user_id' => $user->id,
            'plan_id' => $plan->id
        ]);

        $response = Http::withToken($mpAccessToken)->post('https://api.mercadopago.com/checkout/preferences', [
            'items' => [
                [
                    'title' => $plan->name,
                    'quantity' => 1,
                    'unit_price' => (float) $plan->price,
                    'currency_id' => $plan->currency
                ]
            ],
            // Inyectamos el JSON oculto
            'external_reference' => $referenciaOculta, 
            
            'back_urls' => [
                'success' => 'https://ludomancer.izofer.com/panel?pago=exito',
                'failure' => 'https://ludomancer.izofer.com/panel?pago=fallo',
            ],
            'auto_return' => 'approved',
        ]);

        if ($response->successful()) {
            return response()->json([
                'init_point' => $response->json()['init_point'] 
            ]);
        }

        return response()->json(['error' => 'Falla al contactar al banco.'], 500);
    }

    public function mercadopagoWebhook(Request $request)
    {
        Log::info('--- MENSAJERO DE MERCADO PAGO DETECTADO ---');
        Log::info($request->all());

        // MercadoPago envía una notificación cuando hay una actualización
        if ($request->type === 'payment') {
            $paymentId = $request->data['id'];
            $mpAccessToken = env('MERCADOPAGO_ACCESS_TOKEN');

            // Consultamos a MP para ver si el pago es real y está aprobado
            $response = Http::withToken($mpAccessToken)->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $paymentData = $response->json();

                if ($paymentData['status'] === 'approved') {
                    // 1. Extraemos y decodificamos el JSON que enviamos
                    $referenciaDecodificada = json_decode($paymentData['external_reference'], true);
                    
                    $userId = $referenciaDecodificada['user_id'] ?? null;
                    $planId = $referenciaDecodificada['plan_id'] ?? null;

                    $user = User::find($userId);
                    $plan = Plan::find($planId); // Buscamos el plan real en la BD

                    if ($user && $plan) {
                        $exists = Transaction::where('gateway_transaction_id', $paymentId)->exists();
                        
                        if (!$exists) {
                            // 2. ¡Ejecutamos con datos 100% dinámicos desde la BD!
                            $this->processSuccessfulPayment(
                                $user, 
                                $paymentData['transaction_amount'], 
                                $paymentData['currency_id'], 
                                $plan->price,
                                $paymentId, 
                                'mercadopago', 
                                $plan->name,
                                $plan->days_of_power,
                                $plan->id
                            );
                            
                            Log::info("Pago exitoso. Usuario: {$user->email} compró el plan: {$plan->name}");
                        }
                    }
                }
            }
        }

        // Siempre hay que responder 200 OK rápido para que MP no reintente
        return response()->json(['status' => 'ok'], 200);
    }
}