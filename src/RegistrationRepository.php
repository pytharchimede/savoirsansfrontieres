<?php
declare(strict_types=1);
final class RegistrationRepository {
 public function __construct(private PDO $db) {}
 public function isActiveSubject(string $code): bool {
  $stmt=$this->db->prepare('SELECT 1 FROM subjects WHERE code=? AND active=1 LIMIT 1');
  $stmt->execute([$code]);return (bool)$stmt->fetchColumn();
 }
 public function create(array $data): void {
  $stmt=$this->db->prepare('INSERT INTO registrations (type,full_name,email,phone,subject,student_level,created_at) VALUES (:type,:name,:email,:phone,:subject,:student_level,NOW())');
  $stmt->execute(['type'=>$data['type'],'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone'],'subject'=>$data['subject'],'student_level'=>$data['studentLevel']]);
 }
}
