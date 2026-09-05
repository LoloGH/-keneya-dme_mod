<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Hospitalization;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Tableau de bord médical (§10).
 *
 * Les indicateurs sont calculés par des requêtes agrégées sur colonnes
 * indexées : aucune collection complète n'est chargée en mémoire, ce qui
 * garde l'écran rapide même avec plusieurs années de données (§58).
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();

        return view('dashboard', [
            'kpis' => $this->kpis($today),
            'activityChart' => $this->consultationsPerDay(),
            'serviceBreakdown' => $this->consultationsByService(),
            'recentActivity' => $this->recentActivity(),
            'upcomingAppointments' => $this->upcomingAppointments(),
        ]);
    }

    /**
     * Indicateurs du jour (§10).
     *
     * @return array<string, array{label: string, value: int, hint: string, route: string|null}>
     */
    private function kpis(Carbon $today): array
    {
        return [
            'patients' => [
                'label' => 'Patients du jour',
                'value' => Patient::whereDate('created_at', $today)->count(),
                'hint' => 'Nouveaux dossiers ouverts',
                'route' => route('patients.index'),
            ],
            'consultations' => [
                'label' => 'Consultations',
                'value' => Consultation::whereDate('started_at', $today)->count(),
                'hint' => 'Réalisées aujourd’hui',
                'route' => route('consultations.index'),
            ],
            'appointments' => [
                'label' => 'Rendez-vous',
                'value' => Appointment::whereDate('scheduled_for', $today)
                    ->whereIn('status', ['scheduled', 'confirmed'])->count(),
                'hint' => 'Programmés aujourd’hui',
                'route' => route('appointments.index'),
            ],
            'hospitalized' => [
                'label' => 'Patients hospitalisés',
                'value' => Hospitalization::where('status', 'admitted')->count(),
                'hint' => 'Séjours en cours',
                'route' => route('hospitalizations.index'),
            ],
            'pending_exams' => [
                'label' => 'Examens en attente',
                'value' => LabOrder::whereIn('status', ['requested', 'in_progress'])->count()
                    + ImagingOrder::whereIn('status', ['requested', 'scheduled'])->count(),
                'hint' => 'Laboratoire et imagerie',
                'route' => route('laboratory.index'),
            ],
            'available_results' => [
                'label' => 'Résultats disponibles',
                'value' => LabOrder::whereIn('status', ['available', 'validated'])
                    ->whereDate('completed_at', $today)->count(),
                'hint' => 'Validés aujourd’hui',
                'route' => route('laboratory.index'),
            ],
            'prescriptions' => [
                'label' => 'Ordonnances du jour',
                'value' => Prescription::whereDate('issued_on', $today)->count(),
                'hint' => 'Émises aujourd’hui',
                'route' => route('prescriptions.index'),
            ],
        ];
    }

    /**
     * Consultations des 14 derniers jours, pour le graphique d'activité.
     *
     * @return Collection<int, array{label: string, date: string, value: int}>
     */
    private function consultationsPerDay(): Collection
    {
        $start = Carbon::today()->subDays(13);

        $counts = Consultation::query()
            ->where('started_at', '>=', $start)
            ->get(['started_at'])
            ->groupBy(fn (Consultation $consultation) => $consultation->started_at->format('Y-m-d'))
            ->map->count();

        return collect(range(0, 13))->map(function (int $offset) use ($start, $counts) {
            $date = $start->copy()->addDays($offset);

            return [
                'label' => $date->translatedFormat('D d'),
                'date' => $date->format('Y-m-d'),
                'value' => (int) ($counts[$date->format('Y-m-d')] ?? 0),
            ];
        });
    }

    /**
     * Répartition des consultations par service sur 30 jours (§10).
     *
     * @return Collection<int, array{label: string, value: int, share: float}>
     */
    private function consultationsByService(): Collection
    {
        $rows = Consultation::query()
            ->with('service:id,name')
            ->where('started_at', '>=', Carbon::today()->subDays(30))
            ->get(['id', 'service_id'])
            ->groupBy(fn (Consultation $consultation) => $consultation->service?->name ?? 'Non affecté')
            ->map->count()
            ->sortDesc();

        $total = max(1, $rows->sum());

        return $rows->map(fn (int $count, string $label) => [
            'label' => $label,
            'value' => $count,
            'share' => round($count / $total * 100, 1),
        ])->values();
    }

    /**
     * Timeline d'activité récente (§10).
     *
     * @return Collection<int, AuditLog>
     */
    private function recentActivity(): Collection
    {
        return AuditLog::query()
            ->with(['causer:id,name,first_name,last_name,title', 'patient:id,patient_number,first_name,last_name'])
            ->whereIn('action', ['created', 'updated', 'downloaded'])
            ->latest('created_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Appointment> */
    private function upcomingAppointments(): Collection
    {
        return Appointment::query()
            ->with(['patient:id,patient_number,first_name,last_name', 'doctor:id,name,first_name,last_name,title'])
            ->upcoming()
            ->limit(6)
            ->get();
    }
}
