<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Vehicle;
use App\Services\Blockchain\IPFSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    protected $ipfs;

    public function __construct(IPFSService $ipfs)
    {
        $this->ipfs = $ipfs;
    }

    public function index(Request $request, $vehicleId)
    {
        $vehicle = Vehicle::findOrFail($vehicleId);
        $documents = $vehicle->documents()->orderBy('created_at', 'desc')->get();
        
        return response()->json($documents);
    }

    public function upload(Request $request, $vehicleId)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:registration,insurance,tech_control,facture,autre',
            'file' => 'required|file|max:10240', // Max 10MB
            'expiry_date' => 'nullable|date',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::findOrFail($vehicleId);
        $file = $request->file('file');
        
        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getPathname());
        
        // Store file locally
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('documents/' . $vehicleId, $fileName, 'local');
        
        // Store on IPFS if public
        $ipfsHash = null;
        if ($request->boolean('is_public')) {
            try {
                $ipfsHash = $this->ipfs->add($file->getPathname());
            } catch (\Exception $e) {
                // Continue even if IPFS fails
            }
        }

        $docType = $request->document_type === 'autre' ? 'facture' : $request->document_type;

        $document = $vehicle->documents()->create([
            'document_type' => $docType,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_hash' => $fileHash,
            'ipfs_hash' => $ipfsHash,
            'expiry_date' => $request->expiry_date,
            'uploaded_by' => $request->user()->id,
            'is_public' => $request->boolean('is_public'),
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document,
        ], 201);
    }

    public function show($id)
    {
        $document = Document::with('vehicle')->findOrFail($id);
        
        return response()->json($document);
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
        
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        
        // Delete file from storage
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }
        
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    public function verify(Request $request, $vehicleId, $documentId)
    {
        $document = Document::where('vehicle_id', $vehicleId)->findOrFail($documentId);
        
        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $filePath = Storage::disk('local')->path($document->file_path);
        $currentHash = hash_file('sha256', $filePath);
        
        $isValid = $currentHash === $document->file_hash;

        return response()->json([
            'is_valid' => $isValid,
            'stored_hash' => $document->file_hash,
            'current_hash' => $currentHash,
            'ipfs_hash' => $document->ipfs_hash,
        ]);
    }

    public function certify($id)
    {
        $document = Document::with('vehicle')->findOrFail($id);

        if (!$document->ipfs_hash) {
            if (!Storage::disk('local')->exists($document->file_path)) {
                return response()->json(['message' => 'Fichier introuvable sur le serveur'], 404);
            }

            $filePath = Storage::disk('local')->path($document->file_path);
            try {
                $document->ipfs_hash = $this->ipfs->add($filePath);
                $document->is_public = true;
                $document->save();
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Certification IPFS échouée: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Document certifié (hash IPFS enregistré)',
            'document' => $document->fresh(),
            'ipfs_url' => $this->ipfs->getUrl($document->ipfs_hash),
            'live' => $this->ipfs->isLive(),
        ]);
    }
}
