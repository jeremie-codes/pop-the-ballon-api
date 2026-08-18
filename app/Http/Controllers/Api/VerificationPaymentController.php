<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\FlexpaieService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VerificationPaymentController extends Controller
{

    public function initiate(
        Request $request,
        FlexpaieService $flexpay
    ) {

        try {
            $data = $request->validate([
                'identity' => 'required',Rule::in(['national_id','passport','driver_license']),

                'document' => 'required|image',
                'selfie' => 'required|image',

                'currency' => 'required|in:USD,CDF',
                'method' => 'required|in:mobile,card',

                'phone' => 'required_if:method,mobile'
            ]);

            $user = $request->user();

            $documentPath = $request->file('document')->store('verification/documents');
            $selfiePath = $request->file('selfie')->store('verification/selfies');

            $priceUSD = 5; // prix certification
            $priceCDF = 100;
            $reference = 'VER-' . uniqid();

            if ($data['method'] == "mobile") {
                $response = $flexpay->mobilePayment(
                    $reference,
                    $data['currency'] == "USD" ? $priceUSD : $priceCDF,
                    $data['phone'],
                    $data['currency'],
                    route('verification.callback', ['reference' => $reference])
                );
            } else {
                $response = $flexpay->cardPayment(
                    $reference,
                    $data['currency'] == "USD" ? $priceUSD : $priceCDF,
                    $data['currency'],
                    route('verification.callback', ['reference' => $reference]),
                    route('verification.success', ['reference' => $reference]),
                    route('verification.cancel', ['reference' => $reference]),
                    route('verification.decline', ['reference' => $reference])
                );
            }

            VerificationRequest::create([
                'user_id' => $user->id,
                'identity_type' => $data['identity'],
                'document_path' => $documentPath,
                'selfie_path' => $selfiePath,
                'amount' => $data['currency'] == "USD" ? $priceUSD : $priceCDF,
                'currency' => $data['currency'],
                'reference' => $reference,
                'order_number' => $response['orderNumber'] ?? null
            ]);

            return response()->json([
                'code' => $response['code'],
                'message' => $response['message'],
                'orderNumber' => $response['orderNumber'] ?? null,
                'url' => $response['url'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::error('Verification payment error', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

    public function status(
        Request $request,
        FlexpaieService $flexpay
    ) {
        $data = $request->validate([
            'order_number' => 'required'
        ]);

        $verification = VerificationRequest::where('order_number', $data['order_number'])->first();

        if (!$verification) {
            return response()->json([
                'code' => 1,
                'message' => 'Transaction introuvable'
            ], 404);
        }

        $response = $flexpay->getPaymentStatus($verification->order_number);
        $status = $response['transaction']['status'] ?? 2;

        if ($status == 0) {
            $this->approveVerification($verification->reference);
        }

        if ($status == 1) {
            $verification->update(['status' => 'rejected']);
        }

        if ($status == 4) {
            $verification->update(['status' => 'cancelled']);
        }

        return response()->json([
            'code' => 0,
            'transaction' => ['status' => $status]
        ]);
    }

    private function approveVerification($reference)
    {

        $request = VerificationRequest::where('reference', $reference)->first();

        if (!$request) {
            return;
        }

        DB::transaction(function () use ($request) {
            $request->update(['status' => 'approved']);
            $request->user->update(['verified' => true]);
        });
    }

    public function success($reference)
    {
        $this->approveVerification($reference);
        return response()->json([
            'status' => 'success'
        ]);
    }

    public function callback(Request $request, $reference)
    {
        $content = json_decode(
            $request->getContent(),
            true
        );

        $status = $content['status'] ?? 2;

        if ($status == 0) {
            $this->approveVerification($reference);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function cancel($reference)
    {
        $request = VerificationRequest::where('reference', $reference)->first();

        if (!$request) {
            return;
        }

        $request->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Paiement déjà clôturé',
        ]);
    }

    public function decline($reference)
    {
        $request = VerificationRequest::where('reference', $reference)->first();

        if (!$request) {
            return;
        }

        $request->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Paiement déjà clôturé',
        ]);
    }
}
