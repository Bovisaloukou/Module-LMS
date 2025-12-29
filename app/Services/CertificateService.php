<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    public function generate(User $student, Course $course, Enrollment $enrollment): Certificate
    {
        $existing = Certificate::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $certificateNumber = $this->generateCertificateNumber();

        $certificate = Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => $certificateNumber,
            'issued_at' => now(),
        ]);

        $pdfPath = $this->generatePdf($certificate);

        $certificate->update(['pdf_path' => $pdfPath]);

        return $certificate->fresh();
    }

    public function regeneratePdf(Certificate $certificate): Certificate
    {
        $pdfPath = $this->generatePdf($certificate);

        $certificate->update(['pdf_path' => $pdfPath]);

        return $certificate->fresh();
    }

    public function downloadPdf(Certificate $certificate): \Illuminate\Http\Response
    {
        $certificate->load(['student', 'course']);

        $pdf = Pdf::loadView('certificates.template', [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'course' => $certificate->course,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'certificate-'.$certificate->certificate_number.'.pdf';

        return $pdf->download($filename);
    }

    private function generatePdf(Certificate $certificate): string
    {
        $certificate->load(['student', 'course']);

        $pdf = Pdf::loadView('certificates.template', [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'course' => $certificate->course,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $directory = 'certificates/'.$certificate->student_id;
        $filename = $certificate->certificate_number.'.pdf';
        $path = $directory.'/'.$filename;

        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    private function generateCertificateNumber(): string
    {
        do {
            $number = 'CERT-'.strtoupper(Str::random(8)).'-'.date('Y');
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }
}
