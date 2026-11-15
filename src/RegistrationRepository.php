<?php
declare(strict_types=1);
final class RegistrationRepository {
 public function __construct(private PDO $db) {}
 public function create(array $data): void {
  $stmt=$this->db->prepare('INSERT INTO registrations (type,full_name,email,phone,subject,student_level,created_at) VALUES (:type,:name,:email,:phone,:subject,:student_level,NOW())');
  $stmt->execute(['type'=>$data['type'],'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone'],'subject'=>$data['subject'],'student_level'=>$data['studentLevel']]);
 }
}
