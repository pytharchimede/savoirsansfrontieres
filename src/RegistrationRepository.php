<?php
declare(strict_types=1);
final class RegistrationRepository {
 public function __construct(private PDO $db) {}
 public function create(array $data): void {
  $stmt=$this->db->prepare('INSERT INTO registrations (type,full_name,email,phone,created_at) VALUES (:type,:name,:email,:phone,NOW())');
  $stmt->execute(['type'=>$data['type'],'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']]);
 }
}
