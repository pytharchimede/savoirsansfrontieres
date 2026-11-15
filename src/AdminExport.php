<?php
declare(strict_types=1);
final class AdminExport {
 public static function xlsx(array $rows): never {
  if (!class_exists('ZipArchive')) { http_response_code(503);exit('Extension PHP zip nécessaire pour Excel'); }
  $headers=['ID','Profil','Nom et prénom','Email','Téléphone','Date inscription'];
  $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
  $all=array_merge([$headers],array_map(fn($r)=>[(string)$r['id'],$r['type'],$r['full_name'],$r['email'],$r['phone'],$r['created_at']],$rows));
  foreach($all as $i=>$line){$xml.='<row r="'.($i+1).'">';foreach($line as $j=>$v){$letter=chr(65+$j);$xml.='<c r="'.$letter.($i+1).'" t="inlineStr"><is><t>'.htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>';}$xml.='</row>';}
  $xml.='</sheetData></worksheet>';
  $tmp=tempnam(sys_get_temp_dir(),'ssf_xlsx_');$zip=new ZipArchive();
  if($zip->open($tmp,ZipArchive::OVERWRITE)!==true){http_response_code(503);exit('Export indisponible');}
  $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
  $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
  $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inscriptions" sheetId="1" r:id="rId1"/></sheets></workbook>');
  $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
  $zip->addFromString('xl/worksheets/sheet1.xml',$xml);$zip->close();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="ssf-inscriptions-'.date('Ymd').'.xlsx"');
  header('Cache-Control: no-store');readfile($tmp);unlink($tmp);exit;
 }
 public static function pdf(array $rows): never {
  // PDF simple sans dépendance externe ; caractères convertis pour les polices PDF standard.
  $lines=['SAVOIR SANS FRONTIERES - INSCRIPTIONS','Export du '.date('d/m/Y H:i'),''];
  foreach($rows as $r){$lines[]='#'.$r['id'].' | '.$r['type'].' | '.$r['full_name'];$lines[]='  '.$r['email'].' | '.$r['phone'].' | '.$r['created_at'];$lines[]='';}
  if(!$rows)$lines[]='Aucune inscription';
  $pages=array_chunk($lines,48);$objects=[];$objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
  $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
  $kids=[];$next=4;
  foreach($pages as $page){$pid=$next++;$cid=$next++;$kids[]="$pid 0 R";$content="BT /F1 10 Tf 42 790 Td 14 TL\n";
   foreach($page as $line){$line=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$line)?:'';$line=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$line);$content.='('.substr($line,0,105).") Tj T*\n";}$content.="ET";
   $objects[$pid]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents $cid 0 R >>";
   $objects[$cid]="<< /Length ".strlen($content)." >>\nstream\n$content\nendstream";
  }
  $objects[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
  ksort($objects);$pdf="%PDF-1.4\n";$offset=[0];
  foreach($objects as $id=>$obj){$offset[$id]=strlen($pdf);$pdf.="$id 0 obj\n$obj\nendobj\n";}
  $xref=strlen($pdf);$pdf.="xref\n0 ".(max(array_keys($objects))+1)."\n0000000000 65535 f \n";
  for($i=1;$i<=max(array_keys($objects));$i++)$pdf.=sprintf('%010d 00000 n ',$offset[$i])."\n";
  $pdf.="trailer\n<< /Size ".(max(array_keys($objects))+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
  header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="ssf-inscriptions-'.date('Ymd').'.pdf"');header('Cache-Control: no-store');echo $pdf;exit;
 }
}
