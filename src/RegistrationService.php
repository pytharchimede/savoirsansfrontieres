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
  if($type==='etudiant' && (!in_array($subject,['mathematiques','physique'],true) || $studentLevel===null || mb_strlen($studentLevel)<2 || mb_strlen($studentLevel)>100))throw new InvalidArgumentException('Veuillez choisir la matière et préciser la classe ou le niveau.');
  if (!in_array($type,['etudiant','enseignant'],true) || mb_strlen($name)<3 || mb_strlen($name)>180 ||
      !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 ||
      strlen($phone)<6 || strlen($phone)>50 || empty($input['consent'])) {
   throw new InvalidArgumentException('Vérifiez les informations saisies et votre consentement.');
  }
  $this->repository->create(compact('type','name','email','phone','subject','studentLevel'));
  return compact('type','name','email','phone');
 }
}
