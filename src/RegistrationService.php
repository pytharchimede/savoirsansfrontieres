<?php
declare(strict_types=1);
final class RegistrationService {
 public function __construct(private RegistrationRepository $repository) {}
 public function register(array $input): array {
  $type=(string)($input['type']??'');
  $name=trim((string)($input['name']??''));
  $email=trim((string)($input['email']??''));
  $phone=trim((string)($input['phone']??''));
  $subject=$type==='etudiant'?trim((string)($input['subject']??'')):null;
  $studentLevel=$type==='etudiant'?trim((string)($input['student_level']??'')):null;
  if($type==='etudiant'){
   $db=ssf_db();
   foreach([['education_subjects',$subject],['education_levels',$studentLevel]] as [$table,$value]){
    if(!$value || mb_strlen($value)>100)throw new InvalidArgumentException('Veuillez sélectionner une matière et un niveau dans les listes.');
    $stmt=$db->prepare("SELECT COUNT(*) FROM $table WHERE code=? AND active=1");
    $stmt->execute([$value]);
    if(!(int)$stmt->fetchColumn())throw new InvalidArgumentException('Veuillez sélectionner une matière et un niveau disponibles dans les listes.');
   }
  }
  if (!in_array($type,['etudiant','enseignant'],true) || mb_strlen($name)<3 || mb_strlen($name)>180 ||
      !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 ||
      strlen($phone)<6 || strlen($phone)>50 || empty($input['consent'])) {
   throw new InvalidArgumentException('Vérifiez les informations saisies et votre consentement.');
  }
  $this->repository->create(compact('type','name','email','phone','subject','studentLevel'));
  return compact('type','name','email','phone');
 }
}
