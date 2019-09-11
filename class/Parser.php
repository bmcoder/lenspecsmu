<?php
// класс для парсинга сайта Lenspecsmu.ru

class Parser extends DB
{
	static public function getСomplexes()
	{
		$url = 'https://lenspecsmu.ru/ajax/search/getFlats.php?city=1&type=1&price%5Bmin%5D=0&price%5Bmax%5D=100&area%5Bmin%5D=0.0&area%5Bmax%5D=286.3&floor%5Bmin%5D=1&floor%5Bmax%5D=25&kitchenArea%5Bmin%5D=0.0&kitchenArea%5Bmax%5D=40.0&group=1&cards=1';
		
		$response = json_decode(file_get_contents($url),true);
		$complexes = $response['complexes'];
		
		// создаём таблицу с комплексами застройщика
		self::query("CREATE TABLE IF NOT EXISTS complexes (id int unique , flat int , name varchar(255) , address varchar(255) , status varchar(255) , deadline varchar(255))");
		
		// готовим множественный запрос к БД (заполняем таблицу complexes)
		$stmt = self::prepare("INSERT INTO complexes VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE flat=?");	
		
		foreach($complexes as $complex)
		{
			$flat = explode(' ',$complex['areaCount'])[0];
			$stmt->execute([$complex['id'],$flat,$complex['name'],$complex['address'],$complex['status'],$complex['deadline'],$flat]);
		}
		
		return "таблица в БД создана и заполнена, читаем данные...";	
	}

	static public function getFlats()
	{
		// создаём таблицу с свободными квартирами комплекса
		self::query("CREATE TABLE IF NOT EXISTS flats (id int unique , complex_id int , number int , area int , floor int , name varchar(255) , room varchar(255) , section varchar(255) , building varchar(255), link varchar(255) , plan varchar(255) , price int)");

		// получаем id,flat из БД complexes для использования в запросах к lenspecsmu
		$stmt = self::run("SELECT * FROM complexes");
		$complexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($complexes as $complex)
		{
			$complex_id = $complex['id'];
			$flat_count = $complex['flat'];
			
			// цикличные запросы к lenspecsmu, лимит 8 квартир на запрос
			// проверка кол-ва квартир
			if($flat_count<=7)
			{
				$url = 'https://lenspecsmu.ru/ajax/search/getFlats.php?object%5B%5D='.$complex_id.'&now=0&next=false&more=20&sortType=&sortDirection=&city=1&type=1&price%5Bmin%5D=2.7&price%5Bmax%5D=71.3&area%5Bmin%5D=0.0&area%5Bmax%5D=286.3&floor%5Bmin%5D=1&floor%5Bmax%5D=25&kitchenArea%5Bmin%5D=0.0&kitchenArea%5Bmax%5D=40.0&group=1';
				self::addFlat($url,$complex_id,$x);
				$requests .=  'запрос https://lenspecsmu.ru/ajax/search/getFlats.php?object%5B%5D='.$complex_id.'&now=0&next=7 [total flats = '.$flat_count.']<br>';
			}
			else
			{
				$x=0;
				$y=7;
				while($x<$flat_count)
				{
					$url = 'https://lenspecsmu.ru/ajax/search/getFlats.php?object%5B%5D='.$complex_id.'&now='.$x.'&next='.$y.'&more=20&sortType=&sortDirection=&city=1&type=1&price%5Bmin%5D=2.7&price%5Bmax%5D=71.3&area%5Bmin%5D=0.0&area%5Bmax%5D=286.3&floor%5Bmin%5D=1&floor%5Bmax%5D=25&kitchenArea%5Bmin%5D=0.0&kitchenArea%5Bmax%5D=40.0&group=1';
					$requests .=  'запрос https://lenspecsmu.ru/ajax/search/getFlats.php?object%5B%5D='.$complex_id.'&now='.$x.'&next='.$y.' [total flats = '.$flat_count.']<br>';
					$requests .= self::addFlat($url,$complex_id,$x).'<br>';
					$x+=8;
					$y+=8;
				}
			}
		}
		return $requests;	
	}

	static public function addFlat($url,$complex_id,$x)
	{
		// получаем данные из lenspecsmu
		$response = json_decode(file_get_contents($url),true);
		// варианты получаемых массивов
		if($x==0)
		{
			$flats = $response['complexes'][0]['flats'];
		}
		else
		{
			$flats = $response['flats'];
		}
		$comment = json_encode($flats);
		// готовим множественный запрос к БД (заполняем таблицу flats)
		$stmt = self::prepare("INSERT IGNORE INTO flats VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");	
		
		foreach($flats as $flat)
		{
			$price = str_replace(' ','',$flat['price']);
			$price = (int)$price;
			$stmt->execute([$flat['id'],$complex_id,$flat['number'],$flat['area'],$flat['floor'],$flat['name'],$flat['room'],$flat['section'],$flat['building'],$flat['link'],$flat['plan'],$price]);
		}		
		return $comment;
	}
}
