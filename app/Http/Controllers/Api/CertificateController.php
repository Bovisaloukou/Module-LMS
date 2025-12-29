<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Certificates
 *
 * APIs for viewing and downloading course completion certificates.
 */
class CertificateController extends Controller
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    /**
     * My Certificates
     *
     * List all certificates earned by the authenticated student.
     *
     * @authenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $certificates = Certificate::where('student_id', $request->user()->id)
            ->with('course')
            ->latest('issued_at')
            ->get();

        return CertificateResource::collection($certificates);
    }

    /**
     * Show Certificate
     *
     * Get certificate details by certificate number.
     *
     * @authenticated
     */
    public function show(Request $request, string $certificateNumber): CertificateResource|JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with('course')
            ->first();

        if (! $certificate) {
            return response()->json(['message' => 'Certificate not found.'], 404);
        }

        $user = $request->user();
        if ($certificate->student_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return new CertificateResource($certificate);
    }

    /**
     * Download Certificate PDF
     *
     * Download the certificate as a PDF file.
     *
     * @authenticated
     */
    public function download(Request $request, string $certificateNumber): \Illuminate\Http\Response|JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)->first();

        if (! $certificate) {
            return response()->json(['message' => 'Certificate not found.'], 404);
        }

        $user = $request->user();
        if ($certificate->student_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return $this->certificateService->downloadPdf($certificate);
    }

    /**
     * Verify Certificate
     *
     * Public endpoint to verify a certificate by its number.
     */
    public function verify(string $certificateNumber): JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['student:id,name', 'course:id,title'])
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate_number' => $certificate->certificate_number,
            'student_name' => $certificate->student->name,
            'course_title' => $certificate->course->title,
            'issued_at' => $certificate->issued_at->toISOString(),
        ]);
    }
}
