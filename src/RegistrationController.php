<?php
declare(strict_types=1);
final class RegistrationController {
 public function submit(): never {
  if (!isset($_SESSION['csrf'],$_POST['csrf']) || !is_string($_POST['csrf']) ||
      !hash_equals($_SESSION['csrf'],$_POST['csrf'])) {
   http_response_code(419); exit('Session expirée. Actualisez la page.');
  }
  if (!empty($_POST['website'])) { http_response_code(400); exit('Requête invalide'); }
  try {
   (new RegistrationService(new RegistrationRepository(ssf_db())))->register($_POST);
   $_SESSION['csrf']=bin2hex(random_bytes(32));
   header('Location: /merci',true,303); exit;
  } catch (InvalidArgumentException $e) {
   http_response_code(422); exit(htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8'));
  } catch (Throwable $e) {
   error_log('SSF registration error: '.$e->getMessage());
   http_response_code(503); exit('Service momentanément indisponible.');
  }
 }
}
