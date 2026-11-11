<?php
declare(strict_types=1);
namespace Dalo;

final class AttackCatalog
{
    public const VERSION = '1.0.0';
    private const PATTERNS = [
        'credential_stuffing' => ['name'=>'Credential stuffing','signal'=>'failures and distinct accounts','checks'=>['failed_auth_count'=>5,'distinct_accounts_5m'=>4],'score'=>25,'labyrinth'=>'arianna','variants'=>[
            ['id'=>'generic_auth','status'=>401,'code'=>'AUTH_REJECTED','message'=>'Credenziali non verificate. Ripeti la richiesta.'],['id'=>'session_refresh','status'=>403,'code'=>'SESSION_REFRESH_REQUIRED','message'=>'Sessione da aggiornare.'],['id'=>'reset_queue','status'=>202,'code'=>'RESET_QUEUED','message'=>'Se i dati corrispondono, riceverai istruzioni.']]],
        'password_spray' => ['name'=>'Password spraying','signal'=>'few attempts across many accounts','checks'=>['distinct_accounts_5m'=>8,'failures_per_account_max_5m'=>2],'score'=>25,'labyrinth'=>'specchio','variants'=>[
            ['id'=>'auth_delay','status'=>429,'code'=>'AUTH_RETRY','message'=>'Richiesta temporaneamente limitata.'],['id'=>'generic_auth','status'=>401,'code'=>'AUTH_REJECTED','message'=>'Credenziali non verificate.'],['id'=>'mfa_check','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.']]],
        'endpoint_enumeration' => ['name'=>'Endpoint enumeration','signal'=>'many distinct paths and not-found responses','checks'=>['distinct_paths_5m'=>12,'not_found_ratio_5m'=>0.5],'score'=>20,'labyrinth'=>'specchio','variants'=>[
            ['id'=>'generic_404','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.'],['id'=>'method_error','status'=>405,'code'=>'METHOD_UNAVAILABLE','message'=>'Metodo non disponibile.'],['id'=>'generic_404_alt','status'=>404,'code'=>'ROUTE_UNAVAILABLE','message'=>'Percorso non disponibile.']]],
        'token_replay' => ['name'=>'Token replay','signal'=>'reused single-use token','checks'=>['token_reused'=>true],'score'=>30,'labyrinth'=>'arianna','variants'=>[
            ['id'=>'token_expired','status'=>403,'code'=>'TOKEN_EXPIRED','message'=>'Token non più valido.'],['id'=>'session_refresh','status'=>401,'code'=>'SESSION_REFRESH_REQUIRED','message'=>'Sessione da aggiornare.'],['id'=>'verification_pending','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.']]],
        'reset_abuse' => ['name'=>'Password reset abuse','signal'=>'reset bursts across accounts','checks'=>['reset_requests_5m'=>8,'distinct_accounts_5m'=>5],'score'=>25,'labyrinth'=>'arianna','variants'=>[
            ['id'=>'reset_queue','status'=>202,'code'=>'RESET_QUEUED','message'=>'Se i dati corrispondono, riceverai istruzioni.'],['id'=>'generic_auth','status'=>429,'code'=>'RESET_RATE_LIMITED','message'=>'Richiesta temporaneamente limitata.'],['id'=>'verification_pending','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.']]],
        'adaptive_error_probing' => ['name'=>'Adaptive error probing','signal'=>'repeated requests after varied errors','checks'=>['distinct_error_codes_5m'=>4,'requests_after_error_5m'=>6],'score'=>20,'labyrinth'=>'arianna','variants'=>[
            ['id'=>'generic_400','status'=>400,'code'=>'REQUEST_UNAVAILABLE','message'=>'Richiesta non elaborabile.'],['id'=>'generic_404','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.'],['id'=>'generic_400_alt','status'=>422,'code'=>'VALIDATION_UNAVAILABLE','message'=>'Richiesta non verificabile.']]],
        'workflow_bypass' => ['name'=>'Authentication workflow bypass','signal'=>'protected actions without completed authentication','checks'=>['protected_without_auth_5m'=>3],'score'=>30,'labyrinth'=>'minotauro','variants'=>[
            ['id'=>'auth_required','status'=>401,'code'=>'AUTH_REQUIRED','message'=>'Autenticazione richiesta.'],['id'=>'verification_pending','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.'],['id'=>'resource_unavailable','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.']]],
        'honeypot_followup' => ['name'=>'Honeypot follow-up','signal'=>'decoy endpoint hit followed by more requests','checks'=>['honeypot_hit'=>true,'requests_after_honeypot_5m'=>2],'score'=>35,'labyrinth'=>'minotauro','variants'=>[
            ['id'=>'decoy_error','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.'],['id'=>'decoy_auth','status'=>401,'code'=>'AUTH_REQUIRED','message'=>'Autenticazione richiesta.'],['id'=>'decoy_reset','status'=>202,'code'=>'RESET_QUEUED','message'=>'Se i dati corrispondono, riceverai istruzioni.']]],
        'distributed_automation' => ['name'=>'Distributed automation','signal'=>'shared campaign marker across sources','checks'=>['campaign_sources_5m'=>5,'campaign_requests_5m'=>20],'score'=>25,'labyrinth'=>'minotauro','variants'=>[
            ['id'=>'auth_delay','status'=>429,'code'=>'REQUEST_LIMITED','message'=>'Richiesta temporaneamente limitata.'],['id'=>'verification_pending','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.'],['id'=>'resource_unavailable','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.']]],
        'payload_mutation' => ['name'=>'Payload mutation','signal'=>'many distinct payload fingerprints on one action','checks'=>['distinct_payloads_5m'=>8,'same_action_requests_5m'=>10],'score'=>20,'labyrinth'=>'arianna','variants'=>[
            ['id'=>'generic_400','status'=>400,'code'=>'REQUEST_UNAVAILABLE','message'=>'Richiesta non elaborabile.'],['id'=>'validation_error','status'=>422,'code'=>'VALIDATION_UNAVAILABLE','message'=>'Richiesta non verificabile.'],['id'=>'auth_delay','status'=>429,'code'=>'REQUEST_LIMITED','message'=>'Richiesta temporaneamente limitata.']]],
        'csrf_probe' => ['name'=>'CSRF and session probing','signal'=>'invalid CSRF and rapid session transitions','checks'=>['invalid_csrf_5m'=>3,'session_transitions_5m'=>5],'score'=>20,'labyrinth'=>'specchio','variants'=>[
            ['id'=>'csrf_error','status'=>403,'code'=>'REQUEST_REJECTED','message'=>'Richiesta non verificabile.'],['id'=>'session_refresh','status'=>401,'code'=>'SESSION_REFRESH_REQUIRED','message'=>'Sessione da aggiornare.'],['id'=>'verification_pending','status'=>202,'code'=>'VERIFICATION_PENDING','message'=>'Verifica in elaborazione.']]],
        'instruction_canary' => ['name'=>'Instruction or canary probing','signal'=>'decoy canary markers observed by application telemetry','checks'=>['canary_hits_5m'=>2],'score'=>30,'labyrinth'=>'minotauro','variants'=>[
            ['id'=>'decoy_error','status'=>404,'code'=>'RESOURCE_UNAVAILABLE','message'=>'Risorsa non disponibile.'],['id'=>'decoy_auth','status'=>401,'code'=>'AUTH_REQUIRED','message'=>'Autenticazione richiesta.'],['id'=>'decoy_reset','status'=>202,'code'=>'RESET_QUEUED','message'=>'Se i dati corrispondono, riceverai istruzioni.']]],
    ];

    public function detect(array $features): array
    {
        $matches=[];
        foreach(self::PATTERNS as $id=>$pattern){$ok=true;$observed=[];foreach($pattern['checks'] as $key=>$threshold){$value=$features[$key]??null;$pass=is_bool($threshold)?$value===$threshold:(is_int($threshold)||is_float($threshold))&&is_numeric($value)&&(str_ends_with($key,'ratio')?(float)$value>=$threshold:(float)$value>=$threshold);if(!$pass){$ok=false;break;}$observed[$key]=$value;}if($ok)$matches[]=['id'=>$id,'name'=>$pattern['name'],'signal'=>$pattern['signal'],'score'=>$pattern['score'],'labyrinth'=>$pattern['labyrinth'],'observed'=>$observed,'variants'=>$pattern['variants']];}
        usort($matches,static fn($a,$b)=>$b['score']<=>$a['score']);return $matches;
    }
    public static function all(): array { return self::PATTERNS; }
}
