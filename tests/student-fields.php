<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/RegistrationRepository.php';
require dirname(__DIR__).'/src/RegistrationService.php';
$db=ssf_db();
$email='ssf-test-'.bin2hex(random_bytes(6)).'@example.invalid';
try {
 $service=new RegistrationService(new RegistrationRepository($db));
 $service->register(['type'=>'etudiant','name'=>'Test Matiere SSF','email'=>$email,'phone'=>'+2250700000000','subject'=>'mathematiques','student_level'=>'terminale','consent'=>'1']);
 $s=$db->prepare('SELECT subject,student_level FROM registrations WHERE email=? ORDER BY id DESC LIMIT 1');$s->execute([$email]);$row=$s->fetch(PDO::FETCH_ASSOC);
 if(!$row || $row['subject']!=='mathematiques' || $row['student_level']!=='terminale')throw new RuntimeException('Enregistrement matière/niveau incorrect');
 echo "Inscription apprenant : OK\n";
 $bad=false;
 try{$service->register(['type'=>'etudiant','name'=>'Test Invalide','email'=>'bad@example.invalid','phone'=>'+2250700000000','consent'=>'1']);}catch(InvalidArgumentException $e){$bad=true;}
 if(!$bad)throw new RuntimeException('Matière et classe non obligatoires');
 echo "Validation matière/niveau : OK\n";
 echo "SUCCES : test formulaire terminé\n";
}finally{
 $s=$db->prepare('DELETE FROM registrations WHERE email=?');$s->execute([$email]);
}
