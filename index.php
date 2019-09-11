<?php
require_once './class/autoload.php';

// парсим жилые комплексы + сохраняем в БД
echo Parser::getСomplexes()."<hr>";

// выводим список спарсенных ЖК комплексов из БД
$stmt = DB::run("SELECT * FROM complexes");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
{
    $info.= $row['name']."<br>";
    $info.= "адрес: ".$row['address']."<br>";
    $info.= "готовность: ".$row['deadline']."<br>";
    $info.= "квартир свободно: ".$row['flat']."<br><br>";
}
echo $info;

// парсим все свободные квартиры + сохраняем в БД
echo Parser::getFlats();