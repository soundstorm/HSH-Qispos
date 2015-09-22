<?php
$regexp = '#<tr>[\s\t\n\r]*'.
	'(?P<parent><!--[\s\t\n\r]*-->)?[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(<b>)?(?P<nr>[\d]*?)?(</b>)?[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<name>[\w\s\d-_]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<art>[\w]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<note>[\d,]*?)[\s\t\n\r]*'.
	'(<a(.*?)href="(?<detailpage>([^"]*))"(.*?)</a>)?[\s\t\n\r]*'.
	'</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<status>[\w]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<vermerk>[\*\s\w\d-_]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<credits>[\d]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<versuch>[\d]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<semester>[\w \d\/-]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<pdatum>[\.\d]*?)[\s\t\n\r]*</td>[\s\t\n\r]*'.
	'<td[^>]*>[\s\t\n\r]*(?P<adatum>[\.\d]*?)[\s\t\n\r]*</td>[\s\t\n\r]*</tr>#i';


$startpage = 'https://qispos.fh-hannover.de/qisserver/rds?state=user&type=1&category=auth.login&startpage=portal.vm';

$user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7";

$headers[] = "Connection: keep-alive";
$headers[] = "Proxy-Connection: keep-alive";
$headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
$headers[] = "Accept-Encoding: gzip, deflate";

$username = $_POST['username'];
$password = $_['password'];

$data = array(
	'asdf'	=> $username,
	'fdsa'	=> $password,
	'submit'=>'Absenden'
);

$postinfo = http_build_query($data);

$ck = "./cookie.txt";

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_NOBODY, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_COOKIEJAR, $ck);
curl_setopt($ch, CURLOPT_COOKIEFILE, $ck);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

//Login
curl_setopt($ch, CURLOPT_URL, $startpage);
curl_setopt($ch, CURLOPT_REFERER, 'https://qispos.fh-hannover.de/');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
$html = curl_exec($ch);
$html = str_replace('"/','"https://qispos.fh-hannover.de/', $html);


curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_POST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, NULL);
curl_setopt($ch, CURLOPT_REFERER, $startpage);

$html = preg_match('#<a(.*?)href="(.*?)"(.*?)>(.*?)Prüfungen</a>#i', $html, $matches);
$nextPage = str_replace('&amp;','&',$matches[2]);
curl_setopt($ch, CURLOPT_URL, $nextPage);
$html = curl_exec($ch);
curl_setopt($ch, CURLOPT_REFERER, $nextPage);

preg_match('#<a(.*?)href="(.*?)"(.*?)>Notenspiegel</a>#i', $html, $matches);
$notenPage = str_replace('&amp;','&',$matches[2]);

curl_setopt($ch, CURLOPT_URL, $notenPage);
$html = curl_exec($ch);

$abschluesse = array();
$Aoffset = 0;
while (preg_match('#<a(.*?)href="(?P<href>.*?)"(.*?)title="Leistungen für Abschluss (?P<abschluss>[\s\d\w]*?) anzeigen"(.*?)>(.*?)</a>#i', $html, $matches, PREG_OFFSET_CAPTURE, $Aoffset)) {
	$nextPage = str_replace('&amp;','&',$matches['href'][0]);
	$abschluss = $matches['abschluss'][0];
	$Aoffset = $matches[6][1];
	
	curl_setopt($ch, CURLOPT_REFERER, $notenPage);
	curl_setopt($ch, CURLOPT_URL, $nextPage);

	$noten = curl_exec($ch);

	$lastOffset = 0;
	$result = array();
	$parent = array();
	while(preg_match($regexp, $noten, $matches, PREG_OFFSET_CAPTURE, $lastOffset)) {
		if (preg_match("/(\d+?)\.(\d+?)\.(\d+)/", $matches['pdatum'][0], $mtchs)) {
			$pdatum = $mtchs[3].'-'.$mtchs[2].'-'.$mtchs[1];
		} else {
			$pdatum = '0000-00-00';
		}
		if (preg_match("/(\d+?)\.(\d+?)\.(\d+)/", $matches['adatum'][0], $mtchs)) {
			$adatum = $mtchs[3].'-'.$mtchs[2].'-'.$mtchs[1];
		} else {
			$adatum = '0000-00-00';
		}
		$credits = $matches['credits'][0]+0;
		if ($matches['note'][0] == '') $note = '0.0';
		else {
			$note = str_replace(',','.',$matches['note'][0]);
			if (!strpos($note,'.')) $note.='.0';
		}
		$pnr = ($matches['nr'][0]+0);
		if ($matches['parent'][0] != '') {
			$arr = array(
				'nr'		=>	$matches['nr'][0],
				'name'		=>	$matches['name'][0],
				'art'		=>	$matches['art'][0],
				'note'		=>	$note,
				'status'	=>	$matches['status'][0],
				'vermerk'	=>	$matches['vermerk'][0],
				'credits'	=>	$credits,
				'pruefungen'	=>	array()
			);
			$parent = $arr;
			$result[] = $arr;
		} else {
			$arr = array(
				'nr'		=>	$matches['nr'][0],
				'name'		=>	$matches['name'][0],
				'art'		=>	$matches['art'][0],
				'note'		=>	$note,
				'noten'		=>	array(),
				'status'	=>	$matches['status'][0],
				'vermerk'	=>	$matches['vermerk'][0],
				'credits'	=>	$credits,
				'versuch'	=>	$matches['versuch'][0],
				'semester'	=>	$matches['semester'][0],
				'pdatum'	=>	$pdatum,
				'adatum'	=>	$adatum,
			);
			
			if ($matches['detailpage'][0] != '') {
				curl_setopt($ch, CURLOPT_REFERER, $nextPage);
				curl_setopt($ch, CURLOPT_URL, str_replace('&amp;','&', $matches['detailpage'][0]));
				$details = curl_exec($ch);
				$reg = '#<td(.*?)tabelle1(.*?)align="right">[\s\t\n\r]*(?P<value>[\d,]+)[\s\t\n\r]*#i';
				if (preg_match_all($reg, $details, $m)) {
					$arr['noten']['1'] = $m['value'][0];
					$arr['noten']['2'] = $m['value'][1];
					$arr['noten']['3'] = $m['value'][2];
					$arr['noten']['4'] = $m['value'][3];
					$arr['noten']['5'] = $m['value'][4];
					$arr['noten']['teilnehmer'] = $m['value'][5];
					$arr['noten']['druchschnitt'] = $m['value'][6];
				}
			}
			
			$parent['pruefungen'][] = $arr;
			$result[sizeof($result)-1]['pruefungen'][] = $arr;
			$arr['parent'] = $parent['nr'];
		}
		$lastOffset = $matches[floor(sizeof($matches)/2)][1];
	}
	$abschluesse[$abschluss] = $result;
}
curl_close($ch);
print(json_encode($abschluesse));
