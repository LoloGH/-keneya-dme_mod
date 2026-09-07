<?php

declare(strict_types=1);

namespace Keneya\Dme\Services\Documents;

use Keneya\Dme\Models\Consultation;
use Keneya\Dme\Models\Hospitalization;
use Keneya\Dme\Models\LabOrder;
use Keneya\Dme\Models\MedicalDocument;
use Keneya\Dme\Models\Patient;
use Keneya\Dme\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génération des documents PDF (§47).
 *
 * Chaque document porte l'établissement, le patient, la date, l'auteur,
 * sa référence métier et un QR code de vérification. Le PDF produit peut
 * être renvoyé au navigateur ou archivé dans le dossier du patient — dans
 * ce dernier cas il devient un MedicalDocument soumis aux mêmes règles
 * d'accès que les documents importés (§42).
 */
class PdfGenerator
{
    public function __construct(
        private readonly DocumentStorage $storage,
        private readonly QrCodeGenerator $qrCodes,
    ) {
    }

    public function prescription(Prescription $prescription): string
    {
        $prescription->loadMissing(['patient', 'doctor', 'items']);

        return $this->render('dme::pdf.prescription', [
            'prescription' => $prescription,
            'patient' => $prescription->patient,
            'reference' => $prescription->prescription_number,
        ]);
    }

    public function consultationReport(Consultation $consultation): string
    {
        $consultation->loadMissing(['patient', 'doctor', 'service', 'vitalSigns', 'clinicalNotes', 'diagnoses']);

        return $this->render('dme::pdf.consultation', [
            'consultation' => $consultation,
            'patient' => $consultation->patient,
            'reference' => $consultation->consultation_number,
        ]);
    }

    public function labReport(LabOrder $order): string
    {
        $order->loadMissing(['patient', 'doctor', 'items.results']);

        return $this->render('dme::pdf.lab-report', [
            'order' => $order,
            'patient' => $order->patient,
            'reference' => $order->order_number,
        ]);
    }

    public function dischargeSummary(Hospitalization $hospitalization): string
    {
        $hospitalization->loadMissing(['patient', 'doctor', 'service', 'events']);

        return $this->render('dme::pdf.discharge-summary', [
            'hospitalization' => $hospitalization,
            'patient' => $hospitalization->patient,
            'reference' => $hospitalization->hospitalization_number,
        ]);
    }

    public function patientSummary(Patient $patient): string
    {
        $patient->loadMissing(['allergies', 'chronicConditions', 'medications', 'attendingDoctor']);

        return $this->render('dme::pdf.patient-summary', [
            'patient' => $patient,
            'reference' => $patient->patient_number,
        ]);
    }

    /**
     * Certificat médical libre.
     */
    public function certificate(Patient $patient, string $title, string $content, ?string $reference = null): string
    {
        return $this->render('dme::pdf.certificate', [
            'patient' => $patient,
            'title' => $title,
            'content' => $content,
            'reference' => $reference ?? $patient->patient_number,
        ]);
    }

    /**
     * Archive un PDF déjà produit dans le dossier du patient.
     */
    public function archive(
        Patient $patient,
        string $contents,
        string $title,
        string $type,
        ?object $source = null,
    ): MedicalDocument {
        return $this->storage->storeGenerated($patient, $contents, $title, $type, array_filter([
            'source_type' => $source !== null ? $source::class : null,
            'source_id' => $source?->getKey(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $view, array $data): string
    {
        $reference = (string) ($data['reference'] ?? '');

        $data['facility'] = config('dme.facility');
        $data['generatedAt'] = now();
        $data['qrCode'] = $this->qrCodes->dataUri(
            rtrim((string) config('app.url'), '/').'/documents/verifier/'.$reference
        );

        return Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->output();
    }
}
