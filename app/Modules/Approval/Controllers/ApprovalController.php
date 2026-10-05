<?php

namespace App\Modules\Approval\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Approval\Actions\DecideApprovalAction;
use App\Modules\Approval\DTOs\DecideApprovalDTO;
use App\Modules\Approval\Exceptions\ApprovalForbiddenException;
use App\Modules\Approval\Queries\GetPurchaseOrderApprovalQueueQuery;
use App\Modules\Roles\Queries\GetOneRoleFromUserQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ApprovalController extends Controller
{
    /**
     * Hub approval. Menu `approval_documents` sudah ada di DB dan menunjuk ke
     * `/approvals`, jadi halaman ini perlu ada supaya induknya tidak 404 walau
     * baru Purchase Order yang punya modul.
     */
    public function index(): Response
    {
        return Inertia::render('Approval/Index');
    }

    public function purchaseOrders(
        Request $request,
        GetPurchaseOrderApprovalQueueQuery $queue,
        GetOneRoleFromUserQuery $roles,
    ): Response {
        $role = $roles->execute((int) $request->user()->id);

        $filters = [
            'search' => $request->string('search')->toString(),
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];

        return Inertia::render('Approval/PurchaseOrder/Index', [
            'queue' => $queue->execute((string) $role->name, $filters),
            'filters' => [
                'search' => $filters['search'],
                'date_from' => (string) $request->string('date_from')->toString(),
                'date_to' => (string) $request->string('date_to')->toString(),
            ],
        ]);
    }

    /**
     * Menyimpan keputusan approve/tolak/revisi untuk langkah approval yang
     * sedang berjalan.
     *
     * Dokumen dan langkah yang boleh diputuskan ditentukan ulang di server dari
     * `transaction_approvals`; nilai yang dikirim klien tidak pernah dipakai
     * untuk menentukan otorisasi.
     */
    public function decidePurchaseOrder(
        Request $request,
        int $transaction,
        DecideApprovalAction $action,
        GetOneRoleFromUserQuery $roles,
    ): JsonResponse {
        $status = (string) $request->input('status');

        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(DecideApprovalDTO::STATUSES)],
                'description' => [
                    Rule::requiredIf(fn () => $status === DecideApprovalDTO::STATUS_NEED_REVISION),
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);
        } catch (ValidationException $exception) {
            // `bootstrap/app.php` mendaftarkan
            // `shouldRenderJsonWhen(fn ($r) => $r->is('api/*'))`, jadi handler
            // global membalas redirect 302 untuk route non-api walau klien
            // meminta JSON. Endpoint ini hanya dipakai axios, dan XHR mengikuti
            // redirect secara transparan sehingga pesan validasinya hilang.
            // Karena itu error validasi dibalas eksplisit sebagai 422 + JSON.
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        $user = $request->user();
        $role = $roles->execute((int) $user->id);

        try {
            $action->execute(new DecideApprovalDTO(
                transaction_id: $transaction,
                role_id: (int) $role->id,
                proceed_by: (int) $user->id,
                status: $validated['status'],
                description: $validated['description'] ?? null,
            ));
        } catch (ApprovalForbiddenException $exception) {
            // Role pemohon bukan pemilik langkah yang sedang berjalan. Ini
            // masalah otorisasi, bukan input salah, jadi 403 bukan 422.
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => $validated['status'] === DecideApprovalDTO::STATUS_APPROVED
                ? 'Purchase Order disetujui.'
                : 'Purchase Order ditandai perlu revisi.',
        ]);
    }
}
