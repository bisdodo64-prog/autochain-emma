<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;

use App\Models\Alert;

use App\Models\Maintenance;

use App\Models\MileageRecord;

use App\Models\Vehicle;

use App\Services\Blockchain\EthereumService;

use Illuminate\Http\Request;



class BlockchainController extends Controller

{

    protected EthereumService $ethereum;



    public function __construct(EthereumService $ethereum)

    {

        $this->ethereum = $ethereum;

    }



    public function status()

    {

        return response()->json([

            'blockchain' => $this->ethereum->getStatus(),

        ]);

    }



    public function transactions()

    {

        $items = collect();



        Vehicle::whereNotNull('blockchain_tx_hash')

            ->orderByDesc('created_at')

            ->get()

            ->each(function (Vehicle $vehicle) use ($items) {

                $formatted = $this->formatTxHash($vehicle->blockchain_tx_hash);

                $items->push(array_merge([

                    'id' => 'reg-' . $vehicle->id,

                    'type' => 'registration',

                    'label' => 'Enregistrement véhicule',

                    'vehicle' => "{$vehicle->brand} {$vehicle->model}",

                    'vehicle_id' => $vehicle->id,

                    'date' => $vehicle->created_at?->format('d/m/Y') ?? now()->format('d/m/Y'),

                    'sort_at' => $vehicle->created_at ?? now(),

                ], $formatted));

            });



        Maintenance::with('vehicle')

            ->whereNotNull('blockchain_tx_hash')

            ->orderByDesc('performed_at')

            ->get()

            ->each(function (Maintenance $record) use ($items) {

                $vehicle = $record->vehicle;

                $formatted = $this->formatTxHash($record->blockchain_tx_hash);

                $items->push(array_merge([

                    'id' => 'mnt-' . $record->id,

                    'type' => 'maintenance',

                    'label' => 'Maintenance certifiée',

                    'vehicle' => $vehicle ? "{$vehicle->brand} {$vehicle->model}" : 'Véhicule',

                    'vehicle_id' => $record->vehicle_id,

                    'date' => $record->performed_at?->format('d/m/Y') ?? now()->format('d/m/Y'),

                    'sort_at' => $record->performed_at ?? now(),

                ], $formatted));

            });



        MileageRecord::with('vehicle')

            ->whereNotNull('blockchain_tx_hash')

            ->orderByDesc('recorded_at')

            ->get()

            ->each(function (MileageRecord $record) use ($items) {

                $vehicle = $record->vehicle;

                $formatted = $this->formatTxHash($record->blockchain_tx_hash);

                $items->push(array_merge([

                    'id' => 'mil-' . $record->id,

                    'type' => 'mileage',

                    'label' => 'Relevé kilométrique',

                    'vehicle' => $vehicle ? "{$vehicle->brand} {$vehicle->model}" : 'Véhicule',

                    'vehicle_id' => $record->vehicle_id,

                    'date' => $record->recorded_at?->format('d/m/Y') ?? now()->format('d/m/Y'),

                    'sort_at' => $record->recorded_at ?? now(),

                ], $formatted));

            });



        $sorted = $items->sortByDesc('sort_at')->values()->map(function ($item) {

            unset($item['sort_at']);

            return $item;

        });



        return response()->json($sorted);

    }



    public function stats()

    {

        return response()->json([

            'tx' => Maintenance::whereNotNull('blockchain_tx_hash')->count()

                + MileageRecord::whereNotNull('blockchain_tx_hash')->count()

                + Vehicle::whereNotNull('blockchain_tx_hash')->count(),

            'certified' => Vehicle::whereNotNull('blockchain_id')->count(),

            'maintenance' => Maintenance::whereNotNull('blockchain_tx_hash')->count(),

            'anomalies' => Alert::whereNull('dismissed_at')->where('severity', 'critical')->count(),

        ]);

    }



    protected function formatTxHash(?string $fullHash): array

    {

        if (!$fullHash || !str_starts_with($fullHash, '0x')) {

            return [

                'hash' => '—',

                'full_hash' => $fullHash,

                'explorer_url' => null,

            ];

        }



        $short = strlen($fullHash) > 12

            ? substr($fullHash, 0, 6) . '…' . substr($fullHash, -4)

            : $fullHash;



        $template = (string) config('blockchain.explorer_tx_url', '');

        $explorerUrl = $template

            ? str_replace('{hash}', $fullHash, $template)

            : null;



        return [

            'hash' => $short,

            'full_hash' => $fullHash,

            'explorer_url' => $explorerUrl,

        ];

    }

}


