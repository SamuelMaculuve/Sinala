<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SignatureService {
    public function store(string $dataUrl, int $organizationId, string $context): array {
        if(!preg_match('/^data:image\/png;base64,(.+)$/',$dataUrl,$match)) throw ValidationException::withMessages(['signature'=>'Assinatura inválida.']);
        $binary=base64_decode($match[1],true);
        if($binary===false || strlen($binary)<100 || strlen($binary)>2_000_000) throw ValidationException::withMessages(['signature'=>'Desenhe uma assinatura válida.']);
        $path="signatures/{$organizationId}/{$context}/".Str::uuid().'.png'; Storage::disk('local')->put($path,$binary);
        return ['uuid'=>(string)Str::uuid(),'path'=>$path,'sha256'=>hash('sha256',$binary),'mime_type'=>'image/png'];
    }
}
