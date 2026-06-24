<?php

// sdid 2023
//sdid 1315
function checklib_php(){return 0;}
//~sdid 1315
//require_once ("makeOperLib.php");
if(!function_exists("checkmakeOperLib_php"))
  {include __DIR__."/makeOperLib.php";}
// ~ sdid 2023
//require_once ("easydb.php");
//require_once ("easylib.php");
//require_once ("ShowMainPage.php");
//require_once ("SendMailSmtpClass.php");

ini_set('soap.wsdl_cache_enabled', 0 );
ini_set('soap.wsdl_cache_ttl', 0);

//sdid 3358
// добавить запись в таблицу exlogs
function writeExlogs($dbh,$modulename,$modulepoint,$armessage)//,$userid)
  {
  if(!$dbh) {$dbh = dbconnect();}
  $jsonmessage = "";
  if(isset($armessage))
    {
    if(is_array($armessage))
      {
      //$jsonmessage = json_encode($armessage); //JSON_UNESCAPED_UNICODE
      $jsonmessage = json_encode($armessage, JSON_UNESCAPED_UNICODE); //JSON_UNESCAPED_UNICODE
      //error_log("\n\njsonmessage = $jsonmessage\n\n",0);
      $sql = "insert into ".DBPref."exlogs (f_modulename, f_modulepoint, f_message, f_userid, f_dtcreate) values ";
      $sql = $sql."('$modulename','$modulepoint','".$jsonmessage."', ".$_SESSION['loginid'].", '".date("Y-m-d H:i:s")."')";
      $res = $dbh->exec($sql);
      }
    }
  }
//~sdid 3358

//sdid 3010
// Получение ставки НДС

// Входящие параметры:
// dt – дата операции, на которую нужно получить ставку НДС (формат YYYY-mm-dd)
// specinvid – ИД операции для которой определяем НДС, если задан, то параметры 2.1.3. и т.д. не используются(определяются из операици), для новой операции - 0
// idoper – ИД вида операции
// parenttype – тип операции 2\4 по договору\спецификации
// org – ИД организации
// contrid – ИД клиента
// dogid - ИД договора/спецификации, в зависимости от parenttype
// invozm - возмещаемость
// inbdr - бюджет
// dopcontractid - ИД доп.контракта: если в виде операций f_extform=3, то на шаге проверки маршрутов, проверяем по связанной спецификации, переданном в функцию в доп контракте
// Возвращает spr.f_num ставки НДС, где f_type=10
//function getNDS($dt,$specinvid=0,$idoper=0,$parenttype=0,$org=0,$contrid=0)
//function getNDS($dt,$specinvid=0,$idoper=0,$parenttype=0,$dogid=0,$dopcontractid=0)
function getNDS($inpar)
  {  
  //error_log("\n\ninpar = ".var_export($inpar,true)."\n\n",0);              
  $dt = "";           if(isset($inpar['dt']))             {$dt            = $inpar['dt'];}
  $specinvid=0;       if(isset($inpar['specinvid']))      {$specinvid     = $inpar['specinvid'];}
  $idoper=0;          if(isset($inpar['idoper']))         {$idoper        = $inpar['idoper'];}
  $parenttype=0;      if(isset($inpar['parenttype']))     {$parenttype    = $inpar['parenttype'];}
  $dogid=0;           if(isset($inpar['dogid']))          {$dogid         = $inpar['dogid'];}
  $dopcontractid=0;   if(isset($inpar['dopcontractid']))  {$dopcontractid = $inpar['dopcontractid'];}
  $invozm = 0;        if(isset($inpar['invozm']))         {$invozm        = $inpar['invozm'];}
  $orgid = 0;         if(isset($inpar['orgid']))          {$orgid         = $inpar['orgid'];}
  $contrid = 0;       if(isset($inpar['contrid']))        {$contrid       = $inpar['contrid'];}
  $inbdr = 0;         if(isset($inpar['inbdr']))          {$inbdr         = $inpar['inbdr'];}
  $oprst = 0;         if(isset($inpar['oprst']))          {$oprst         = $inpar['oprst'];}
  //sdid 3548
  $ourexecutor = 0;   if(isset($inpar['ourexecutor']))    {$ourexecutor   = $inpar['ourexecutor'];}
  //~sdid 3548
  //error_log("\n\ninpar = ".var_export($inpar,true)."\n\n",0);
  //echo "$dt|$specinvid|<br>";
  $retval = -1;
  $sql = "";
  $isok = 0;     // флаг, нашли ли ставку НДС
  $executor = 0; // Так называемый "Исполнитель" по ТЗ
  $bdrid = 0;    // Код бюджета (доходный/расходный/не задан)
  $tvozm = -1;   // Код возмещаемости типа операции
  $sivozm = -1;  // Код возмещаемости операции
  $sidttmcr = "";// Дата создания операции

  $stage1_4_3=0;
  $stage1_4_2_2=0;
  $stage1_4_2 = 0;
  $stage1_5   = 0;

  $dbh = dbconnect();
  //echo "specinvid:$specinvid|idoper:$idoper|<br>";
  //if(strlen($dt)>0)
  //  {
    if($specinvid>0)  {$sql="select t.f_isfinance from veda_typeopers t, veda_spec_invoices si where t.f_id=si.f_idoper and si.f_id=$specinvid";}
    elseif($idoper>0) {$sql="select t.f_isfinance from veda_typeopers t where t.f_id=$idoper";}
    //echo "$sql<br>";
    if(($specinvid>0)||($idoper>0))
      {
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {//1.1. Если создаваемая операция делается для вида операций с установленным признаком f_isfinance=1, то значение НДС возвращается «Без НДС»
        if($row['f_isfinance']==1) {$retval=0;$isok=1;return $retval;} 
        }
      }
    // Не нашли НДС первым этапом, продолжаем
    $sibdrarticle = "";
    $tbdrarticle = "";
    if($inbdr>0) {$sibdrarticle=$inbdr;$tbdrarticle=$inbdr;} else {$sibdrarticle="si.f_bdrarticle";$tbdrarticle="t.f_bdrarticle";}
    // Если есть specinvid, определим текущие параметры
    //echo "isok1:$isok|specinvid:$specinvid|<br>";
    if(($isok==0)&&($specinvid>0))
      {
      //$dopsql1 = "si.f_idoper";
      //if($idoper>0) $dopsql1 = $idoper;
      //$sbdrarticle = "";
      //if($inbdr>0) {$sbdrarticle = $inbdr;} else {$sbdrarticle = "f_bdrarticle";}
      //echo "idoper:$idoper<br>";
      if($idoper>0)
        {
        $sql = "
               select t.f_isfinance,
                      (select s84.f_num
                         from veda_spr s84, veda_spr s85, veda_spr s86
                        where s84.f_type=84 and s85.f_type=85 and s86.f_type=86
                          and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$tbdrarticle
                      ) as bdr,
                      t.f_nds as tnds, t.f_vozm as tvozm
               from veda_typeopers t where t.f_id=$idoper
               ";
        }
      else
        {
        $sql = "
               select t.f_isfinance,
                      si.f_specid,
                      si.f_idoper,
                      si.f_parenttype,
                      case 
                        when 
                             (select s84.f_num
                                from veda_spr s84, veda_spr s85, veda_spr s86
                               where s84.f_type=84 and s85.f_type=85 and s86.f_type=86
                                 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$sibdrarticle)=1 
                             then # доход #si.f_orgid
                          (case 
                             when si.f_parenttype=4 then (select f_orgid from veda_dogs where f_id=si.f_specid)
                             else (select d.f_orgid from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=si.f_specid)
                           end)
                        when 
                             (select s84.f_num
                                from veda_spr s84, veda_spr s85, veda_spr s86
                               where s84.f_type=84 and s85.f_type=85 and s86.f_type=86
                                 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$sibdrarticle)=2 
                             then # расход 
                             si.f_contrid
                               #(case 
                               #   when si.f_parenttype=4 then (select f_contrid from veda_dogs where f_id=si.f_specid)
                               #   else (select d.f_contrid from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=si.f_specid)
                               # end)
                           else 0 # бюджет не задан, либо +/-
                       end as executor,
                      (select s84.f_num
                         from veda_spr s84, veda_spr s85, veda_spr s86
                        where s84.f_type=84 and s85.f_type=85 and s86.f_type=86
                          and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$sibdrarticle
                      ) as bdr,
                      t.f_nds as tnds,
                      si.f_nds as sinds,
                      t.f_vozm as tvozm,
                      si.f_isvozm as sivozm,
                      DATE(si.f_dttmcr) as sidttmcr
               from veda_typeopers t, veda_spec_invoices si 
               where t.f_id=si.f_idoper and si.f_id=$specinvid
               ";
        }
      //echo "$sql<br>";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        //echo "idoper1:$idoper|executor:".$row['executor']."|<br>";
        if($idoper==0) {$executor = $row['executor'];}
        else
          {
          //echo $row['f_parenttype']."|dogid:$dogid|".$row['f_specid']."|";
          $sqle = "";
          if($parenttype==2 && $dogid>0) {$sqle = "select d.f_orgid as orgid, d.f_contrid as contrid from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=$dogid";}
          elseif($parenttype==4 && $dogid>0) {$sqle = "select d.f_orgid as orgid, d.f_contrid as contrid from veda_dogs d where d.f_id=$dogid";}
          //elseif($row['f_parenttype']==2 && $dogid==0&&$row['f_specid']>0) 
          //  {$sqle = "select d.f_orgid as orgid, d.f_contrid as contrid from veda_dogs d,veda_specs s where s.f_dogid=d.f_id and s.f_id=".$row['f_specid'];}//при редактировании формы не передается договор
          if(strlen($sqle)>0)
            {
            $rese = $dbh->query($sqle);
            if($rowe = $rese->fetch(PDO::FETCH_ASSOC))
              {
              if($row['bdr']==1) {if($orgid>0){$executor=$orgid;}else{$executor=$rowe['orgid'];}}
              elseif($row['bdr']==2) {if($contrid>0){$executor=$contrid;}else{$executor=$rowe['contrid'];}}
              else {$retval=$row['tnds'];$isok=1;return [$retval,$row['bdr']];}//sdid3342

              if($ourexecutor==1) {if($orgid>0){$executor=$orgid;}else{$executor=$rowe['orgid'];}} //sdid 3548
              }
            }
          }
        //$executor = $row['executor'];
        $bdrid = $row['bdr']; 
        //if(strlen($dt)==0 && $idoper==0) {$sidttmcr = $row['sidttmcr'];}
        if(strlen($dt)>0 && $idoper>0) {$sidttmcr = $dt;}
        else {$sidttmcr = $row['sidttmcr'];}
        //$idoper = $row['f_idoper']; 
        if($idoper==0) {$idoper = $row['f_idoper'];} 
        //$parenttype = $row['f_parenttype'];
        if($parenttype==0) {$parenttype = $row['f_parenttype'];}
        if($invozm>0) {$tvozm = $invozm;}  else {$tvozm = $row['tvozm'];}
        if($invozm>0) {$sivozm = $invozm;} else {$sivozm = $row['sivozm'];}
        //$sidttmcr = $row['sidttmcr'];
        $isourorg = 0; // наша ли организация
        // Если 1.2.1.1.3. Бюджет: не указан, то исполнителя нет, тогда ставим в НДС Вида операций, чтобы пользователь сам указал ставку входящего НДСа
        //echo "bdrid:$bdrid|idoper:$idoper|<br>";
        if(($bdrid!=1)&&($bdrid!=2)&&($idoper>0)) {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342
        //$stage1_5 = 0;
        elseif($idoper>0) // Если бюджет доходный либо расходный, то 1.2.2. Смотрим, является ли исполнитель нашим лицом согласно veda_clients.f_isourorg:
          {
          //echo "bdrid:$bdrid|idoper:$idoper|executor:$executor|<br>";
          $sql="select f_isourorg from veda_clients where f_id=$executor";
          $res1 = $dbh->query($sql);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC)) 
            { // 1.2.2.1. Если = 0, то ставим НДС Вида операций и пользователь должен сам вручную указать тип НДС.
            $isourorg = $row1['f_isourorg'];
            if($row1['f_isourorg']==0) {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342
            elseif(($row1['f_isourorg']==1)&&(($bdrid==1)||($bdrid==2))) // 1.2.3. Если бюджет указан и Исполнитель наша организация
              {
              if(($bdrid==1)&&($sivozm==1)) {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];} // !!! то для Возмещаемых операций с Бюджет+ указываем НДС Вида операций.//sdid3342
              else // 1.3. Если исполнитель наше лицо, то смотрим по таблице veda_attrhist является ли данное лицо УСН с типом Без НДС. Для поиска нужно взять Дату создания операции.
                {
                $sql = "# НДС+СНО
                       select ath1.f_fldvalue as ndsid, ath2.f_fldvalue as snoid
                         from veda_attrhist ath1, veda_attrhist ath2
                        where ath1.f_objtype=26 and ath1.f_fldnum=44 and ath1.f_objid=$executor #ОРГАНИЗАЦИЯ 
                          and '$sidttmcr'>=ath1.f_validdate 
                          and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                             where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor)
                               OR
                               ((select min(f_validdate) from veda_attrhist 
                                  where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor) IS NULL)
                              )
                          and  ath2.f_objtype=26 and ath2.f_fldnum=36 and ath2.f_objid=$executor #ОРГАНИЗАЦИЯ 
                          and '$sidttmcr'>=ath2.f_validdate 
                          and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                              where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor)
                               OR
                               ((select min(f_validdate) from veda_attrhist 
                                  where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor) IS NULL)
                              )
                       ";
                $res2 = $dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC)) 
                  {
                  if($ourexecutor==1) {$retval=$row2['ndsid'];$isok=1;return [$retval,$bdrid];} //sdid 3548

                  if(($row2['snoid']==2)&&($row2['ndsid']==0)) {$retval=0;$isok=1;return [$retval,$bdrid];} // является ли данное лицо УСН с типом Без НДС. 1.3.1. Если является, то указываем в НДС «Без НДС».//sdid3342
                  else // 1.4. Иначе: Ищем НДС с 0%. Должны выполняться 2 условия
                    {  // 1.4.1. Первое – услуги оказываются по договору транспортному. Для этого смотрим, какой БДР в операции:
                       // Если БДР «Доходы», то определяем договор по операции. Если договор «Транспортный», 
                       // т.е. с подтипом «ТЭО исполнитель наше ЮЛ», «Транспортно-экспедиционное обслуживание» или «Пользование КТК», то условие выполнено. 
                       // Можем переходить к проверке второго условия на предмет экспорта-импорта ( $stage1_4_2=1). 
                    $stage1_4_2 = 0;
                    //$stage1_5   = 0;
                    $res1_4_1 = 0;
                    if($bdrid==1) //БДР - Доходы
                      {
                      //$stage1_4_2 = 0;
                      //$stage1_5   = 0;
                      if($dogid>0 && $parenttype>0)
                        {
                        //(select f_subtype from veda_dogs where f_id=$dogid) as dogsubtypeid
                        if($parenttype==2) {$sql = "select d.f_subtype as dogsubtypeid from veda_dogs d, veda_specs s where s.f_id=$dogid and d.f_id=s.f_dogid";}
                        elseif($parenttype==4) {$sql = "select d.f_subtype as dogsubtypeid from veda_dogs d where d.f_id=$dogid";}
                        }
                      else
                        {
                        $sql = "
                               select 
                                 case when si.f_parenttype=2 
                                        then (select d.f_subtype from veda_dogs d, veda_specs s where s.f_id=si.f_specid and d.f_id=s.f_dogid)
                                      when si.f_parenttype=4
                                        then (select d.f_subtype from veda_dogs d where d.f_id=si.f_specid)
                                      else 0
                                 end as dogsubtypeid
                               from veda_spec_invoices si where si.f_id=$specinvid
                               ";
                        }
                      $res3 = $dbh->query($sql);
                      if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                        {
                        $stid = $row3['dogsubtypeid'];
                        // Если подтип «ТЭО исполнитель наше ЮЛ», «Транспортно-экспедиционное обслуживание» или «Пользование КТК» // то условие выполнено... 
                        if(($stid==8)||($stid==1)||($stid==5)) {$stage1_4_2=1;$stage1_5=0;$isok=0;$res1_4_1=1;} // Если является, то условие выполнено и проверяем заявку на второе условие. Не выполнено, то в п.1.5.
                        else {$stage1_5=1;}
                        }
                      }
                    elseif(($bdrid==2)&&($isourorg==1)) {$stage1_4_2=1;$stage1_5=0;$isok=0;$res1_4_1=1;} // БДР - Расходы
                    else {$stage1_4_2=0;$stage1_5=1;$isok=0;}
                    // 1.4.2 Второе - Определяем признак Импорт/Экспорт: 
                    $stage1_4_3=0;
                    $stage1_4_2_2=0;
                    $res1_4_2=0;
                    if($stage1_4_2==1)
                      {
                      if($dogid>0 && $parenttype==2) {$sql = "select f_impexp as impexp from veda_specs where f_id=$dogid ";}
                      elseif($dopcontractid>0 && $parenttype==4) {$sql = "select f_impexp as impexp from veda_specs where f_id=$dopcontractid ";}
                      else {$sql = "select s.f_impexp as impexp from veda_spec_invoices si, veda_specs s where si.f_parenttype=2 and s.f_id=si.f_specid and si.f_id=$specinvid";}
                      $res4 = $dbh->query($sql);
                      if($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                        {
                        if(($row4['impexp']==1)||($row4['impexp']==2)) {$stage1_4_3=1; $stage1_4_2_2=0;$res1_4_2=1;}
                        else {$stage1_4_2_2=1;}
                        }
                      }
                    //error_log("\n\nres1_4_1=$res1_4_1 res1_4_2=$res1_4_2\n\n",0);
                    if($res1_4_1==1 && $res1_4_2==1) {$stage1_5=0;$retval=3;$isok=1;return [$retval,$bdrid];}//sdid3342
                    // 1.4.2.2.	Определяем экспорт/импорт по маршрутам связанных со спецификацией операции (в том числе и через парент спецификацию)
                    if(($stage1_4_2_2==1)&&($res1_4_2==0))
                      {
                      $p1country = 0;
                      $p2country = 0;
                      $rdogid = 0;
                      if($dogid>0 && $parenttype==2) {$rdogid=$dogid;}
                      elseif($dopcontractid>0 && $parenttype==4) {$rdogid=$dopcontractid;}
                      if($rdogid>0)
                        {
                        $sql = "
                               select r.f_p1country -- r.f_id, 
                                 from veda_routes r, veda_specs s
                                where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                  and s.f_id=$rdogid and r.f_parentid=0 and r.f_p1country>0
                               ";
                        }
                      else
                        {
                        $sql = "
                               select r.f_p1country -- r.f_id, 
                                 from veda_routes r, veda_specs s, veda_spec_invoices si
                                where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                  and s.f_id=si.f_specid and si.f_parenttype=2 and si.f_id=$specinvid and r.f_parentid=0 and r.f_p1country>0
                               ";
                        }
                      $res5 = $dbh->query($sql);
                      if($row5 = $res5->fetch(PDO::FETCH_ASSOC)) {$p1country=$row5['f_p1country'];}

                      if($rdogid>0)
                        {
                        $sql = "
                               select r.f_p2country -- r.f_id, r.f_parentid
                                 from veda_routes r, veda_specs s
                                where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                  and s.f_id=$rdogid and r.f_parentid>0 and r.f_p2country>0 
                               order by r.f_parentid desc                             
                               ";
                        }
                      else
                        {
                        $sql = "
                               select r.f_p2country -- r.f_id, r.f_parentid
                                 from veda_routes r, veda_specs s, veda_spec_invoices si
                                where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                  and s.f_id=si.f_specid and si.f_parenttype=2 and si.f_id=$specinvid and r.f_parentid>0 and r.f_p2country>0 
                               order by r.f_parentid desc                             
                               ";
                        }
                      $res6 = $dbh->query($sql);
                      if($row6 = $res6->fetch(PDO::FETCH_ASSOC)) {$p2country=$row6['f_p2country'];}

                      if( (($p1country==1)&&($p2country!=1))||(($p2country==1)&&($p1country!=1)) )
                        {
                        $res1_4_2=1;
                        if($res1_4_1==1) {$stage1_5=0;$retval=3;$isok=1;return [$retval,$bdrid];}//sdid3342
                        }
                      else 
                        {
                        if($dopcontractid>0 && $parenttype==4) {$stage1_5=1;}
                        else {$stage1_5=0;$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342
                        }
                      }
                    }
                  }
                }
              }
            //$retval=$row1['f_nds'];$isok=1;return $retval;
            }
          }////
        // 1.5.	В ином случае смотрим, какая ставка НДС у организации исполнителя на дату создания операции и возвращаем её (НДС по СНО). 
        if($stage1_5==1)
          {
          $sql = "# НДС+СНО
                 select ath1.f_fldvalue as ndsid, ath2.f_fldvalue as snoid
                   from veda_attrhist ath1, veda_attrhist ath2
                  where ath1.f_objtype=26 and ath1.f_fldnum=44 and ath1.f_objid=$executor #ОРГАНИЗАЦИЯ 
                    and '$sidttmcr'>=ath1.f_validdate 
                    and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                       where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor)
                         OR
                         ((select min(f_validdate) from veda_attrhist 
                            where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor) IS NULL)
                        )
                    and  ath2.f_objtype=26 and ath2.f_fldnum=36 and ath2.f_objid=$executor #ОРГАНИЗАЦИЯ 
                    and '$sidttmcr'>=ath2.f_validdate 
                    and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                        where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor)
                         OR
                         ((select min(f_validdate) from veda_attrhist 
                            where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor) IS NULL)
                        )
                 ";
          $res7 = $dbh->query($sql);
          if($row7 = $res7->fetch(PDO::FETCH_ASSOC)) {$retval=$row7['ndsid'];$isok=1;return [$retval,$bdrid];}//sdid3342
          }
        }
      }
    // Если specinvid не задан
    elseif($specinvid==0 && strlen($dt)>0 && $dogid>0 && (($parenttype==2)||($parenttype==4)))
    //elseif($specinvid==0 && strlen($dt)>0 && $dogid>0 && (($parenttype==2)||($parenttype==4 && $dopcontractid>0)))
    //elseif($specinvid==0 && strlen($dt)>0)
      {
      //echo "specinvid:$specinvid|dt:$dt|dogid:$dogid|parenttype:$parenttype<br>";
      $dsql = "";
      if($parenttype==2)
        {$dsql = " ,(select d.f_orgid from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=$dogid) as orgid,
                    (select d.f_contrid from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=$dogid) as contrid,
                    (select d.f_subtype from veda_dogs d, veda_specs s where d.f_id=s.f_dogid and s.f_id=$dogid) as dogsubtypeid,
                    (select f_impexp from veda_specs where f_id=$dogid) as impexp ";}
      elseif($parenttype==4) 
        {$dsql = " ,(select f_orgid from veda_dogs where f_id=$dogid) as orgid, 
                    (select f_contrid from veda_dogs where f_id=$dogid) as contrid,
                    (select f_subtype from veda_dogs where f_id=$dogid) as dogsubtypeid,
                    (select f_impexp from veda_specs where f_id=$dopcontractid) as impexp ";}

      $sql = "
             select t.f_isfinance,
                    (select s84.f_num
                       from veda_spr s84, veda_spr s85, veda_spr s86
                      where s84.f_type=84 and s85.f_type=85 and s86.f_type=86
                        and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$tbdrarticle
                    ) as bdr,
                    t.f_nds as tnds, t.f_vozm as tvozm
                    $dsql
             from veda_typeopers t where t.f_id=$idoper
             ";
      //error_log("\n\nsql = $sql\n\n",0);
      //echo "$sql<br>";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        //if($row['bdr']==1) {$executor=$row['orgid'];}
        //elseif($row['bdr']==2) {$executor=$row['contrid'];}
        if($row['bdr']==1) {if($orgid>0){$executor=$orgid;}else{$executor=$row['orgid'];}}
        elseif($row['bdr']==2) {if($contrid>0){$executor=$contrid;}else{$executor=$row['contrid'];}}
        else {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342

        if($ourexecutor==1) {if($orgid>0){$executor=$orgid;}else{$executor=$row['orgid'];}} //sdid 3548

        $bdrid = $row['bdr']; 
        if($invozm>0) {$tvozm = $invozm;} else {$tvozm = $row['tvozm'];}
        //$tvozm = $row['tvozm'];
        $tnds  = $row['tnds'];
        $sidttmcr = $dt;
        $isourorg = 0; // наша ли организация
        //!!!!!
        // Если 1.2.1.1.3. Бюджет: не указан, то исполнителя нет, тогда ставим в НДС Вида операций, чтобы пользователь сам указал ставку входящего НДСа
        //echo "executor:$executor|bdrid:$bdrid|idoper:$idoper|tnds:$tnds|<br>";
        if(($bdrid!=1)&&($bdrid!=2)&&($idoper>0)) {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342
        //!!!!!
        // 1.2.2. Смотрим, является ли исполнитель нашим лицом согласно veda_clients.f_isourorg
        $stage1_5 = 0;
        //error_log("\n\nexecutor=$executor\n\n",0);
        $sql="select f_isourorg from veda_clients where f_id=$executor";
        $res1 = $dbh->query($sql);
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC)) 
          { // 1.2.2.1. Если = 0, то ставим НДС Вида операций и пользователь должен сам вручную указать тип НДС.
          $isourorg = $row1['f_isourorg'];
          if($row1['f_isourorg']==0) 
            {
            $retval=$row['tnds'];$isok=1;
            //echo "isourorg:$isourorg|tnds:$tnds|$retval|<br>";
            return [$retval,$bdrid];//sdid3342
            }
          elseif(($row1['f_isourorg']==1)&&(($bdrid==1)||($bdrid==2))) // 1.2.3. Если бюджет указан и Исполнитель наша организация
            {
            //echo "bdrid:$bdrid|tvozm:$tvozm|<br>";
            if(($bdrid==1)&&($tvozm==1)) {$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];} // !!! то для Возмещаемых операций с Бюджет+ указываем НДС Вида операций.//sdid3342
            else // 1.3. Если исполнитель наше лицо, то смотрим по таблице veda_attrhist является ли данное лицо УСН с типом Без НДС. Для поиска нужно взять Дату создания операции.
              {
              $sql = "# НДС+СНО
                     select ath1.f_fldvalue as ndsid, ath2.f_fldvalue as snoid
                       from veda_attrhist ath1, veda_attrhist ath2
                      where ath1.f_objtype=26 and ath1.f_fldnum=44 and ath1.f_objid=$executor #ОРГАНИЗАЦИЯ 
                        and '$sidttmcr'>=ath1.f_validdate 
                        and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                           where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor)
                             OR
                             ((select min(f_validdate) from veda_attrhist 
                                where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor) IS NULL)
                            )
                        and  ath2.f_objtype=26 and ath2.f_fldnum=36 and ath2.f_objid=$executor #ОРГАНИЗАЦИЯ 
                        and '$sidttmcr'>=ath2.f_validdate 
                        and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                            where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor)
                             OR
                             ((select min(f_validdate) from veda_attrhist 
                                where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor) IS NULL)
                            )
                     ";
              //error_log("\n\nsql_NDS+SNO =  $sql\n\n",0);
              //echo "$sql<br>";
              $res2 = $dbh->query($sql);
              if($row2 = $res2->fetch(PDO::FETCH_ASSOC)) 
                {
                if($ourexecutor==1) {$retval=$row2['ndsid'];$isok=1;return [$retval,$bdrid];} //sdid 3548

                if(($row2['snoid']==2)&&($row2['ndsid']==0)) {$retval=0;$isok=1;return [$retval,$bdrid];} // является ли данное лицо УСН с типом Без НДС. 1.3.1. Если является, то указываем в НДС «Без НДС».//sdid3342
                else // 1.4. Иначе: Ищем НДС с 0%. Должны выполняться 2 условия
                  {  // 1.4.1. Первое – услуги оказываются по договору транспортному. Для этого смотрим, какой БДР в операции:
                     // Если БДР «Доходы», то определяем договор по операции. Если договор «Транспортный», 
                     // т.е. с подтипом «ТЭО исполнитель наше ЮЛ», «Транспортно-экспедиционное обслуживание» или «Пользование КТК», то условие выполнено. 
                     // Можем переходить к проверке второго условия на предмет экспорта-импорта ( $stage1_4_2=1). 
                  $stage1_4_2 = 0;
                  //$stage1_5   = 0;
                  $res1_4_1   = 0;
                  //echo "bdrid:$bdrid|isourorg:$isourorg<br>";
                  if($bdrid==1) //БДР - Доходы
                    {
                    $stid = $row['dogsubtypeid'];
                    // Если подтип «ТЭО исполнитель наше ЮЛ», «Транспортно-экспедиционное обслуживание» или «Пользование КТК» // то условие выполнено... 
                    if(($stid==8)||($stid==1)||($stid==5)) {$stage1_4_2=1;$stage1_5=0;$isok=0;$res1_4_1=1;} // Если является, то условие выполнено и проверяем заявку на второе условие. Не выполнено, то в п.1.5.
                    else {$stage1_5=1;}
                    }
                  elseif(($bdrid==2)&&($isourorg==1)) 
                    {$stage1_4_2=1;$stage1_5=0;$isok=0;$res1_4_1=1;} // БДР - Расходы
                  else {$stage1_4_2=0;$stage1_5=1;$isok=0;}
                  // 1.4.2 Второе - Определяем признак Импорт/Экспорт: 
                  $stage1_4_3=0;
                  $stage1_4_2_2=0;
                  $res1_4_2=0;
                  if($stage1_4_2==1)
                    {
                    //echo "impexp:".$row['impexp']."<br>";
                    if(($row['impexp']==1)||($row['impexp']==2)) 
                      {$stage1_4_3=1; $stage1_4_2_2=0;$res1_4_2=1;}
                    else {$stage1_4_2_2=1;}
                    }

                  if($res1_4_1==1 && $res1_4_2==1) {$stage1_5=0;$retval=3;$isok=1;return [$retval,$bdrid];}//sdid3342
                  // 1.4.2.2.	Определяем экспорт/импорт по маршрутам связанных со спецификацией операции (в том числе и через парент спецификацию)
                  //echo "stage1_4_2_2:$stage1_4_2_2|res1_4_2:$res1_4_2<br>";
                  if(($stage1_4_2_2==1)&&($res1_4_2==0))
                    {
                    $p1country = 0;
                    $p2country = 0;
                    $rdogid = 0;
                    if($parenttype==2) {$rdogid=$dogid;}
                    elseif($parenttype==4) {$rdogid=$dopcontractid;}
                    //echo "rdogid:$rdogid<br>";
                    if(strlen($rdogid)>0 && $rdogid>0)
                      {
                      $sql = "
                             select r.f_p1country -- r.f_id, 
                               from veda_routes r, veda_specs s
                              where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                and s.f_id=$rdogid and r.f_parentid=0 and r.f_p1country>0
                             ";
                      $res5 = $dbh->query($sql);
                      if($row5 = $res5->fetch(PDO::FETCH_ASSOC)) {$p1country=$row5['f_p1country'];}
                     
                      $sql = "
                             select r.f_p2country -- r.f_id, r.f_parentid
                               from veda_routes r, veda_specs s
                              where (r.f_postid=s.f_postid or r.f_postid=(select f_postid from veda_specs where f_id=s.f_parentspecid and s.f_parentspecid>0))
                                and s.f_id=$rdogid and r.f_parentid>0 and r.f_p2country>0 
                             order by r.f_parentid desc                             
                             ";
                      $res6 = $dbh->query($sql);
                      if($row6 = $res6->fetch(PDO::FETCH_ASSOC)) {$p2country=$row6['f_p2country'];}
                      }
                    //echo "p1country:$p1country|p2country:$p2country|<br>";
                    if( (($p1country==1)&&($p2country!=1))||(($p2country==1)&&($p1country!=1)) )
                      {
                      $res1_4_2=1;
                      if($res1_4_1==1) {$stage1_5=0;$retval=3;$isok=1;return [$retval,$bdrid];}//sdid3342
                      }
                    else 
                      {
                      if(strlen($rdogid)==0 || $rdogid==0) {$stage1_5=1;}
                      else {$stage1_5=0;$retval=$row['tnds'];$isok=1;return [$retval,$bdrid];}//sdid3342
                      }
                    //echo "res1_4_1:$res1_4_1<br>";
                    }
                  }
                }
              }
            }
          }//
        // 1.5.	В ином случае смотрим, какая ставка НДС у организации исполнителя на дату создания операции и возвращаем её (НДС по СНО). 
        //error_log("\n\nstage1_5 = $stage1_5\n\n",0);
        if($stage1_5==1)
          {
          $sql = "# НДС+СНО
                 select ath1.f_fldvalue as ndsid, ath2.f_fldvalue as snoid
                   from veda_attrhist ath1, veda_attrhist ath2
                  where ath1.f_objtype=26 and ath1.f_fldnum=44 and ath1.f_objid=$executor #ОРГАНИЗАЦИЯ 
                    and '$sidttmcr'>=ath1.f_validdate 
                    and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                       where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor)
                         OR
                         ((select min(f_validdate) from veda_attrhist 
                            where f_validdate>ath1.f_validdate and f_objtype=26 and f_fldnum=44 and f_objid=$executor) IS NULL)
                        )
                    and  ath2.f_objtype=26 and ath2.f_fldnum=36 and ath2.f_objid=$executor #ОРГАНИЗАЦИЯ 
                    and '$sidttmcr'>=ath2.f_validdate 
                    and ('$sidttmcr'<(select min(f_validdate) from veda_attrhist 
                                        where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor)
                         OR
                         ((select min(f_validdate) from veda_attrhist 
                            where f_validdate>ath2.f_validdate and f_objtype=26 and f_fldnum=36 and f_objid=$executor) IS NULL)
                        )
                 ";
          $res7 = $dbh->query($sql);
          if($row7 = $res7->fetch(PDO::FETCH_ASSOC)) {$retval=$row7['ndsid'];$isok=1;return [$retval,$bdrid];}//sdid3342
          }
        }
      }
    //} if(strlen($dt)>0)
  return [$retval,$bdrid];//sdid3342
  }
//~sdid 3010

//sdid2685
function inClosedBuhPeriod($dt)
  {
  $retval = 0;
  $dbh = dbconnect();
  $sql="select case when (select max(vs.f_dtbuhcls) from veda_settings vs where vs.f_settype=1)>'$dt' then 1 else 0 end incls";
  $res = $dbh->query($sql);
  if($row = $res->fetch(PDO::FETCH_ASSOC))
    {$retval = $row['incls'];}
  return $retval;
  }
//~sdid2685

//sdid 1772
  function setExceptionLogToBD($module,$point,$msg,$dttmcr,$usrid)
    {
    $dbh = dbconnect();
    $msg = str_replace("'","\'",$msg);
    $sql = "insert into ".DBPref."exlogs (f_modulename ,f_modulepoint, f_message, f_userid, f_dtcreate)".
           "                      values ('".$module."','".$point."' ,'".$msg."',".$usrid.",'".$dttmcr."')";
    $dbh->exec($sql);
    }
//~sdid 1772

//sdid 524
// Проверка на запрет создания финансового документа по операции
  function isAllowCreateFD($operid)
    {
    $dbh = dbconnect();
    $val=0;
/*    $sql = "select (si.f_outbuhperiod +
            (select count(*) from veda_akts a where a.f_operid=si.f_id) + 
            (select count(*) from veda_akts_details_opers ad where ad.f_operid=si.f_id) +
            (select count(*) from veda_specs s where s.f_id=si.f_specid and s.f_status in (6,8))) s 
            from veda_spec_invoices si where si.f_id=".$operid;
*/
    $sql = "select (t1+t2+t3) s from 
                   (select 
                    CASE si.f_outbuhperiod 
                      WHEN 0 THEN 1 ELSE 0 END t1,
                    CASE 
                      WHEN 
                        ((select count(*) from veda_akts a where a.f_operid=si.f_id) + 
                        (select count(*) from veda_akts_details_opers ad where ad.f_operid=si.f_id)) = 0 
                      THEN 0 ELSE 1 END t2,
                    CASE 
                      #WHEN (select count(*) from veda_specs s where s.f_id=si.f_specid and s.f_status in (6,8)) >0
                      WHEN (select count(*) from veda_specs s where s.f_id=si.f_specid and s.f_status=6 and si.f_parenttype=2) >0
                      THEN 1 ELSE 0 END t3 
           from veda_spec_invoices si where si.f_id=".$operid.") k";
    //echo $sql."<br>";
    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      { $val = $row['s']; }

    if($val==3) {return 0;} // запрет
    // sdid 1314
    //else {return 1;}       // разрешение
    else
      {
      $sql = "SELECT COUNT(*) ctgs FROM ".DBPref."categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $operid;
      $conn = $dbh->query($sql);
      if ($row = $conn->fetch(PDO::FETCH_ASSOC))
        {
        if ($row['ctgs'] > 0)
          {return -1;}// запрет "Учесть как расход ОВЭД" или "Учесть как расход ОЛ"
        else
          {return 1;} // разрешение
        }
      }
    // ~ sdid 1314
    }
//~sdid 524
//sdid 1785
function getCheckMasPayOperIncl($maspayid,$operid)
  {
  // Отдаём json строку, принмаем 
  // maspayid --- id обобщенного платежа
  // operid   --- id операции
  if(($maspayid>0)&&($operid>0))
    {
    try
      {
      //подключаемся к базе
      $dbh = dbconnect();
      //получаем данные из базы
      $sql = "select mp.f_acchistid from veda_maspayfe mp where mp.f_id=$maspayid";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        if($row['f_acchistid']>0)
          {
          $msg = "К выбранному обобщенному платежу привязано платежное поручение.\nВ случае продолжения изменения НЕ будут применены";
          $a = array(false,$msg);
          return json_encode($a);
          }
        }
      $sql = "select mpo.f_maspayfeid, mp.f_acchistid 
                from veda_maspayfe_opers mpo, veda_maspayfe mp 
               where mp.f_id=mpo.f_maspayfeid and mpo.f_operid=$operid";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        if($row['f_maspayfeid']==$maspayid)
          {
          $msg = "Операция уже включена в этот обобщенный платеж. В случае продолжения запись НЕ будет обновлена.";
          $a = array(false,$msg);
          }
        else
          {
          if($row['f_acchistid']>0)
            {
            $msg = "Операция уже включена в другой обобщенный платеж (ИД ".$row['f_maspayfeid'].") со связанным платежным поручением.\nВ случае продолжения изменения НЕ будут применены.";
            $a = array(false,$msg);
            }
          else
            {
            $msg = "Операция уже включена в другой обобщенный платеж (ИД ".$row['f_maspayfeid'].").\nВ случае продолжения операция будет исключена из текущего обобщенного платежа\nи добавлена в выбранный платеж.";
            $a = array(false,$msg);
            }
          }  
        }
      else
        {
        $msg = "Ok";
        $a = array(true,$msg);
        } 
      return json_encode($a);
      }
    catch (PDOException $e)
      {return json_encode(array(false,"Database error: ".$e->getMessage()));}
    }
  else
    {return json_encode(array(false,"Не заданы параметры"));}
  }
//~sdid 1785

//sdid 706
// ФУНКЦИЯ ДЛЯ ОТДАЧИ ТАРИФОВ Отдела Логистики (ОЛ)
function getOLTarifsVal($routeid,$contrid)
  {
// Отдаём json строку, принмаем 
// routeid --- id маршрута
// contrid --- id первозчика
  if(($routeid>0)&&($contrid>0))
    {
    try
      {
        $response ="";
        //подключаемся к базе
        $dbh = dbconnect();
        //получаем данные из базы
        $sql = "SELECT 
                 get_tarifsolsum($contrid,$routeid,tolb.f_type) fsum,
                 '643' fval,
                 ifnull((select t.f_operid 
                           from veda_tarifsol t, veda_routes r 
		          where t.f_type=tolb.f_type 
			    and t.f_contrid=$contrid
                            and r.f_id=$routeid
                            #and t.f_p1location=r.f_p1location
                            #and t.f_p2location=r.f_p2location
                            #and NOW() between t.f_dtstart and t.f_dtend
		            and t.f_ktksize in (select distinct k.f_typesize from veda_routes_ktk rk, veda_ktk k where rk.f_routeid=$routeid and k.f_id=rk.f_ktkid)
			  order by t.f_operid limit 1
                        ),0) foperid,
                 ifnull((select t.f_nds 
                           from veda_tarifsol t, veda_routes r, veda_specs s, veda_dogs d, veda_clients o 
		          where t.f_type=tolb.f_type 
			    and t.f_contrid=$contrid
			    and r.f_id=$routeid
			    and s.f_postid=r.f_postid 
		            and d.f_id=s.f_dogid 
		            and o.f_id=d.f_orgid
                            and t.f_nds=(CASE o.f_sno WHEN 1 THEN 2 WHEN 2 THEN 0 ELSE 2 END)
                            #and t.f_p1location=r.f_p1location
                            #and t.f_p2location=r.f_p2location
                            #and NOW() between t.f_dtstart and t.f_dtend
                        ),0) fnds,
                 ifnull((select f_name from veda_spr where f_type=161 and f_num=tolb.f_type), '') typename,
                 tolb.f_type
                FROM veda_tarifsol tolb
                WHERE tolb.f_isbase=1";
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      $i=0;
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $response->rows[$i]=array(
                "f_sum"            => $row['fsum'],
                "f_val"            => $row['fval'],
                "f_operid"         => $row['foperid'],
                "f_nds"            => $row['fnds'],
                "f_type"           => $row['f_type'],
                "typename"         => $row['typename']
                                 );
        $i++;
        }
      if($i>0)
        {return json_encode($response);}
      else
        {return "[false,\"Данные не найдены\"]";}
      }
    catch (PDOException $e)
      {return "[false,\"Database error: ".$e->getMessage()."\"]";}
    }
  else
    {return "[false,\"Не заданы параметры\"]";}
  }
//~sdid 706

//создать ктк без номера
function createKTKBN($ktktype,$ktknum)
  {
  $nktknum = "";
  $retval  = 0;
  $dbh = dbconnect();
  if(strlen($ktknum)>3){$nktknum=$ktknum;}
  else
    {
    $sql = "select concat('ktkbn',IFNULL(max(cast(SUBSTR(k.f_num,6) as UNSIGNED)),0)+1) nktknum 
            from ".DBPref."ktk k where k.f_num like 'ktkbn%'";
    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {$nktknum=$row['nktknum'];}
    }
  if(strlen($nktknum)>3)
    {
    $ki = Array('curtbl'=>103,'f_num'=>$nktknum,'f_typesize'=>$ktktype,'f_status'=>20,'f_mlid'=>1373);
    $ar = json_decode(addRowTbl($ki), true);
    if($ar[0]=="true")
      {$retval=$ar[2];}
    }
  return $retval;
  }

//определить подразделение 1С для организации
function getOPorgFrom1C($orgid,$jstr)
  {
  $OP="";
  $orgdivnum=0;
  if(strlen($jstr)>0)
    {
    $opars = json_decode($jstr,true);
    if(isset($opars['orgdivnum']))
      {$orgdivnum = $opars['orgdivnum'];}
    }
  if(($orgdivnum>0)&&($orgid>0))
    {
    $dbh = dbconnect();
    $sql = "select f_name from ".DBPref."spr where f_type=143 and f_num=".$orgdivnum." and f_uslint=".$orgid;
    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $OP = $row['f_name'];
      }
    }
  if(strlen($OP)==0)
    {
    $OP="ОП";
    if(($orgid==15))
      {$OP="ОП Нск";}
    elseif($orgid==30)
      {$OP="ОП ВК";}
    elseif($orgid==16)
      {$OP="ОП на Маркса";}
    elseif(($orgid==31)||($orgid==32)||($orgid==29)||($orgid==34))
      {$OP="Основное";}
    }
  return $OP;
  }

//проверить права пользователя на таблицу
function checkRightsTblForUsr($login,$tbl,$right)
  {
  $retval = 0;
  $rs = " and r.f_add=1 ";
  if($right==1)
    {$rs=" and r.f_add=1 ";}
  elseif($right==2)
    {$rs=" and r.f_edit=1 ";}
  elseif($right==3)
    {$rs=" and r.f_del=1 ";}
  elseif($right==4)
    {$rs=" and r.f_view=1 ";}
  $dbh    = dbconnect();
  $sql = "select count(*) cnt from ".DBPref."rights_tbl r where r.f_table=".$tbl." ".$rs." and 
            ((r.f_usrtype=2 and r.f_usrgrp in (select f_grpid from ".DBPref."usr_grp where f_usrid=".$login.")) or 
            (r.f_usrtype=1 and r.f_usrgrp=".$login.") or (r.f_usrtype=0 and r.f_usrgrp=0))";
  //echo $sql."<br>";
  $res = $dbh->query($sql);
  if($row = $res->fetch(PDO::FETCH_ASSOC))
    {
    if($row['cnt']>0)
      {$retval=1;}
    }
  return $retval;
  }

//получить описание
function getDescr($tblid,$fid)
{
    $retval = "";
    if(($tblid>0)&&($fid>0))
    {
        try
        {
            $dbh = dbconnect();
            //$sql = "select f_tablename from ".DBPref."menu_all where f_id=".$tblid;
            //$res = $dbh->query($sql);
            //if($row = $res->fetch(PDO::FETCH_ASSOC))
            //{
            $sql = "select f_descr from ".DBPref."interfaces where menuid=".$tblid." and fldnum=".$fid;
            $res = $dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
            {$retval=strip_tags($row['f_descr']);}
            //{$retval=str_replace("\n","\u000d<br />",$row['f_descr']);}
            //}
        }
        catch (Exception $e)
        {return "";}
    }
    return $retval;
}

//удалить историю просмотров
function clearHistView()
{
    if($_SESSION['loginid'])
    {
        $dbh = dbconnect();
        $sql = "delete from ".DBPref."histview where f_userid=".$_SESSION['loginid'];
        $dbh->exec($sql);
    }
}

//собрать выпадающую кнопку по таблице
function getTblRecMenu($tblid, $opars,$menuicon="")
  {
  //echo "1<br>";
  $retval = "";
  try
    {
    if($tblid>0)
      {
      $lmenuicon = "nav-icon fas fa-ellipsis-h";
      //sdid 1274
      if(strlen($menuicon)>0){$lmenuicon = $menuicon;}
      //sdid1199
      //$retval = "<div style=\"position:absolute\"><div style=\"position:relative\"><div class=\"nav\" style=\"vertical-align:top; \">
      //             <div class=\"nav-item dropdown btn\" style=\"top:-25px;\">
      //               <!-- <a id=\"dropdownSubMenu1_@f_id\" href=\"#\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"nav-link img-circle elevation-2\"> -->
      //               <!-- <a id=\"dropdownSubMenu1_@f_id\" href=\"#\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"nav-link img-circle\"> -->
      //               <!-- sdid 1471 -->
      //               <!-- <a id=\"dropdownSubMenu1_@f_id\" href=\"#\" onclick=\"popupMenuClick(@f_id); return false;\" class=\"nav-link img-circle\"> -->
      //               <a id=\"dropdownSubMenu3_@f_id\" href=\"#\" onclick=\"popupMenuClick(3, @f_id); return false;\" class=\"nav-link img-circle\">
      //               <!-- ~ sdid 1471 -->
      //               <!-- <button type=\"button\" class=\"img-circle elevation-2 btn btn-default btn-xs\"> -->
      //               <div class=\"delivery__icon-wrap delivery__icon-wrap d-flex align-items-center justify-content-center\"><i class=\"".$lmenuicon."\"></i></div>
      //               <!-- </button> -->
      //               </a>
      //               <div id=\"\" class=\"ydropdown\" style=\"position: fixed;display: block;z-index:901;\">
      //               <!-- <div> -->
      //               <!-- <ul aria-labelledby=\"dropdownSubMenu1_@f_id\" class=\"dropdown-menu border-0 shadow\" style=\"max-width: 1200px;position:fixed;z-index:901;\"> -->
      //               <!-- sdid 1471 -->
      //               <!-- <ul id=\"uldropdownSubMenu1_@f_id\" class=\"dropdown-menu border-0 shadow\" style=\"max-width: 1200px;position:absolute;z-index:901;\" > -->
      //               <ul id=\"uldropdownSubMenu3_@f_id\" class=\"dropdown-menu border-0 shadow\" style=\"max-width: 1200px;position:absolute;z-index:901;\" >
      //               <!-- ~ sdid 1471 -->
      //          ";
      $retval = "<div style=\"position:absolute\"><div style=\"position:relative\"><div class=\"nav\" style=\"vertical-align:top; \">
                   <div class=\"nav-item dropdown btn\" style=\"top:-25px;\">
                     <a id=\"dropdownSubMenu3_@f_id\" href=\"#\" onclick=\"popupMenuClick(3, @f_id); return false;\" class=\"nav-link img-circle\">
                     <div class=\"delivery__icon-wrap delivery__icon-wrap d-flex align-items-center justify-content-center\"><i class=\"".$lmenuicon."\"></i></div>
                     </a>
                     <div id=\"\" class=\"ydropdown\" style=\"position: fixed;display: block;z-index:901;\">
                     <ul id=\"uldropdownSubMenu3_@f_id\" class=\"dropdown-menu border-0 shadow\" style=\"max-width: 1200px;position:absolute;z-index:901;\" >
                ";
      //~sdid1199
      $usl = "";
      $opars = json_decode($opars,true);
      if(isset($opars['usl']))
        {$usl = $opars['usl'];}
      $cntsecondlevel = 0;
      $cntthirdlevel  = 0;
      $dbh = dbconnect();
      $sql = "select rb.*,ma.f_checkrights,ifnull((select count(*) from ".DBPref."tblrecbtn where f_menulevel=2 and f_menuid=".$tblid."),0) cntsecondlevel,
                ifnull((select count(*) from ".DBPref."tblrecbtn where f_menulevel=3 and f_menuid=".$tblid."),0) cntthirdlevel
                ,ifnull((select count(*) from ".DBPref."tblrecbtn where f_menulevel=4 and f_menuid=".$tblid."),0) cnt4level
                ,ma.f_wfieldshistory
              from ".DBPref."tblrecbtn rb,".DBPref."menu_all ma 
              where ma.f_id=rb.f_menuid and rb.f_menuid=".$tblid." and rb.f_usl in (".$usl.") and rb.f_menulevel=1
                order by rb.f_menulevel,rb.f_menunum";
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        //sdid 1386
        $retval = $retval."<li><a href=\"#\" onclick=\"".$row['f_onclick']."\" id=\"".$row['f_btnid']."\" class=\"dropdown-item\">".$row['f_title']."</a></li>";
        //~sdid 1386
        //$retval = $retval."<li><a href=\"#\" onclick=\"".$row['f_onclick']."\" class=\"\">".$row['f_title']."</a></li>";
        $cntsecondlevel = $row['cntsecondlevel'];
        $cntthirdlevel  = $row['cntthirdlevel'];
        $cnt4level      = $row['cnt4level'];
        }
      if($cntsecondlevel>0)
        {
        //$retval = $retval."
        //               <li class=\"dropdown-divider\"></li>
        //               <li class=\"dropdown-submenu dropdown-hover\">
        //               <!-- sdid 1471 -->
        //               <!--  <a id=\"dropdownSubMenu2_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Другое</a> -->
        //               <!--  <ul aria-labelledby=\"dropdownSubMenu2_@f_id\" class=\"dropdown-menu border-0 shadow\"> -->
        //               <a id=\"dropdownSubMenu4_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Другое</a>
        //               <ul aria-labelledby=\"dropdownSubMenu4_@f_id\" class=\"dropdown-menu border-0 shadow\">
        //               <!-- ~ sdid 1471 -->
        //                  ";
        $retval = $retval."
                       <li class=\"dropdown-divider\"></li>
                       <li class=\"dropdown-submenu dropdown-hover\">
                       <br><a id=\"dropdownSubMenu4_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Другое</a><br>
                       <ul aria-labelledby=\"dropdownSubMenu4_@f_id\" class=\"dropdown-menu border-0 shadow\">
                          ";
        $sql = "select rb.*,ma.f_checkrights 
                from ".DBPref."tblrecbtn rb,".DBPref."menu_all ma 
                where ma.f_id=rb.f_menuid and rb.f_menuid=".$tblid." and rb.f_usl in (".$usl.") and rb.f_menulevel=2
                  order by rb.f_menulevel,rb.f_menunum";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $retval = $retval."<li><a href=\"#\" onclick=\"".$row['f_onclick']."\" class=\"dropdown-item\">".$row['f_title']."</a></li>";
          }
        $retval = $retval."
                         </ul>
                       </li>
                          ";
        }
      if($cntthirdlevel>0)
        {
        //$retval = $retval."
        //               <li class=\"dropdown-divider\"></li>
        //               <li class=\"dropdown-submenu dropdown-hover\">
        //               <!-- sdid 1471 -->
        //               <!--  <a id=\"dropdownSubMenu3_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Прочее</a> -->
        //               <!--  <ul aria-labelledby=\"dropdownSubMenu3_@f_id\" class=\"dropdown-menu border-0 shadow\"> -->
        //               <a id=\"dropdownSubMenu5_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Прочее</a>
        //               <ul aria-labelledby=\"dropdownSubMenu5_@f_id\" class=\"dropdown-menu border-0 shadow\">
        //               <!-- ~ sdid 1471 -->
        //                  ";
        $retval = $retval."
                       <li class=\"dropdown-divider\"></li>
                       <li class=\"dropdown-submenu dropdown-hover\">
                       <br><a id=\"dropdownSubMenu5_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">Прочее</a><br>
                       <ul aria-labelledby=\"dropdownSubMenu5_@f_id\" class=\"dropdown-menu border-0 shadow\">
                          ";
        $sql = "select rb.*,ma.f_checkrights 
                from ".DBPref."tblrecbtn rb,".DBPref."menu_all ma 
                where ma.f_id=rb.f_menuid and rb.f_menuid=".$tblid." and rb.f_usl in (".$usl.") and rb.f_menulevel=3
                  order by rb.f_menulevel,rb.f_menunum";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $retval = $retval."<li><a href=\"#\" onclick=\"".$row['f_onclick']."\" class=\"dropdown-item\">".$row['f_title']."</a></li>";
          }
        $retval = $retval."
                         </ul>
                       </li>
                          ";
        }
     if($cnt4level>0)
        {
        $retval = $retval."
                       <li class=\"dropdown-divider\"></li>
                       <li class=\"dropdown-submenu dropdown-hover\">
                       <a id=\"dropdownSubMenu6_@f_id\" href=\"#\" role=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" class=\"dropdown-item dropdown-toggle\">История атрибутов</a>
                       <ul aria-labelledby=\"dropdownSubMenu6_@f_id\" class=\"dropdown-menu border-0 shadow\">
                          ";
        $sql = "select rb.*,ma.f_checkrights 
                from ".DBPref."tblrecbtn rb,".DBPref."menu_all ma 
                where ma.f_id=rb.f_menuid and rb.f_menuid=".$tblid." and rb.f_usl in (".$usl.") and rb.f_menulevel=4
                  order by rb.f_menulevel,rb.f_menunum";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $retval = $retval."<li><a href=\"#\" onclick=\"".$row['f_onclick']."\" class=\"dropdown-item\">".$row['f_title']."</a></li>";
          }
        $retval = $retval."
                         </ul>
                       </li>
                          ";
        }
      $retval = $retval."</ul></div>
                   </div>
                 </div></div></div>";
      //~sdid 1274
      }
    return $retval;
    }
  catch (Exception $e)
    {return "";}
  }

//собрать строку кнопок по таблице
function getTblRecBtns($tblid, $opars)
  {
  //echo "1<br>";
  $retval = "";
  try
    {
    if($tblid>0)
      {
      $usl = "";
      //echo $opars."<br>";
      $opars = json_decode($opars,true);
      if(isset($opars['usl']))
        {$usl = $opars['usl'];}
      $nstr = 0;$cb=0;
      $dbh = dbconnect();
      $sql = "select rb.*,ma.f_checkrights,ifnull(rrb.f_wfldrights,1) recbtnright 
              from (".DBPref."tblrecbtn rb,".DBPref."menu_all ma) 
              left join ".DBPref."rights_recbtn rrb on rrb.f_tblrecbtnid=rb.f_id 
                and ((rrb.f_usrtype=2 and rrb.f_usrgrp in (select f_grpid from ".DBPref."usr_grp where f_usrid=".$_SESSION['loginid'].")) or 
                     (rrb.f_usrtype=1 and rrb.f_usrgrp=".$_SESSION['loginid']."))
              where ma.f_id=rb.f_menuid and rb.f_menuid=".$tblid." and rb.f_usl in (".$usl.") order by rb.f_strnum,rb.f_num";
      //echo $sql."<br>";
      //if(strlen($usl)>2)
      //{echo $sql."<br>";}
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        //echo $row['recbtnright']."<br>";
        if($row['recbtnright']==1)
          {
          $needhref = 1;
          if($nstr!=$row['f_strnum'])
            {
            if($nstr==0)
              {$retval = $retval."<div class=\"modal-body\"><div class=\"row\">";}
            else
              {$retval = $retval."</div><br><div class=\"row\">";}
            $nstr = $row['f_strnum'];
            }
          //echo $row['f_onclick']."<br>";
          $divf = "";
          $divl = "";
          $ldiv = "";
          $attr_id = "";
          //echo $row['f_title']."<br>";
          if(strcmp($row['f_title'], "Сделать копию")==0)
            {
            $attr_id = "id=\"btnplusadd@f_id\"";
            }
          elseif(strlen($row['f_btnid'])>0)
            {
            if(substr($row['f_btnid'],0,7)=="dragref")
		            {
              $divf    = "<div draggable=\"true\" ondragstart=\"drag(event)\" id=\"d".$row['f_btnid']."\">";
              $ldiv    = getSpanStrOpr($row['f_SpanStr']);
              $divl    = "</div>";
              $needhref = 0;
              }
            else
              {
              $attr_id = "id=\"".$row['f_btnid']."\"";
              }
            }
          //echo "needhref: $needhref<br>";
          if($needhref==1)
            {
            //echo "divf:$divf<br>";
            //echo "divl:$divl<br>";
            //echo "ldiv:$ldiv<br>";
            //echo "attr_id:$attr_id<br>";
            //echo "SpanStr:".$row['f_SpanStr']."<br>";
            //echo "SpanStr:".getSpanStrOpr($row['f_SpanStr'])."<br>";
            $retval = $retval.$divf."<a href='#' ".$attr_id." title='".$row['f_title']."' onclick=".$row['f_onclick'].">".
              getSpanStrOpr($row['f_SpanStr'])."</a>".$divl."&nbsp;";}
            //echo "retval:$retval<br>";
          else
            {$retval = $retval.$divf.$ldiv.$divl."&nbsp;";}
          $cb++;
          }
        }
      if($cb>0)
        {$retval = $retval."</div></div>";}
      }
    //echo "$retval<br>";
    return $retval;
    }
  catch (Exception $e)
    {return "";}
  }

//пернести спецфикацию в закрытые в 1С
function clsSpec($specid)
{
    $retval = "Ошибка не определена";
    try
    {
        $dbh = dbconnect();
        $sql = "select s.f_kod1cb,d.f_orgid,c.f_typecode, ".
            "  (select f_typecode from ".DBPref."client_codes where f_contrid=d.f_orgid) nbd1c ".
            "from ".DBPref."specs s,".DBPref."dogs d,veda_client_codes c ".
            "where d.f_id=s.f_dogid and c.f_contrid=d.f_orgid and s.f_id=".$specid;
        //echo $sql."<br>";
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            if((strlen($row['f_kod1cb'])>3)&&($row['f_typecode']>0))
            {
                //echo $row['f_kod1cb']."_".$row['f_typecode']."<br>";
                $rretval = c1c_clsSpec($row['f_kod1cb'],$row['nbd1c'],"");
                if($rretval['retval']=="true")
                {
                    $retval = "Перенесли в закрытые";
                }
                else
                {
                    $retval = "Ошибка переноса в закрытые: ".$rretval['msg'];
                }
            }
            else
            {$retval = "Ошибка определения кодов спецификации";}
        }
        else
        {$retval = "Ошибка нахождения данных о спецификации";}
        return $retval;
    }
    catch (Exception $e)
    {return "Ошибка: ".$e->getMessage()."";}
}

//экспортировать финансовый документ в 1С
function expFinDocTo1C($tdoc,$docid)
  {
  $retval = "Ошибка не определена";
  try
    {
    $retv = 0;
    //echo $docid."_".$tdoc."<br>";
    if(($docid>0)&&($tdoc>0))
      {
      $dbh = dbconnect();
      if($tdoc==1)//экспортируем Счет покупателю
        {
        $ndo = 0;
        $sql = "select ".
            "  case ".
            "    when i.f_dogtype=2 then (select f_kod1cb from ".DBPref."specs where f_id=i.f_dogid) ".
            "    when i.f_dogtype=4 then (select f_kod1c from ".DBPref."dogs where f_id=i.f_dogid) ".
            "  end kod1c,i.f_dogid ".
            "from ".DBPref."schets i where i.f_id=".$docid;
        //if($_SESSION['loginid']==2)
        //  {echo $sql;}
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          if(strlen($row['kod1c'])<3)
            {$ndo=1;}
          }
        if($ndo==0)
          {
          $sql = "select i.f_dogtype,i.f_dogid,i.f_id,i.f_sum,i.f_val,i.f_dt,i.f_srok,i.f_nds,i.f_maininv,i.f_ismaininv,i.f_status,i.f_grnd,i.f_com,i.f_num, ".
              "  i.f_orgid,i.f_contrid,i.f_kod1c,".
              "  (select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) nbd1c,".
              "  (select f_kod1c from ".DBPref."bank_accounts where f_id=i.f_obaccid) obaccid, ".
              "  (select f_name from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid)) top, ".
              "  case ".
              "    when i.f_dogtype=4 then ".
              "      (select f_kod1c from ".DBPref."dogs where f_id=i.f_dogid)  ".
              "    when i.f_dogtype=2 then ".
              "      case when i.f_vozm=1 then ".
              "        (select f_kod1cp from ".DBPref."specs where f_id=i.f_dogid) else ".
              "        (select f_kod1cb from ".DBPref."specs where f_id=i.f_dogid) end ".
              "  end dogid, ".
              //"  (select f_kod1c from ".DBPref."nomenk where f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) and f_nbd1c=(select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) nomenk ".
              "  (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) and f_nbd1c=(select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) limit 1) nomenk ".
              "from ".DBPref."schets i where i.f_id=".$docid;
          //if($_SESSION['loginid']==2)
          //  {echo $sql;}
          $res = $dbh->query($sql);
          if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
            $sorgdiv = "";
            $orgid   = getClntInf(2,2,$row['f_orgid'],1,$row['f_orgid'],2);
            $contrid = getClntInf(2,2,$row['f_contrid'],0,$row['f_orgid'],2);
            if($row['f_maininv']==0)
              {
              $nomenk = "[{\"код\":\"".$row['nomenk']."\",\"всум\":\"".$row['f_sum']."\",\"восн\":\"".str_replace("\"","",$row['top']." ".$row['f_com'])."\",\"вндс\":\"".$row['f_nds']."\"}]";
              if($row['f_ismaininv']>0)
                {
                $nomenk = "";
                $sql1 = "select i.f_sum,i.f_grnd,i.f_com,i.f_num,i.f_nds,".
                    "  (select f_name from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid)) top, ".
                    //"  (select f_kod1c from ".DBPref."nomenk where f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) nomenk ".
                    "  (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) and f_nbd1c=".$row['nbd1c']." limit 1) nomenk ".
                    "from ".DBPref."schets i where i.f_maininv=".$row['f_id'];
                $res1 = $dbh->query($sql1);
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if(strlen($nomenk)>0)
                    {$nomenk=$nomenk.",";}
                  $nomenk=$nomenk."{\"код\":\"".$row1['nomenk']."\",\"всум\":\"".$row1['f_sum']."\",\"восн\":\"".str_replace("\"","",$row1['top']." ".$row1['f_com'])."\",\"вндс\":\"".$row1['f_nds']."\"}";
                  }
                $nomenk = "[".$nomenk."]";
                }
              $dopstr  = "";
              $dground = "";
              if(($row['f_dogtype']==2)&&($row['f_dogid']>0))
                {
                $sql1 = "select s.f_num,s.f_dt,s.f_postid,d.f_dogname,d.f_dogdate,ifnull(s.f_orgdivision,0) f_orgdivision,
                         (select f_name from ".DBPref."spr where f_type=33 and f_num=s.f_typez) typez 
                         from ".DBPref."specs s,".DBPref."dogs d where d.f_id=s.f_dogid and s.f_id=".$row['f_dogid'];
                $res1 = $dbh->query($sql1);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row1['f_orgdivision']>0){$sorgdiv = "{\"orgdivnum\":\"".$row1['f_orgdivision']."\"}";}
                  //$sql2 = "select distinct(k.f_num) f_num,k.f_id from veda_ktk k,veda_ktk_hist h where k.f_id=h.f_ktkid and h.f_specid=".$row1['f_postid'];
                  $sql2 = "select distinct(k.f_num) f_num from veda_ktk k,veda_routes_ktk h,veda_routes r where k.f_id=h.f_ktkid and ".
                      "  r.f_id=h.f_routeid and r.f_postid=".$row1['f_postid'];
                  //echo $sql2."<br>";
                  $res2 = $dbh->query($sql2);
                  $ktk = "";
                  $lkk = 0;
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    if($lkk>0){$ktk = $ktk."; ";}
                    $ktk = $ktk.$row2['f_num'];
                    $lkk++;
                    }
                  $dopstr  = $row1['typez']." № ".$row1['f_num']." от ".format_dt($row1['f_dt'],0,0)." г. ".$ktk;
                  $dground = "Договор № ".$row1['f_dogname']." от ".format_dt($row1['f_dogdate'],0,0)." г. ";
                  }
                }
              //echo $dopstr."<br>";
              //echo $nomenk."<br>";
              $OP = getOPorgFrom1C($row['f_orgid'],$sorgdiv);
              $retval = c1c_createInvoice($orgid,$row['obaccid'],$contrid,$row['dogid'],$row['f_sum'],$row['f_val'],$row['f_dt'],$row['f_srok'],$row['f_nds'],$nomenk,$row['f_grnd'],$row['f_num'],$row['nbd1c'],$row['f_kod1c'],$dopstr,$row['f_status'],$dground,$OP);
              //if($_SESSION['loginid']==2)
              //  {var_dump($retval);}
              if($retval['retval']=="true")
                {
                $ninvst = 1;
                if($row['f_status']>1){$ninvst = $row['f_status'];}
                $sql = "update ".DBPref."schets set f_kod1c='".$retval['msg']."',f_num='".$retval['msg']."',f_status=".$ninvst." where f_id=".$row['f_id'];
                $dbh->exec($sql);
                $descr = "";
                if(isset($retval['descr'])){$descr="(".$retval['descr'].")";}
                $retval = "Счет экспортирован в 1С ".$descr;
                $retv = 1;
                }
              else
                {
                //sdid 1427
                //if($_SESSION['loginid']==2)
                //  {var_dump(json_decode(trim($retval),true));}
                //if(is_string($retval)){$retval=trim($retval);}
                //$retval = json_decode($retval,true);
                $retval = $retval['msg'];
                //~sdid 1427
                }
              }
            else
              {$retval = "Документ входит в состам агрегированного документа";}
            }
          else
            {$retval = "Документ не найдена в БД";}
          }
        elseif($ndo==1)
          {$retval = "Договор или спецификация не выгружены в 1С!!!";}
        }
      elseif($tdoc==10)//экспортируем Реализация (акты, накладные, УПД)
        {
        //echo $docid."_".$tdoc."<br>";
        $sql = "select i.f_c1guid,i.f_dogtype,i.f_dogid,i.f_id,i.f_sum,i.f_val,i.f_dt,i.f_nds,i.f_mainakt,i.f_ismainakt,i.f_status,ifnull(i.f_com,'') f_com,
                  i.f_num,i.f_vozm, 
                  i.f_orgid,i.f_contrid,i.f_kod1c,
                  (select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) nbd1c,
                  (select f_name from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid)) top, 
                  ifnull((select lct.f_valstr 
                          from ".DBPref."categs lct,".DBPref."spec_invoices lsi 
                          where lct.f_ctgtype=31 and lct.f_objecttype=4 and lct.f_objectid=lsi.f_idoper and lsi.f_id=i.f_operid),'') nomenkgroup,
                  case 
                    when ifnull((select f_orgdivision from ".DBPref."spec_invoices where f_id=i.f_operid),0)>0 then 
                      (select f_orgdivision from ".DBPref."spec_invoices where f_id=i.f_operid)
                    when i.f_dogtype=2 then (select f_orgdivision from ".DBPref."specs where f_id=i.f_dogid)
                    else 0 
                  end orgdiv,
                  case 
                    when i.f_dogtype=4 then 
                      (select f_onestrdocs from ".DBPref."dogs where f_id=i.f_dogid) 
                    when i.f_dogtype=2 then 
                      (select d.f_onestrdocs from ".DBPref."dogs d,".DBPref."specs s where d.f_id=s.f_dogid and s.f_id=i.f_dogid) 
                    else 0 
                  end onestrdocs, 
                  #sdid3302
                  case 
                    when i.f_dogtype=4 then ''
                    when i.f_dogtype=2 then 
                      ifnull((select s.f_dirmain from ".DBPref."specs s where s.f_id=i.f_dogid),'') 
                    else ''
                  end dirs, 
                  #~sdid3302
                  case ".
              "    when i.f_dogtype=4 then ".
              "      (select f_kod1c from ".DBPref."dogs where f_id=i.f_dogid)  ".
              "    when i.f_dogtype=2 then ".
              "      case when i.f_vozm=1 then ".
              "        (select f_kod1cp from ".DBPref."specs where f_id=i.f_dogid) else ".
              "        (select f_kod1cb from ".DBPref."specs where f_id=i.f_dogid) end ".
              "  end dogid, ".
              " (SELECT f_onestrdocs FROM ".DBPref."clients WHERE f_id=i.f_orgid) org_onestrdocs, ". // sdid 2321
              " case
                    when i.f_dogtype=2 then
                        (SELECT d.f_subtype FROM ".DBPref."dogs d, ".DBPref."specs s WHERE d.f_id=s.f_dogid AND s.f_id=i.f_dogid)
                    when i.f_dogtype=4 then
                        (SELECT f_subtype FROM ".DBPref."dogs WHERE f_id=i.f_dogid)
                end dogsubtype, ". // ~ sdid 2321
              //"  (select f_kod1c from ".DBPref."nomenk where f_nbd1c=(select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) and f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) nomenk ".
              //"  (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=(select f_nomenkid from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid))) and f_nbd1c=(select f_typecode from ".DBPref."client_codes where f_contrid=i.f_orgid) limit 1) nomenk ".
              "  (select nnf.f_kod1c ".
              "   from ".DBPref."nomenk nnf,".DBPref."nomenk nmn,".DBPref."typeopers otn,".DBPref."spec_invoices nsi,".DBPref."client_codes ncl ".
              "   where nnf.f_name=nmn.f_name and nmn.f_id=otn.f_nomenkid and otn.f_id=nsi.f_idoper and nnf.f_nbd1c=ncl.f_typecode and ".
              "     ncl.f_contrid=i.f_orgid and nsi.f_id=i.f_operid and nnf.f_edizm=nmn.f_edizm limit 1) nomenk ". 
              "from ".DBPref."akts i where i.f_id=".$docid." and i.f_status<>9";
        //if($_SESSION['loginid']==2)
        //  {echo $sql."<br>";}
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $dirs   = $row['dirs'];//sdid3302
          //echo "$dirs<br>";
          $orgdiv = $row['orgdiv'];
          //echo $row['f_contrid']."<br>";
          $orgid   = getClntInf(2,2,$row['f_orgid'],1,$row['f_orgid'],2);
          $contrid = getClntInf(2,2,$row['f_contrid'],0,$row['f_orgid'],2);
          //echo $orgid."<br>";
          //echo $contrid."<br>";
          //sdid2321
          $org_onestrdocs = $row['org_onestrdocs'];
          $dogsubtype     = $row['dogsubtype'];
          if($dogsubtype!=1){$org_onestrdocs=0;}
          if($org_onestrdocs==1)
            {
            $forwarding_services_code = "00000001677";
            $rf_nomenkonestr          = "";
            $rf_nomenkonestrgr        = "";
            $rf_nomenkonestrsum       = 0;
            $rf_nomenkonestrnds       = 0;
            $not_rf_nomenkonestr      = "";
            $not_rf_nomenkonestrgr    = "";
            $not_rf_nomenkonestrsum   = 0;
            $not_rf_nomenkonestrnds   = 0;
            }
          //~sdid2321
          if($row['f_mainakt']==0)
            {
            $vozm = 1;
            if($row['f_vozm']==2){$vozm = 2;}
            $detcountry = "";$dettdnum="";
            $nomenk = "[{\"detnomenk\":\"".$row['nomenk']."\",\"detsum\":\"".$row['f_sum']."\",".
                "\"izvozm\":\"".$vozm."\",\"detcol\":\"1\",".
                "\"detcountry\":\"".$detcountry."\",\"dettdnum\":\"".$dettdnum."\",".
                "\"nomenkgroup\":\"".$row['nomenkgroup']."\",".
                "\"detdescr\":\"".str_replace("\"","",str_replace("ВА. ","",$row['top']." ".$row['f_com']))."\",\"detnds\":\"".$row['f_nds']."\"}]";
            if($row['f_ismainakt']>0)
              {
              $nomenk          = "";
              $nomenkonestr    = "";
              $nomenkonestrgr  = "";
              $nomenkonestrsum = 0;
              $nomenkonestrnds = 0;
              $sql1 = "select i.f_sum,i.f_com,i.f_num,i.f_nds,
                         (select f_forrf from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid)) forrf, -- sdid 2321
                         (select f_name from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=i.f_operid)) top, 
                         ifnull((select lct.f_valstr 
                                 from ".DBPref."categs lct,".DBPref."spec_invoices lsi 
                                 where lct.f_ctgtype=31 and lct.f_objecttype=4 and lct.f_objectid=lsi.f_idoper and lsi.f_id=i.f_operid),'') nomenkgroup,
                         (select nnf.f_kod1c 
                          from ".DBPref."nomenk nnf,".DBPref."nomenk nmn,".DBPref."typeopers otn,".DBPref."spec_invoices nsi,".DBPref."client_codes ncl 
                          where nnf.f_name=nmn.f_name and nmn.f_id=otn.f_nomenkid and otn.f_id=nsi.f_idoper and nnf.f_nbd1c=ncl.f_typecode and 
                            ncl.f_contrid=i.f_orgid and nsi.f_id=i.f_operid and nnf.f_edizm=nmn.f_edizm limit 1) nomenk 
                       from ".DBPref."akts i where i.f_mainakt=".$row['f_id'];
              //echo $sql1."|";
              $res1 = $dbh->query($sql1);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                //sdid2321
                if($org_onestrdocs==1)
                  {
                  if($row1['forrf']==1)
                    {
                    if(strlen($rf_nomenkonestr)>0)
                      {$rf_nomenkonestr .= ", ";}
                    else
                      {
                      $rf_nomenkonestrgr  = $row1['nomenkgroup'];
                      $rf_nomenkonestrnds = $row1['f_nds'];
                      }
                    $rf_nomenkonestr    .= str_replace("ВА. ","",$row1['top'])." ".$row1['f_com'];
                    $rf_nomenkonestrsum += $row1['f_sum'];
                    }
                  else
                    {
                    if(strlen($not_rf_nomenkonestr)>0)
                      {$not_rf_nomenkonestr .= ", ";}
                    else
                      {
                      $not_rf_nomenkonestrgr  = $row1['nomenkgroup'];
                      $not_rf_nomenkonestrnds = $row1['f_nds'];
                      }
                    $not_rf_nomenkonestr    .= str_replace("ВА. ","",$row1['top'])." ".$row1['f_com'];
                    $not_rf_nomenkonestrsum += $row1['f_sum'];
                    }
                  }
                elseif($row['onestrdocs']==1)
                //if($row['onestrdocs']==1)
                //~sdid 2321
                  {
                  if(strlen($nomenkonestr)>0){$nomenkonestr=$nomenkonestr.", ";}
                  $nomenkonestr    = $nomenkonestr.str_replace("ВА. ","",$row1['top'])." ".$row1['f_com'];
                  $nomenkonestrsum = $nomenkonestrsum+$row1['f_sum'];
                  if(strlen($nomenkonestrgr)==0){$nomenkonestrgr=$row1['nomenkgroup'];}
                  $nomenkonestrnds = $row1['f_nds'];
                  }
                else
                  {
                  if(strlen($nomenk)>0)
                    {$nomenk=$nomenk.",";}
                  $nomenk = $nomenk."{\"detnomenk\":\"".$row1['nomenk']."\",\"detsum\":\"".$row1['f_sum']."\",".
                      "\"izvozm\":\"".$row['f_vozm']."\",\"detcol\":\"1\",".
                      "\"detcountry\":\"".$detcountry."\",\"dettdnum\":\"".$dettdnum."\",".
                      "\"nomenkgroup\":\"".$row1['nomenkgroup']."\",".
                      "\"detdescr\":\"".str_replace("\"","",str_replace("ВА. ","",$row1['top'])." ".$row1['f_com'])."\",\"detnds\":\"".$row1['f_nds']."\"}";
                  }
                }
              //sdid2321
              if($org_onestrdocs==1)
                {
                if(strlen($rf_nomenkonestr)>0)
                  {
                  $rf_nomenkonestr = "Транспортно-экспедиционные услуги до границы (".$rf_nomenkonestr.")";
                  $nomenk .= "{\"detnomenk\":\"".$forwarding_services_code."\",\"detsum\":\"".$rf_nomenkonestrsum."\",".
                          "\"izvozm\":\"".$row['f_vozm']."\",\"detcol\":\"1\",".
                          "\"detcountry\":\"".$detcountry."\",\"dettdnum\":\"".$dettdnum."\",".
                          "\"nomenkgroup\":\"".$rf_nomenkonestrgr."\",".
                          "\"detdescr\":\"".str_replace("\"","",str_replace("ВА. ","",$rf_nomenkonestr))."\",\"detnds\":\"".$rf_nomenkonestrnds."\"}";
                  }
                if(strlen($not_rf_nomenkonestr)>0)
                  {
                  if(strlen($nomenk)>0){$nomenk.=","; }
                  $not_rf_nomenkonestr = "Транспортно-экспедиционные услуги после границы (".$not_rf_nomenkonestr.")";
                  $nomenk .= "{\"detnomenk\":\"".$forwarding_services_code."\",\"detsum\":\"".$not_rf_nomenkonestrsum."\",".
                          "\"izvozm\":\"".$row['f_vozm']."\",\"detcol\":\"1\",".
                          "\"detcountry\":\"".$detcountry."\",\"dettdnum\":\"".$dettdnum."\",".
                          "\"nomenkgroup\":\"".$not_rf_nomenkonestrgr."\",".
                          "\"detdescr\":\"".str_replace("\"","",str_replace("ВА. ","",$not_rf_nomenkonestr))."\",\"detnds\":\"".$not_rf_nomenkonestrnds."\"}";
                  }
                }
              elseif($row['onestrdocs']==1)
              //if($row['onestrdocs']==1)
              //~sdid2321
                {
                $nomenkonestr = "Вознаграждение агента (".$nomenkonestr.")";
                $nomenk = $nomenk."{\"detnomenk\":\"00000000339\",\"detsum\":\"".$nomenkonestrsum."\",".
                    "\"izvozm\":\"".$row['f_vozm']."\",\"detcol\":\"1\",".
                    "\"detcountry\":\"".$detcountry."\",\"dettdnum\":\"".$dettdnum."\",".
                    "\"nomenkgroup\":\"".$nomenkonestrgr."\",".
                    "\"detdescr\":\"".str_replace("\"","",str_replace("ВА. ","",$nomenkonestr))."\",\"detnds\":\"".$nomenkonestrnds."\"}";
                }
              $nomenk = "[".$nomenk."]";
              }
            $dopstr = "";
            if(($row['f_dogtype']==2)&&($row['f_dogid']>0))
              {
              $sql1 = "select f_num,f_dt,f_postid,f_orgdivision ".
                      "from ".DBPref."specs where f_id=".$row['f_dogid'];
              //echo $sql1."<br>";
              $res1 = $dbh->query($sql1);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if($orgdiv==0)
                  {$orgdiv=$row1['f_orgdivision'];}
                //$sql2 = "select distinct(k.f_num) f_num,k.f_id from veda_ktk k,veda_ktk_hist h where k.f_id=h.f_ktkid and h.f_specid=".$row1['f_postid'];
                $sql2 = "select distinct(k.f_num) f_num from veda_ktk k,veda_routes_ktk h,veda_routes r where k.f_id=h.f_ktkid and ".
                    "  r.f_id=h.f_routeid and r.f_postid=".$row1['f_postid'];
                //echo $sql2."<br>";
                $res2 = $dbh->query($sql2);
                $ktk = "";
                while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {
                  $ktk = $ktk.$row2['f_num'].";";
                  }
                }
              $dopstr = "Спецификация № ".$row1['f_num']." от ".format_dt($row1['f_dt'],0,0)." г. ".$ktk;
              }
            //if($_SESSION['loginid']==2)
            //  {
            //  echo $dopstr."<br>";
            //  echo $nomenk."<br>";
            //  }    
            $descr   = "";
            $sorgdiv = "";
            if($orgdiv>0){$sorgdiv = "{\"orgdivnum\":\"".$orgdiv."\"}";}
            $OP = getOPorgFrom1C($row['f_orgid'],$sorgdiv);
            //$descr = $OP."|".$row['f_orgid']."|";
            $nomenk = str_replace("\t"," ",str_replace("\n","",$nomenk));
            //if($_SESSION['loginid']==2){echo $nomenk."|";}
            $retval = "";
            if(strlen($dirs)>0){$dopstr=trim($dopstr." ".str_replace("&bsol;","\\",$dirs));}//sdid3302
            //echo $orgid."<br>";
            //echo $contrid."<br>";
            $ret1c = c1c_addupdAktR($orgid, $contrid, $row['dogid'], $row['f_num'], $row['f_dt'], $row['f_com'], $row['f_sum'], $row['f_val'], $row['f_nds'], $row['f_vozm'], $nomenk, $row['nbd1c'], $dopstr, $OP);//sdid3302
            //var_dump($ret1c);
            //if($_SESSION['loginid']==2){var_dump($ret1c);}
            if($ret1c['retval']=="true")
              {
              $retstatus = 0;
              $retc1guid = "";
              if(isset($ret1c['status']))
                {
                if($ret1c['status']==1)
                  {$retstatus = 7;$retval = "Документ в 1С выгружен, но НЕ проведен. ";}
                elseif($ret1c['status']==2)
                  {$retstatus = 8;$retval = "Выгружено в 1С, проведено. ";}
                else
                  {$retval = "Ошибка выгрузки в 1С. ";}
                if(isset($ret1c['c1guid']))
                  {$retc1guid = $ret1c['c1guid'];}
                }
              if($retstatus>0)
                {
                $kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>$retstatus,'f_dt1c'=>$row['f_dt']);
                if(strlen($retc1guid)>0)
                  {$kai['f_c1guid']=$retc1guid;}
                $ar   = json_decode(editRowTbl($kai), true);
                //var_dump($ar);
                if($ar[0]=="true")
                  {$retval = $retval." Документ обновлен в системе. ";}
                else
                  {$retval = $retval." Ошибка обновления докмуента в системе. ";}
                //$sql = "update ".DBPref."akts set f_kod1c='".$retval['msg']."',f_num='".$retval['msg']."',f_status=8 where f_id=".$row['f_id'];
                //$dbh->exec($sql);
                if(isset($ret1c['descr'])){$descr=$descr.$ret1c['descr'];}
                $retval = $retval." ".$descr;
                $retv = 1;
                }
              }
            else
              {$retval = $ret1c['msg'];}
            }
          else
            {$retval = "Документ входит в состам агрегированного документа";}
          }
        else
          {$retval = "Документ не найдена в БД";}
        }
      //sdid3596
      elseif($tdoc==28)//экспортируем Корректировку Поступления (акты, накладные)
        {
        $sql = "select a.f_c1guid,a.f_dogtype,
                  o.f_mainnomenk mainnomenk,
                  case 
                    when ifnull(a.f_operid,0)>0 
                      then
                           (select ifnull(s1.f_subtype,-1) 
                              from veda_specs s1,veda_spec_invoices si1 
                             where s1.f_id=si1.f_specid
                               and si1.f_id=a.f_operid
                           )
                    else '-1'
                  end specsubtype, 
                  case 
                    when ifnull(a.f_operid,0)>0
                      then
                        (select case when si1.f_isvozm=2 then 1 else 0 end 
                           from veda_spec_invoices si1
                          where si1.f_id=a.f_operid
                        )
                    else
                      case 
                        when (select count(si1.f_isvozm) 
                                   from veda_akts_details ad1, 
                                        veda_akts_details_opers ado1,
                                        veda_spec_invoices si1
                                  where ad1.f_aktid=a.f_id
                                    and ado1.f_akts_detailsid=ad1.f_id
                                    and si1.f_id=ado1.f_operid
                                    and si1.f_isvozm<>2
                                )>0
                             then 0
                        when (select count(si1.f_isvozm) 
                                   from veda_akts_details ad1, 
                                        veda_akts_details_opers ado1,
                                        veda_spec_invoices si1
                                  where ad1.f_aktid=a.f_id
                                    and ado1.f_akts_detailsid=ad1.f_id
                                    and si1.f_id=ado1.f_operid
                                    and si1.f_isvozm=2
                                )>0
                             then 1
                        else 0
                      end 
                    end isnovozm,
                  a.f_dogid,a.f_contrid,
                  c.f_matype,
                  ifnull(o.f_kod1c,'') okod1c,
                  o.f_id ofid,
                  ifnull((select ccc.f_code from ".DBPref."client_codes ccb,".DBPref."client_codes ccc 
                          where ccb.f_contrid=o.f_id and ccc.f_contrid=c.f_id and ccc.f_typecode=ccb.f_typecode),ifnull(c.f_kod1c,'')) ckod1c,
                  c.f_id cfid,ifnull(d.f_kod1c,'') dkod1c,
                  a.f_operid, ifnull(a.f_com,'') comm,ifnull(trim(a.f_num),'') num,
                  a.f_dt dt,case when a.f_dt1c='0000-00-00' then CURRENT_DATE() else a.f_dt1c end dt1c,
                  (select f_typecode from ".DBPref."client_codes where f_contrid=o.f_id) nbd1c, 
                  a.f_id,ifnull(trim(a.f_kod1c),'') num1c,ifnull(a.f_sum,0) dsum,ifnull(a.f_val,643) dval,
                  ifnull(a.f_nds,0) vnds,a.f_ndssum,
                  ifnull((select count(*) from veda_akts_details where f_aktid=a.f_id),0) adcount,
                  case 
                    when (select count(*) from veda_akts_details where f_aktid=a.f_id)>0 then ''
                    when a.f_operid>0 then
                      case 
                        when a.f_type=23 then 
                          ifnull((select concat(sit.f_name,'. ',si.f_invcom) from veda_spec_invoices si, veda_typeopers sit where si.f_id=a.f_operid and sit.f_id=si.f_idoper),'')
                        when a.f_type=25 and (select si.f_invoiceid from veda_spec_invoices si where si.f_id=a.f_operid)>0 then 
                          ifnull((select concat('Товар по инвойсу № ',sch.f_num) from veda_spec_invoices si,veda_schets sch where si.f_id=a.f_operid and sch.f_id=si.f_invoiceid),'')
                        when a.f_type=25 then 'Товар по инвойсу №'
                        else ''
                      end
                    else ''
                  end oname,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_orgdivision from ".DBPref."spec_invoices where f_id=a.f_operid),0)>0 then 
                          (select f_orgdivision from ".DBPref."spec_invoices where f_id=a.f_operid)
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          (select si.f_orgdivision from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid)
                        else 0 
                      end
                    else 0
                  end orgdiv,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select si.f_specid from ".DBPref."spec_invoices si where si.f_id=a.f_operid),0)
                        else 0 
                      end
                    else 0
                  end sfid,
                  case 
                    when a.f_operid>0 then
                      ifnull((select si.f_isvozm from ".DBPref."spec_invoices si where si.f_id=a.f_operid),0)
                    else 0
                  end oisvozm,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ss.f_dirmain from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid),'')
                        else ''
                      end
                    else ''
                  end dirmain,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ss.f_kod1cp from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid),'')
                        else ''
                      end
                    else ''
                  end spec1c,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ifnull((select ccc.f_code from ".DBPref."client_codes ccb,".DBPref."client_codes ccc 
                                                 where ccb.f_contrid=sd.f_orgid and ccc.f_contrid=sd.f_contrid and ccc.f_typecode=ccb.f_typecode),'')
                                  from ".DBPref."dogs sd,".DBPref."specs ss,".DBPref."spec_invoices si 
                                  where si.f_id=a.f_operid and ss.f_id=si.f_specid and sd.f_id=ss.f_dogid),'')
                        else ''
                      end
                    else ''
                  end speccntr1c
                from ".DBPref."akts a,".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
                where a.f_type in (7,29) and a.f_dogid=d.f_id and a.f_orgid=o.f_id and a.f_contrid=c.f_id and a.f_id=(select f_corid from ".DBPref."akts where f_id=$docid) and a.f_status<>9";
        //error_log("\n\nsql1: $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $mainnomenk    = $row['mainnomenk'];
          $specsubtype   = $row['specsubtype'];
          $isnovozm      = $row['isnovozm'];
          $lspecsubtype  = 0; 
          $subconto1     = "";
          $subcontonu1   = "";
          $ndsacc        = "";  
          //if(($row['f_dogtype']==0)||($row['f_dogtype']==4)||($row['f_dogtype']==6))
          //  {
          $descr          = "";
          $sql            = "";
          $okod1c         = getClntInf(2,2,$row['ofid'],1,$row['ofid'],2);
          $ckod1c         = getClntInf(2,2,$row['cfid'],0,$row['ofid'],2);
          $OP             = getOPorgFrom1C($row['ofid'],"");
          $kold           = 0;
          $vozmc          = $row['oisvozm'];
          $dirs           = "";
          $kurscbpps      = 1;
          $fromtovarvdolg = 0;
          $dstr           = "";
          $sfid           = -1;
          $lvozmtotal     = 0;
          //учитываем поступления только с детализацией
          $sql  = "select o.f_orgdivision,ifnull(do.f_bydog,0) bydog,
                     case 
                       when ifnull(o.f_orgdivision,0)>0 then 
                         o.f_orgdivision
                       when o.f_parenttype=2 then 
                         (select f_orgdivision from ".DBPref."specs where f_id=o.f_specid)
                       else 0 
                     end orgdiv,s.f_subtype lspecsubtype,1 kurscbpps,0 fromtovarvdolg,
                     case 
                       when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then do.f_sum 
                       else o.f_sum 
                     end dsum,
                     case 
                       when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then d.f_price 
                       when d.f_count=1 then o.f_sum 
                       else d.f_price 
                     end f_price,
                     ifnull(s.f_id,0) sfid,s.f_dirmain,ifnull(o.f_isvozm,3) f_isvozm,s.f_kod1cp,cl.f_kod1c,ifnull(cl.f_id,0) clfid,
                     d.f_num,d.f_grnd,d.f_count,ifnull(d.f_nds,0) dnds,d.f_ndssum, 
                     case 
                       when d.f_nomenk=0 then (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=top.f_nomenkid) and f_nbd1c=".$row['nbd1c']." limit 1) 
                       else (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=d.f_nomenk) and f_nbd1c=".$row['nbd1c']." limit 1) 
                     end ncode 
                     ,(select f_type from ".DBPref."akts where f_id=d.f_aktid) atype
                     ,d.f_aktid daktid,do.f_id doid
                     ,case when (select f_type from ".DBPref."akts where f_id=d.f_aktid)=29 then 0 else do.f_corid end docorid	
                     ,(select count(*) from ".DBPref."akts_details_opers where f_corid=do.f_id) withcorid
                   from ".DBPref."akts_details d 
                   left join ".DBPref."akts_details_opers do on do.f_akts_detailsid=d.f_id 
                   left join ".DBPref."spec_invoices o on o.f_id=do.f_operid 
                   left join ".DBPref."specs s on s.f_id=o.f_specid 
                   left join ".DBPref."dogs dd on dd.f_id=s.f_dogid 
                   left join ".DBPref."clients cl on cl.f_id=dd.f_contrid 
                   left join ".DBPref."typeopers top on top.f_id=o.f_idoper 
                   where d.f_aktid in (".$row['f_id'].",$docid) order by s.f_id";
          //error_log("\n\nsql2: $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
          $res1       = $dbh->query($sql);
          while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            $lspecsubtype = $row1['lspecsubtype'];
            $orgdiv  = $row1['orgdiv'];
            $sorgdiv = "";
            if($orgdiv>0)
              {$sorgdiv = "{\"orgdivnum\":\"".$orgdiv."\"}";
               $OP = getOPorgFrom1C($row['ofid'],$sorgdiv);}
            if($sfid!=$row1['sfid'])
              {
              if(strlen($dirs)>0){$dirs=$dirs.",";}
              $dirs=$dirs.$row1['f_dirmain'];
              $sfid=$row1['sfid'];
              }
            if(($row1['f_isvozm']==1)||($row1['f_isvozm']==0)){$vozmc++;}
            if($row1['f_isvozm']==1) {$lvozmtotal++;}
            $clkod1c = "-";
            if($row1['clfid']>0)
              {$clkod1c = getClntInf(2,2,$row1['clfid'],0,$row['ofid'],2);}
            if($row1['fromtovarvdolg']==1)
              {$kurscbpps      = $row1['kurscbpps'];
               $fromtovarvdolg = 1;}
            $subconto1     = "";
            $subcontonu1   = "";
            $ndsacc        = 0;
            if($tdoc==28 && $lspecsubtype==2 && $isnovozm==1 && $mainnomenk!=22 && $mainnomenk!=0)
              {
              $subconto1   = "Подготовка документов";
              $subcontonu1 = "Подготовка документов";
              $ndsacc      = 1;  
              }
            elseif($tdoc==28 && $isnovozm==1 && $mainnomenk==22)
              {
              $subconto1   = "Транспортно-экспедиторские услуги";
              $subcontonu1 = "Транспортно-экспедиторские услуги";
              $ndsacc      = 1;
              }
            $schz       = ""; // Счет затрат
            $schznu     = ""; // Счет затрат НУ
            $subconto   = ""; // Субконто
            $subcontonu = ""; // Субконто НУ
            if(isset($row1['bydog']))
              {
              $bydog = $row1['bydog'];
              if($bydog>0) 
                {
                $schz       = "91.02"; // Счет затрат
                $schznu     = "91.02"; // Счет затрат НУ
                $subconto   = "Расходы на  услуги банка"; // Субконто
                $subcontonu = "Расходы на  услуги банка"; // Субконто НУ
                } 
              }
            if($row1['docorid']==0)//если корректирующая строка, то ее пропускаем, дабавим внутри
              {
              if(strlen($dstr)>0){$dstr.=",";}
              $dstr.= "{";
              $dstr.= "\"op\":\"$OP\",\"izvozm\":\"".$row1['f_isvozm']."\",\"speccontrid1C\":\"$clkod1c\",\"specid1C\":\"".$row1['f_kod1cp'].
                      "\",\"detnum\":\"".$row1['f_num']."\",\"detdescr\":\"".str_replace("\"","",$row1['f_grnd'])."\",\"detnomenk\":\"".$row1['ncode'].
                      "\",\"detcol\":\"".(float)$row1['f_count']."\",\"detprice\":\"".$row1['f_price']."\",\"kurscbpps\":\"".$row1['kurscbpps'].
                      "\",\"fromtovarvdolg\":\"".$row1['fromtovarvdolg']."\",\"detsum\":\"".$row1['dsum']."\",\"detnds\":\"".$row1['dnds'].
                      "\",\"schz\":\"$schz\",\"schznu\":\"$schznu\",\"subconto\":\"$subconto\",\"subcontonu\":\"$subcontonu\",\"subconto1\":\"$subconto1".
                      "\",\"subcontonu1\":\"$subcontonu1\",\"ndsacc\":\"$ndsacc\",\"withcorid\":\"".$row1['withcorid']."\",\"docorid\":\"".$row1['docorid'].
                      "\",\"atype\":\"".$row1['atype']."\"";
              if($row1['withcorid']>0)//если строка с скорректировкой, то дабавляем ее
                {
                $cor_kurscbpps      = 1;
                $cor_fromtovarvdolg = 0;
                $cor_dirs           = "";
                $cor_lvozmtotal     = 0;
                $sql  = "select o.f_orgdivision,ifnull(do.f_bydog,0) bydog,
                           case 
                             when ifnull(o.f_orgdivision,0)>0 then 
                               o.f_orgdivision
                             when o.f_parenttype=2 then 
                               (select f_orgdivision from ".DBPref."specs where f_id=o.f_specid)
                             else 0 
                           end orgdiv,
                           s.f_subtype lspecsubtype,1 kurscbpps,0 fromtovarvdolg,
                           case 
                             when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then do.f_sum 
                             else o.f_sum 
                           end dsum,
                           case 
                             when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then d.f_price 
                             when d.f_count=1 then o.f_sum 
                             else d.f_price 
                           end f_price,ifnull(s.f_id,0) sfid,s.f_dirmain,ifnull(o.f_isvozm,3) f_isvozm,s.f_kod1cp,cl.f_kod1c,ifnull(cl.f_id,0) clfid,
                           d.f_num,d.f_grnd,d.f_count,ifnull(d.f_nds,0) dnds,d.f_ndssum, 
                           case 
                             when d.f_nomenk=0 then (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=top.f_nomenkid) and f_nbd1c=".$row['nbd1c']." limit 1) 
                             else (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=d.f_nomenk) and f_nbd1c=".$row['nbd1c']." limit 1) 
                           end ncode 
                           ,(select f_type from ".DBPref."akts where f_id=d.f_aktid) atype
                           ,d.f_aktid daktid,do.f_id doid
                           ,case when (select f_type from ".DBPref."akts where f_id=d.f_aktid)=29 then 0 else do.f_corid end docorid	
                           ,(select count(*) from ".DBPref."akts_details_opers where f_corid=do.f_id) withcorid
                         from ".DBPref."akts_details d 
                         left join ".DBPref."akts_details_opers do on do.f_akts_detailsid=d.f_id 
                         left join ".DBPref."spec_invoices o on o.f_id=do.f_operid 
                         left join ".DBPref."specs s on s.f_id=o.f_specid 
                         left join ".DBPref."dogs dd on dd.f_id=s.f_dogid 
                         left join ".DBPref."clients cl on cl.f_id=dd.f_contrid 
                         left join ".DBPref."typeopers top on top.f_id=o.f_idoper 
                         where do.f_id=(select f_id from veda_akts_details_opers where f_corid=".$row1['doid'].") order by s.f_id";
                $res2       = $dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {
                  $cor_lspecsubtype = $row2['lspecsubtype'];
                  $cor_orgdiv       = $row2['orgdiv'];
                  $cor_sorgdiv      = "";
                  if($cor_orgdiv>0){$cor_sorgdiv = "{\"orgdivnum\":\"".$cor_orgdiv."\"}";
                  $cor_OP           = getOPorgFrom1C($row['ofid'],$cor_sorgdiv);}
                  $cor_dirs         = $row2['f_dirmain'];
                  if($row2['f_isvozm']==1) {$cor_lvozmtotal++;}
                  $cor_clkod1c     = "-";
                  if($row2['clfid']>0)
                    {$cor_clkod1c = getClntInf(2,2,$row2['clfid'],0,$row['ofid'],2);}
                  if($row2['fromtovarvdolg']==1)
                    {$cor_kurscbpps      = $row2['kurscbpps'];
                     $cor_fromtovarvdolg = 1;}
                  $cor_subconto1     = "";
                  $cor_subcontonu1   = "";
                  $cor_ndsacc        = 0;
                  if($cor_lspecsubtype==2 && $isnovozm==1 && $mainnomenk!=22 && $mainnomenk!=0)
                    {
                    $cor_subconto1   = "Подготовка документов";
                    $cor_subcontonu1 = "Подготовка документов";
                    $cor_ndsacc      = 1;  
                    }
                  elseif($isnovozm==1 && $mainnomenk==22)
                    {
                    $cor_subconto1   = "Транспортно-экспедиторские услуги";
                    $cor_subcontonu1 = "Транспортно-экспедиторские услуги";
                    $cor_ndsacc      = 1;
                    }
                  $cor_schz       = ""; // Счет затрат
                  $cor_schznu     = ""; // Счет затрат НУ
                  $cor_subconto   = ""; // Субконто
                  $cor_subcontonu = ""; // Субконто НУ
                  if(isset($row2['bydog']))
                    {
                    $cor_bydog = $row1['bydog'];
                    if($cor_bydog>0) 
                      {
                      $cor_schz       = "91.02"; // Счет затрат
                      $cor_schznu     = "91.02"; // Счет затрат НУ
                      $cor_subconto   = "Расходы на  услуги банка"; // Субконто
                      $cor_subcontonu = "Расходы на  услуги банка"; // Субконто НУ
                      } 
                    }
                  $dstr.= "  ,\"cor_op\":\"$cor_OP\",\"cor_izvozm\":\"".$row2['f_isvozm']."\",\"cor_speccontrid1C\":\"$cor_clkod1c\",\"cor_specid1C\":\"".$row2['f_kod1cp'].
                          "\",\"cor_detnum\":\"".$row2['f_num']."\",\"cor_detdescr\":\"".str_replace("\"","",$row2['f_grnd'])."\",\"cor_detnomenk\":\"".$row2['ncode'].
                          "\",\"cor_detcol\":\"".(float)$row2['f_count']."\",\"cor_detprice\":\"".$row2['f_price']."\",\"cor_kurscbpps\":\"".$row2['kurscbpps'].
                          "\",\"cor_fromtovarvdolg\":\"".$row2['fromtovarvdolg']."\",\"cor_detsum\":\"".$row2['dsum']."\",\"cor_detnds\":\"".$row2['dnds'].
                          "\",\"cor_schz\":\"$cor_schz\",\"cor_schznu\":\"$cor_schznu\",\"cor_subconto\":\"$cor_subconto\",\"cor_subcontonu\":\"$cor_subcontonu\",\"cor_subconto1\":\"$cor_subconto1".
                          "\",\"cor_subcontonu1\":\"$cor_subcontonu1\",\"cor_ndsacc\":\"$cor_ndsacc\",\"cor_withcorid\":\"".$row2['withcorid']."\",\"cor_docorid\":\"".$row2['docorid'].
                          "\",\"cor_atype\":\"".$row2['atype']."\"";
                  }
                }
              $dstr.= "}";
              $kold++;
              }
            }
          if($kold>0)
            {
            $dstr = "[".$dstr."]";
            //доабвляем\обновляем корректирвоку акта поступления в 1С
            $kodc     = "";
            $dtc      = date("Y-m-d");
            $orgcodc  = $okod1c;
            $vidoper  = 1;
            $inaktnum = $row['num1c'];
            $inaktdt  = substr($row['dt1c'],0,10);
            $ckod     = $ckod1c;
            $dkod     = $row['dkod1c'];
            $dval     = $row['dval'];
            $dsum     = $row['dsum'];
            //error_log("\n\nkodc:$kodc\n\ndtc:$dtc\n\norgcodc:$orgcodc\n\nvidoper:$vidoper\n\ninaktnum:$inaktnum\n\ninaktdt:$inaktdt\n\nckod:$ckod\n\ndkod:$dkod\n\ndval:$dval\n\ndsum:$dsum\n\nop:$OP\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
            //error_log("\n\ndstr:$dstr\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
            $ret1c    = c1c_addupdAktCorrect($kodc,$dtc,$orgcodc,$vidoper,$inaktnum,$inaktdt,$ckod,$dkod,$dval,$dsum,$OP,$dstr);
            if($ret1c['retval']=="true")
              {
              $retstatus = 0;
              $retc1guid = "";
              if(isset($ret1c['status']))
                {
                if($ret1c['status']==1)
                  {$retstatus = 7;$retval = "Документ в 1С выгружен, но НЕ проведен. ";}
                elseif($ret1c['status']==2)
                  {$retstatus = 8;$retval = "Выгружено в 1С, проведено. ";}
                else
                  {$retval = "Ошибка выгрузки в 1С. ";}
                if(isset($ret1c['c1guid']))
                  {$retc1guid = $ret1c['c1guid'];}
                }
              if($retstatus>0)
                {
                $kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>$retstatus,'f_kod1c'=>$ret1c['msg'],'f_num'=>$ret1c['msg']);
                if(isset($ret1c['dt']))
                  {
                  $ndt = substr($ret1c['dt'],6,4)."-".substr($ret1c['dt'],3,2)."-".substr($ret1c['dt'],0,2);
                  $kai['f_dt1c']=$ndt;
                  }
                if(strlen($retc1guid)>0)
                  {$kai['f_c1guid']=$retc1guid;}
                $ar   = json_decode(editRowTbl($kai), true);
                if($ar[0]=="true")
                  {$retval = $retval." Документ обновлен в системе. ";}
                else
                  {$retval = $retval." Ошибка обновления докмуента в системе. ";}
                if(isset($ret1c['descr'])){$descr=$descr.$ret1c['descr'];}
                $retval = $retval." ".$descr;
                $retv = 1;
                }
              }
            else
              {
              $retval = "Ошибка обновления/проведения: ".$ret1c['msg'];
              }
            }
          else
            {
            $retval = "По акту отсутствует детализация";
            }
          //  }
          //else
          //  {
          //  $retval = "Тип договора не соответствуем типу выгружаемого документа";
          //  }
          }
        else
          {
          $retval = "Не найден документ соответствующий условиям";
          }
        }
      //~sdid3596
      elseif(($tdoc==7)||($tdoc==23)||($tdoc==25))//экспортируем Поступления (акты, накладные)
        {
        $sql = "select a.f_c1guid,a.f_dogtype,
                  o.f_mainnomenk mainnomenk, #sdid 3594
                  #sdid 3594
                  case 
                    when ifnull(a.f_operid,0)>0 
                      then
                           (select ifnull(s1.f_subtype,-1) 
                              from veda_specs s1,veda_spec_invoices si1 
                             where s1.f_id=si1.f_specid
                               and si1.f_id=a.f_operid
                           )
                    else '-1'
                  end specsubtype, 
                  case 
                    when ifnull(a.f_operid,0)>0
                      then
                        (select case when si1.f_isvozm=2 then 1 else 0 end 
                           from veda_spec_invoices si1
                          where si1.f_id=a.f_operid
                        )
                    else
                      case 
                        when (select count(si1.f_isvozm) 
                                   from veda_akts_details ad1, 
                                        veda_akts_details_opers ado1,
                                        veda_spec_invoices si1
                                  where ad1.f_aktid=a.f_id
                                    and ado1.f_akts_detailsid=ad1.f_id
                                    and si1.f_id=ado1.f_operid
                                    and si1.f_isvozm<>2
                                )>0
                             then 0
                        when (select count(si1.f_isvozm) 
                                   from veda_akts_details ad1, 
                                        veda_akts_details_opers ado1,
                                        veda_spec_invoices si1
                                  where ad1.f_aktid=a.f_id
                                    and ado1.f_akts_detailsid=ad1.f_id
                                    and si1.f_id=ado1.f_operid
                                    and si1.f_isvozm=2
                                )>0
                             then 1
                        else 0
                      end 
                    end isnovozm,
                  #~sdid 3594
                  #sdid 1275
                  a.f_dogid,a.f_contrid,
                  #~sdid 1275
                  #sdid 2242
                  c.f_matype,
                  #sdid 2242
                  ifnull(o.f_kod1c,'') okod1c,
                  o.f_id ofid,
                  #ifnull(c.f_kod1c,'') ckod1c,
                  ifnull((select ccc.f_code from ".DBPref."client_codes ccb,".DBPref."client_codes ccc 
                          where ccb.f_contrid=o.f_id and ccc.f_contrid=c.f_id and ccc.f_typecode=ccb.f_typecode),ifnull(c.f_kod1c,'')) ckod1c,
                  c.f_id cfid,ifnull(d.f_kod1c,'') dkod1c,
                  a.f_operid, ifnull(a.f_com,'') comm,ifnull(trim(a.f_num),'') num,
                  a.f_dt dt,case when a.f_dt1c='0000-00-00' then CURRENT_DATE() else a.f_dt1c end dt1c,
                  (select f_typecode from ".DBPref."client_codes where f_contrid=o.f_id) nbd1c, 
                  a.f_id,ifnull(trim(a.f_kod1c),'') num1c,ifnull(a.f_sum,0) dsum,ifnull(a.f_val,643) dval,
                  ifnull(a.f_nds,0) vnds,a.f_ndssum,
                  ifnull((select count(*) from veda_akts_details where f_aktid=a.f_id),0) adcount,
                  case 
                    when (select count(*) from veda_akts_details where f_aktid=a.f_id)>0 then ''
                    when a.f_operid>0 then
                      case 
                        when a.f_type=23 then 
                          ifnull((select concat(sit.f_name,'. ',si.f_invcom) from veda_spec_invoices si, veda_typeopers sit where si.f_id=a.f_operid and sit.f_id=si.f_idoper),'')
                        when a.f_type=25 and (select si.f_invoiceid from veda_spec_invoices si where si.f_id=a.f_operid)>0 then 
                          ifnull((select concat('Товар по инвойсу № ',sch.f_num) from veda_spec_invoices si,veda_schets sch where si.f_id=a.f_operid and sch.f_id=si.f_invoiceid),'')
                        when a.f_type=25 then 'Товар по инвойсу №'
                        else ''
                      end
                    else ''
                  end oname,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_orgdivision from ".DBPref."spec_invoices where f_id=a.f_operid),0)>0 then 
                          (select f_orgdivision from ".DBPref."spec_invoices where f_id=a.f_operid)
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          (select si.f_orgdivision from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid)
                        else 0 
                      end
                    else 0
                  end orgdiv,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select si.f_specid from ".DBPref."spec_invoices si where si.f_id=a.f_operid),0)
                        else 0 
                      end
                    else 0
                  end sfid,
                  case 
                    when a.f_operid>0 then
                      ifnull((select si.f_isvozm from ".DBPref."spec_invoices si where si.f_id=a.f_operid),0)
                    else 0
                  end oisvozm,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ss.f_dirmain from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid),'')
                        else ''
                      end
                    else ''
                  end dirmain,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ss.f_kod1cp from ".DBPref."specs ss,".DBPref."spec_invoices si where si.f_id=a.f_operid and ss.f_id=si.f_specid),'')
                        else ''
                      end
                    else ''
                  end spec1c,
                  case 
                    when a.f_operid>0 then
                      case 
                        when ifnull((select f_parenttype from ".DBPref."spec_invoices where f_id=a.f_operid),0)=2 then 
                          ifnull((select ifnull((select ccc.f_code from ".DBPref."client_codes ccb,".DBPref."client_codes ccc 
                                                 where ccb.f_contrid=sd.f_orgid and ccc.f_contrid=sd.f_contrid and ccc.f_typecode=ccb.f_typecode),'')
                                  from ".DBPref."dogs sd,".DBPref."specs ss,".DBPref."spec_invoices si 
                                  where si.f_id=a.f_operid and ss.f_id=si.f_specid and sd.f_id=ss.f_dogid),'')
                        else ''
                      end
                    else ''
                  end speccntr1c
                from ".DBPref."akts a,".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
                where a.f_type in (7,23,25) and a.f_dogid=d.f_id and a.f_orgid=o.f_id and a.f_contrid=c.f_id and a.f_id in (".$docid.") and a.f_status<>9";
        //if($_SESSION['loginid']==2)
        //  {echo $tdoc."|".$sql."<br>";}
        //echo $sql."<br>";
        //$retval = "_1_".$sql."_";
        //$answ = "<table border=1 style='width: 100%;'><tr><td colspan=3>Протокол выполнения:</td></tr>";
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          //sdid 3594
          $mainnomenk    = $row['mainnomenk'];
          $specsubtype   = $row['specsubtype'];
          $isnovozm      = $row['isnovozm'];
          $lspecsubtype  = 0; 
          //$lorgid        = $row['orgid']; 
          $subconto1     = "";
          $subcontonu1   = "";
          $ndsacc        = "";  
          //~sdid 3594
          if(($row['f_dogtype']==0)||($row['f_dogtype']==4)||($row['f_dogtype']==6))
            {
            $descr  = "";
            $sql    = "";
            $okod1c = getClntInf(2,2,$row['ofid'],1,$row['ofid'],2);
            $ckod1c = getClntInf(2,2,$row['cfid'],0,$row['ofid'],2);
            $OP = getOPorgFrom1C($row['ofid'],"");
            //echo $OP."<br>";
            //$OP="ОП";
            //if(($row['ofid']==15))
            //{$OP="ОП";}
            //elseif($row['ofid']==30)
            //{$OP="ОП ВК";}
            //elseif(($row['ofid']==31)||($row['ofid']==32)||($row['ofid']==29)||($row['ofid']==34))
            //{$OP="Основное";}
            $kold  = 0;
            $vozmc = $row['oisvozm'];
            $dirs  = "";
            //sdid1583
            $kurscbpps      = 1;
            $fromtovarvdolg = 0;
            //~sdid1583
            if(($row['f_operid']==0)&&($tdoc!=23)&&($tdoc!=25))
              {
              $sql  = "select o.f_orgdivision,
                         ifnull(do.f_bydog,0) bydog, #sdid 3478
                         case 
                           when ifnull(o.f_orgdivision,0)>0 then 
                             o.f_orgdivision
                           when o.f_parenttype=2 then 
                             (select f_orgdivision from ".DBPref."specs where f_id=o.f_specid)
                           else 0 
                         end orgdiv,
                         s.f_subtype lspecsubtype, #sdid 3594 
                         #sdid1583
                         1 kurscbpps,
                         0 fromtovarvdolg,
                         #~sdid1583
                         case ".
                         "    when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then do.f_sum ".
                         "    else o.f_sum ".
                         "  end dsum,".
                         "  case ".
                         "    when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then d.f_price ".
                         "    when d.f_count=1 then o.f_sum ".
                         "    else d.f_price ".
                         "  end f_price,".
                         "  ifnull(s.f_id,0) sfid,s.f_dirmain,ifnull(o.f_isvozm,3) f_isvozm,s.f_kod1cp,cl.f_kod1c,ifnull(cl.f_id,0) clfid,".
                         "  d.f_num,d.f_grnd,d.f_count,".
                         //"  case when d.f_count=1 then o.f_sum else d.f_price end f_price,o.f_sum dsum,".
                         //"  ifnull(o.f_nds,0) dnds,d.f_ndssum, ".
                         "  ifnull(d.f_nds,0) dnds,d.f_ndssum, ".
                         //"  (select f_kod1c from ".DBPref."nomenk where f_nbd1c=".$row['nbd1c']." and f_id=top.f_nomenkid) ncode ".
                         "  case ".
                         "    when d.f_nomenk=0 then (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=top.f_nomenkid) and f_nbd1c=".$row['nbd1c']." limit 1) ".
                         "    else (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=d.f_nomenk) and f_nbd1c=".$row['nbd1c']." limit 1) ".
                         "  end ncode 
                       from ".DBPref."akts_details d 
                       left join ".DBPref."akts_details_opers do on do.f_akts_detailsid=d.f_id 
                       left join ".DBPref."spec_invoices o on o.f_id=do.f_operid 
                       left join ".DBPref."specs s on s.f_id=o.f_specid 
                       left join ".DBPref."dogs dd on dd.f_id=s.f_dogid 
                       left join ".DBPref."clients cl on cl.f_id=dd.f_contrid 
                       left join ".DBPref."typeopers top on top.f_id=o.f_idoper 
                       where d.f_aktid=".$row['f_id']." order by s.f_id";
              //if($_SESSION['loginid']==2)
              //  {echo $sql."<br>";}
              }
            elseif(($tdoc!=23)&&($tdoc!=25))
              {
              $sql  = "select o.f_orgdivision,s.f_id sfid,s.f_dirmain,o.f_isvozm,s.f_kod1cp,cl.f_kod1c,cl.f_id clfid,d.f_num,d.f_grnd,d.f_count,
                         case 
                           when ifnull(o.f_orgdivision,0)>0 then 
                             o.f_orgdivision
                           when o.f_parenttype=2 then 
                             (select f_orgdivision from ".DBPref."specs where f_id=o.f_specid)
                           else 0 
                         end orgdiv,
                         s.f_subtype lspecsubtype, #sdid 3594 
                         #sdid1583
                         case 
                           when o.f_idoper=385 then
                             getcbrate(o.f_val,s.f_perpravdt)
                           else 1
                         end kurscbpps,
                         case 
                           when o.f_idoper=385 then 1
                           else 0
                         end fromtovarvdolg,
                         #~sdid1583
                       d.f_price,(d.f_sum+d.f_ndssum) dsum,d.f_nds dnds,d.f_ndssum, ".
                       //" (select f_kod1c from ".DBPref."nomenk where f_nbd1c=".$row['nbd1c']." and f_id=top.f_nomenkid) ncode ".
                       "  case ".
                       "    when d.f_nomenk=0 then (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=top.f_nomenkid) and f_nbd1c=".$row['nbd1c']." limit 1) ".
                       "    else (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=d.f_nomenk) and f_nbd1c=".$row['nbd1c']." limit 1) ".
                       "  end ncode ".
                       "from ".DBPref."akts_details d,".
                       //" ".DBPref."akts_details_opers do,".
                       "  ".DBPref."spec_invoices o,".DBPref."specs s, ".
                       "  ".DBPref."dogs dd,".DBPref."clients cl,".DBPref."typeopers top ".
                       "where d.f_aktid=".$row['f_id']." and ".
                       //"  do.f_akts_detailsid=d.f_id and ".
                       "  o.f_id=".$row['f_operid']." and s.f_id=o.f_specid and ".
                       "  dd.f_id=s.f_dogid and cl.f_id=dd.f_contrid and top.f_id=o.f_idoper order by s.f_id";
              }
            //echo $sql."<br>";
            //if($_SESSION['loginid']==2)
            //  {echo $tdoc."|".$sql."<br>";}
            if(strlen($sql)>0)
              {
              $dstr = "";
              $res1 = $dbh->query($sql);
              //$retval = $retval."_2_".$sql."|";
              $sfid = -1;
              $lvozmtotal=0; //sdid 3594
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $lspecsubtype = $row1['lspecsubtype']; //sdid 3594
                $orgdiv  = $row1['orgdiv'];
                $sorgdiv = "";
                if($orgdiv>0)
                  {$sorgdiv = "{\"orgdivnum\":\"".$orgdiv."\"}";
                   $OP = getOPorgFrom1C($row['ofid'],$sorgdiv);}
                if($sfid!=$row1['sfid'])
                  {
                  if(strlen($dirs)>0){$dirs=$dirs.",";}
                  $dirs=$dirs.$row1['f_dirmain'];
                  $sfid=$row1['sfid'];
                  }
                //if(strlen($dirs)>0){$dirs=$dirs.",";}
                //$dirs=$dirs.$row1['f_dirmain'];
                if(($row1['f_isvozm']==1)||($row1['f_isvozm']==0)){$vozmc++;}
                if($row1['f_isvozm']==1) {$lvozmtotal++;} //sdid 3594
                if(strlen($dstr)>0){$dstr=$dstr.",";}
                $clkod1c = "-";
                if($row1['clfid']>0)
                  {$clkod1c = getClntInf(2,2,$row1['clfid'],0,$row['ofid'],2);}
                //sdid1583
                if($row1['fromtovarvdolg']==1)
                  {$kurscbpps      = $row1['kurscbpps'];
                   $fromtovarvdolg = 1;}
                //~sdid1583
                //sdid 3594
                $subconto1     = "";
                $subcontonu1   = "";
                $ndsacc        = 0;
                if($tdoc==7 && $lspecsubtype==2 && $isnovozm==1 && $mainnomenk!=22 && $mainnomenk!=0)
                  {
                  $subconto1     = "Подготовка документов";
                  $subcontonu1   = "Подготовка документов";
                  $ndsacc        = 1;  
                  }
                elseif($tdoc==7 && $isnovozm==1 && $mainnomenk==22)
                  {
                  $subconto1     = "Транспортно-экспедиторские услуги";
                  $subcontonu1   = "Транспортно-экспедиторские услуги";
                  $ndsacc        = 1;
                  }
                //~sdid 3594

                //sdid 3478
                $schz       = ""; // Счет затрат
                $schznu     = ""; // Счет затрат НУ
                $subconto   = ""; // Субконто
                $subcontonu = ""; // Субконто НУ
                if(isset($row1['bydog']))
                  {
                  $bydog = $row1['bydog'];
                  if($bydog>0) 
                    {
                    //$bydog=1;
                    $schz       = "91.02"; // Счет затрат
                    $schznu     = "91.02"; // Счет затрат НУ
                    $subconto   = "Расходы на  услуги банка"; // Субконто
                    $subcontonu = "Расходы на  услуги банка"; // Субконто НУ
                    } 
                  }
                //~sdid 3478
                $dstr = $dstr."{\"op\":\"".$OP."\",\"izvozm\":\"".$row1['f_isvozm']."\",\"speccontrid1C\":\"".$clkod1c."\",\"specid1C\":\"".$row1['f_kod1cp']."\",".
                          "\"detnum\":\"".$row1['f_num']."\",\"detdescr\":\"".str_replace("\"","",$row1['f_grnd'])."\",\"detnomenk\":\"".$row1['ncode'].
                          "\",\"detcol\":\"".(float)$row1['f_count']."\",".
                          "\"detprice\":\"".$row1['f_price']."\",".
                          //sdid1583
                          "\"kurscbpps\":\"".$row1['kurscbpps']."\",\"fromtovarvdolg\":\"".$row1['fromtovarvdolg']."\",".
                          //~sdid1583
                          "\"detsum\":\"".$row1['dsum']."\",\"detnds\":\"".$row1['dnds'].//"\",\"detndssum\":\"".$row1['f_ndssum'].
                          //sdid 3478
                            "\",\"schz\":\"$schz\",\"schznu\":\"$schznu\",\"subconto\":\"$subconto\",\"subcontonu\":\"$subcontonu\"". //sdid 3478
                          //~sdid 3478
                          //sdid 3594
                          ",\"subconto1\":\"".$subconto1."\",\"subcontonu1\":\"".$subcontonu1."\",\"ndsacc\":\"$ndsacc\"}";
                  //error_log("\n\ndstr = $dstr\n\n",0);
                          //~sdid 3594
                $kold++;
                }
              }
            //echo $dstr;
            //$retval = $retval."_3_".$dstr."|";
            //if($_SESSION['loginid']==2){echo "isvozm: $vozmc<br>";}
            //if($_SESSION['loginid']==2)
            //  {$retval = $retval."_3_".$kold."|".$dstr."|";echo $retval."<br>";}
            if($kold>0)
              {
              //$retval = $retval."_4_";
              $dstr = "[".$dstr."]";
              //$retval = $retval."_4_".$dstr."|";
              //if($_SESSION['loginid']==2){echo "isvozm: $vozmc<br>";}
              //  {$retval = $retval."_3_".$kold."|".$dstr."|";echo $retval."<br>";}
              if(strlen($row['num1c'])>1)//обновляем акт в 1С
                {
                //$retval = $retval."_5_".$dstr."|";
                //sdid1583
                $ret1c = c1c_addupdAkt(2,$okod1c,$ckod1c,$row['dkod1c'],str_replace("&bsol;","\\",($row['comm'].". ".$dirs)),$row['num'],substr($row['dt'],0,10),$row['num1c'],substr($row['dt1c'],0,10),$row['dsum'],$row['dval'],
                    (int)$row['vnds'],$dstr,$vozmc,1,$row['nbd1c'],$OP,$row['f_c1guid'],(int)$tdoc,$fromtovarvdolg,$kurscbpps);
                //~sdid1583
                if($ret1c['retval']=="true")
                  {
                  $retstatus = 0;
                  $retc1guid = "";
                  if(isset($ret1c['status']))
                    {
                    if($ret1c['status']==1)
                      {$retstatus = 7;$retval = "Документ в 1С выгружен, но НЕ проведен. ";}
                    elseif($ret1c['status']==2)
                      {$retstatus = 8;$retval = "Выгружено в 1С, проведено. ";}
                    else
                      {$retval = "Ошибка выгрузки в 1С. ";}
                    if(isset($ret1c['c1guid']))
                      {$retc1guid = $ret1c['c1guid'];}
                    }
                  if($retstatus>0)
                    {
                    $kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>$retstatus,'f_kod1c'=>$ret1c['msg']);
                    if(isset($ret1c['dt']))
                      {
                      $ndt = substr($ret1c['dt'],6,4)."-".substr($ret1c['dt'],3,2)."-".substr($ret1c['dt'],0,2);
                      $kai['f_dt1c']=$ndt;
                      }
                    if(strlen($retc1guid)>0)
                      {$kai['f_c1guid']=$retc1guid;}
                    $ar   = json_decode(editRowTbl($kai), true);
                    //var_dump($ar);
                    if($ar[0]=="true")
                      {$retval = $retval." Документ обновлен в системе. ";}
                    else
                      {$retval = $retval." Ошибка обновления докмуента в системе. ";}
                    //$sql = "update ".DBPref."akts set f_kod1c='".$retval['msg']."',f_num='".$retval['msg']."',f_status=8 where f_id=".$row['f_id'];
                    //$dbh->exec($sql);
                    if(isset($ret1c['descr'])){$descr=$descr.$ret1c['descr'];}
                    $retval = $retval." ".$descr;
                    $retv = 1;
                    }

                  //$retval = $retval."_6_";
                  //$retval = "Обновили и провели";
                  //$kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>8);
                  //$ar   = editRowTbl($kai);
                  //if($ar[0]=="true")
                  //  {
                  //  $retval = $retval.". Выставили статус.";
                  //  }
                  //$retv = 1;
                  }
                else
                  {
                  //$retval = $retval."_7_";
                  $retval = "Ошибка обновления/проведения: ".$ret1c['msg'];
                  }
                }
              else//создаем акт в 1С
                {
                //$retval = $retval."_8_";
                //sdid1583
                $ret1c = c1c_addupdAkt(1,$okod1c,$ckod1c,$row['dkod1c'],str_replace("&bsol;","\\",($row['comm'].". ".$dirs)),$row['num'],$row['dt'],$row['num1c'],
                    $row['dt1c'],$row['dsum'],$row['dval'],$row['vnds'],$dstr,$vozmc,1,$row['nbd1c'],$OP,$row['f_c1guid'],$tdoc,$fromtovarvdolg,$kurscbpps);
                //~sdid1583
                if($ret1c['retval']=="true")
                  {
                  $retstatus = 0;
                  $retc1guid = "";
                  if(isset($ret1c['status']))
                    {
                    if($ret1c['status']==1)
                      {$retstatus = 7;$retval = "Документ в 1С выгружен, но НЕ проведен. ";}
                    elseif($ret1c['status']==2)
                      {$retstatus = 8;$retval = "Выгружено в 1С, проведено. ";}
                    else
                      {$retval = "Ошибка выгрузки в 1С. ";}
                    if(isset($ret1c['c1guid']))
                      {$retc1guid = $ret1c['c1guid'];}
                    }
                  if($retstatus>0)
                    {
                    $ndt  = substr($ret1c['dt'],6,4)."-".substr($ret1c['dt'],3,2)."-".substr($ret1c['dt'],0,2);
                    $kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>$retstatus,'f_kod1c'=>$ret1c['msg'],'f_dt1c'=>$ndt);
                    if(strlen($retc1guid)>0)
                      {$kai['f_c1guid']=$retc1guid;}
                    $ar   = json_decode(editRowTbl($kai), true);
                    //var_dump($ar);
                    if($ar[0]=="true")
                      {$retval = $retval." Документ обновлен в системе. ";}
                    else
                      {$retval = $retval." Ошибка обновления докмуента в системе. ";}
                    //$sql = "update ".DBPref."akts set f_kod1c='".$retval['msg']."',f_num='".$retval['msg']."',f_status=8 where f_id=".$row['f_id'];
                    //$dbh->exec($sql);
                    if(isset($ret1c['descr'])){$descr=$descr.$ret1c['descr'];}
                    $retval = $retval." ".$descr;
                    $retv = 1;
                    }
                  }
                else
                  {
                  //$retval = $retval."_10_";
                  $retval = "Ошибка создания/проведения: ".$ret1c['msg'];
                  }
                }
              }
            //sdid 1275
            //sdid 2242
            //elseif(($tdoc==23)||($tdoc==25)||((($row['f_dogid']==5286)||($row['f_dogid']==5287))&&($row['f_contrid']==2554)))
            //elseif(($tdoc==23)||($tdoc==25)||((($row['f_dogid']==5286)||($row['f_dogid']==5287))&&($row['f_contrid']==2554)) || (($row['f_dogtype']==4)&&($row['f_matype']==5)) ) //sdid2542
            elseif(($tdoc==23)||($tdoc==25)||((($row['f_dogid']==5286)||($row['f_dogid']==5287)||($row['f_dogid']==7916))&&($row['f_contrid']==2554)) || (($row['f_dogtype']==4)&&($row['f_matype']==5)) ) //sdid2542
            //~sdid 2242
            //~sdid 1275
              {
              $kold = 0;
              $dstr = "";
              if(($row['f_operid']>0)&&($row['adcount']==0))
                {
                $orgdiv  = $row['orgdiv'];
                $sorgdiv = "";
                if($orgdiv>0)
                  {$sorgdiv = "{\"orgdivnum\":\"".$orgdiv."\"}";
                   $OP   = getOPorgFrom1C($row['ofid'],$sorgdiv);}
                $dirs    = $row['dirmain'];
                $sfid    = $row['sfid'];
                $clkod1c = $row['ckod1c'];
                $vozmc   = 0;
                if(isset($row['isvozm'])){if(($row['isvozm']==1)||($row['isvozm']==0)){$vozmc=1;}}
                if(isset($row['oisvozm'])){if(($row['oisvozm']==1)||($row['oisvozm']==0)){$vozmc=1;}}
                //sdid 1275
                $detdescr  = $row['oname'];
                $detnomenk = "";
                //sdid 2242
                //if((($row['f_dogid']==5286)||($row['f_dogid']==5287))&&($row['f_contrid']==2554)){$detnomenk = "00000000939";$detdescr = "Страхование груза";}
                //if(((($row['f_dogid']==5286)||($row['f_dogid']==5287))&&($row['f_contrid']==2554)) || (($row['f_dogtype']==4)&&($row['f_matype']==5)))                        //sdid2542
                if(((($row['f_dogid']==5286)||($row['f_dogid']==5287)||($row['f_dogid']==7916))&&($row['f_contrid']==2554)) || (($row['f_dogtype']==4)&&($row['f_matype']==5))) //sdid2542
                  {$detnomenk = "00000000939";$detdescr = "Страхование груза";}
                //~sdid 2242
                //if($_SESSION['loginid']==2){echo "isvozm: $vozmc<br>";}
                $dstr = $dstr."{\"op\":\"".$OP."\",\"izvozm\":\"".$vozmc."\",\"speccontrid1C\":\"".$row['speccntr1c']."\",\"specid1C\":\"".$row['spec1c']."\",".
                          "\"detnum\":\"1\",\"detdescr\":\"".str_replace("\"","",$detdescr)."\",\"detnomenk\":\"".$detnomenk."\",\"detcol\":1,".
                          "\"detprice\":\"".$row['dsum']."\",".
                          "\"detsum\":\"".$row['dsum']."\",\"detnds\":\"".$row['vnds'].//"\",\"detndssum\":\"".$row1['f_ndssum'].
                          "\"}";
                //~sdid 1275
                $kold++;
                }
              elseif(($row['adcount']>0))
                {
                $sql = "select o.f_orgdivision, 
                          case 
                            when ifnull(o.f_orgdivision,0)>0 then 
                              o.f_orgdivision
                            when o.f_parenttype=2 then 
                              (select f_orgdivision from ".DBPref."specs where f_id=o.f_specid)
                            else 0 
                          end orgdiv,
                          case 
                            when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then do.f_sum 
                              else o.f_sum 
                            end dsum,
                          case 
                            when (select sum(f_sum) from ".DBPref."akts_details_opers where f_akts_detailsid=d.f_id)>0 then d.f_price 
                            when d.f_count=1 then o.f_sum 
                            else d.f_price 
                          end f_price,
                          ifnull(s.f_id,0) sfid,s.f_dirmain,ifnull(o.f_isvozm,3) f_isvozm,s.f_kod1cp,cl.f_kod1c,ifnull(cl.f_id,0) clfid,
                          d.f_num,
                          case 
                            when ".$tdoc."=25 and ifnull(o.f_invoiceid,0)>0 then 
                              ifnull((select concat('Товар по инвойсу № ',sch.f_num) from veda_schets sch where sch.f_id=o.f_invoiceid),'')
                            when ".$tdoc."=25 then 'Товар по инвойсу №'
                            else d.f_grnd
                          end f_grnd,
                          #d.f_grnd,
                          d.f_count,
                          ifnull(d.f_nds,0) dnds,d.f_ndssum, 
                          case 
                            when d.f_nomenk=0 then 
                              (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=top.f_nomenkid) and 
                                 f_nbd1c=".$row['nbd1c']." limit 1) 
                            else (select f_kod1c from ".DBPref."nomenk where f_name in (select f_name from ".DBPref."nomenk where f_id=d.f_nomenk) and 
                                    f_nbd1c=".$row['nbd1c']." limit 1) 
                          end ncode 
                       from ".DBPref."akts_details d 
                       left join ".DBPref."akts_details_opers do on do.f_akts_detailsid=d.f_id 
                       left join ".DBPref."spec_invoices o on o.f_id=do.f_operid 
                       left join ".DBPref."specs s on s.f_id=o.f_specid 
                       left join ".DBPref."dogs dd on dd.f_id=s.f_dogid 
                       left join ".DBPref."clients cl on cl.f_id=dd.f_contrid 
                       left join ".DBPref."typeopers top on top.f_id=o.f_idoper 
                       where d.f_aktid=".$row['f_id']." order by s.f_id";
                $res1 = $dbh->query($sql);
                $sfid = -1;
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  $orgdiv  = $row1['orgdiv'];
                  $sorgdiv = "";
                  if($orgdiv>0)
                    {$sorgdiv = "{\"orgdivnum\":\"".$orgdiv."\"}";
                     $OP = getOPorgFrom1C($row['ofid'],$sorgdiv);}
                  if($sfid!=$row1['sfid'])
                    {
                    if(strlen($dirs)>0){$dirs=$dirs.",";}
                    $dirs=$dirs.$row1['f_dirmain'];
                    $sfid=$row1['sfid'];
                    }
                  if(($row1['f_isvozm']==1)||($row1['f_isvozm']==0)){$vozmc++;}
                  if(strlen($dstr)>0){$dstr=$dstr.",";}
                  $clkod1c = "-";
                  if($row1['clfid']>0)
                    {$clkod1c = getClntInf(2,2,$row1['clfid'],0,$row['ofid'],2);}
                  $dstr = $dstr."{\"op\":\"".$OP."\",\"izvozm\":\"".$row1['f_isvozm']."\",\"speccontrid1C\":\"".$clkod1c."\",\"specid1C\":\"".$row1['f_kod1cp']."\",".
                            "\"detnum\":\"".$row1['f_num']."\",\"detdescr\":\"".str_replace("\"","",$row1['f_grnd'])."\",\"detnomenk\":\"\",".
                            "\"detcol\":\"".(float)$row1['f_count']."\",\"detprice\":\"".$row1['f_price']."\",".
                            "\"detsum\":\"".$row1['dsum']."\",\"detnds\":\"".$row1['dnds'].//"\",\"detndssum\":\"".$row1['f_ndssum'].
                            "\"}"; 
                  $kold++;
                  }
                }
              if($kold>0)
                {
                $dstr   = "[".$dstr."]";
                $actnum = 1;if(strlen($row['num1c'])>0){$actnum = 2;}
                //if($_SESSION['loginid']==2)
                //  {echo $actnum."|".$dstr."<br>";}
                $ret1c  = c1c_addupdAkt($actnum,$okod1c,$ckod1c,$row['dkod1c'],str_replace("&bsol;","\\",($row['comm'].". ".$dirs)),$row['num'],$row['dt'],
                                        $row['num1c'],substr($row['dt1c'],0,10),$row['dsum'],$row['dval'],$row['vnds'],$dstr,$vozmc,1,$row['nbd1c'],$OP,
                                        $row['f_c1guid'],$tdoc);
                //if($_SESSION['loginid']==2)
                //  {echo "$ret1c<br>";var_dump($ret1c);}
                if($ret1c['retval']=="true")
                  {
                  $retstatus = 0;
                  $retc1guid = "";
                  $descr     = "";
                  if(isset($ret1c['status']))
                    {
                    if($ret1c['status']==1)
                      {$retstatus = 7;$retval = "Документ в 1С выгружен, но НЕ проведен. ";}
                    elseif($ret1c['status']==2)
                      {$retstatus = 8;$retval = "Выгружено в 1С, проведено. ";}
                    else
                      {$retval = "Ошибка выгрузки в 1С. ";}
                    if(isset($ret1c['c1guid']))
                      {$retc1guid = $ret1c['c1guid'];}
                    }
                  if($retstatus>0)
                    {
                    $ndt  = substr($ret1c['dt'],6,4)."-".substr($ret1c['dt'],3,2)."-".substr($ret1c['dt'],0,2);
                    $kai  = Array('curtbl'=>83,'curidx'=>$row['f_id'],'f_status'=>$retstatus,'f_kod1c'=>$ret1c['msg'],'f_dt1c'=>$ndt);
                    if(strlen($retc1guid)>0)
                      {$kai['f_c1guid']=$retc1guid;}
                    $ar   = json_decode(editRowTbl($kai), true);
                    if($ar[0]=="true")
                      {$retval = $retval." Документ обновлен в системе. ";}
                    else
                      {$retval = $retval." Ошибка обновления документа в системе. ";}
                    if(isset($ret1c['descr'])){$descr=$descr.$ret1c['descr'];}
                    $retval = $retval." ".$descr;
                    $retv = 1;
                    }
                  }
                else
                  {$retval = "Ошибка выгрузки в 1С: ".$ret1c['msg'];}
                }
              }
            else
              {
              $retval = "По акту отсутствует детализация";
              }
            }
          else
            {
            $retval = "Тип договора не соответствуем типу выгружаемого документа";
            }
          }
        else
          {
          $retval = "Не найден документ соответствующий условиям";
          }
        }
      else 
        {$retval = "Тип документа не определен для выгрузки в 1С";}
      }
    else
      {$retval = "Не коррректно заданы параметры";}
    if($retv==1){$retval="[true,\"".$retval."\"]";}
    else{$retval="[false,\"".$retval."\"]";}
    return $retval;
    }
  catch (Exception $e)
    {return "[false,\"Ошибка: ".$e->getMessage()."\"]";}
  }

//собрать информацию по форме страхования
function getInsureFormVals($djspar)
{
    $retval = "";
    try
    {
        $insid = 0;//ИД страховки
        $insdogid = 0;//договор со страховой компанией
        $insnum  = "";//номер стразового полиса
        $inssump = 0;//сумма предварителньой страховки
        $valinssump = 643;//валюта предварителньой страховки
        $inssum = 0;//сумма страховки
        $inssumval = 0;//валюта страховки
        $inssumpr = 0;//сумма премии
        $inssumprrf = 0;
        $inssumprdr = 0;
        $valinssumpr = 643;//валюта премии
        $curscb = 1;//курс ЦБ
        $curscbdt = date('d.m.Y');//дата курса ЦБ
        $ratepr = 0;//ставка премии
        $raterf = 0;//ставка премии по РФ
        $ratedr = 0;//ставка премии до РФ
        $chtovar = 0;//учитывать в страховке стоимость товара
        $chtp = 0;//учитывать в страховке стоимость таможенных платежей
        $chdost = 0;//учитывать в страховке стоимость доставки
        $chuv = 0;//учитывать в страховке упущенную выгоду
        $inscom = "";//комментарий страховки
        $tovarss = "";
        $tovars  = 0;
        $tpss = "";
        $tps  = 0;
        $dostss = "";
        $dosts  = 0;
        $uvss = "";
        $uvs  = 0;
        $dtid = 0;
        //echo $djspar."<br>";
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            //echo $djspar."<br>";
            $insid = 0;
            if(isset($djspar['insid'])){$insid = $djspar['insid'];}
            if(strlen($insid)==0){$insid=0;}
            if(isset($djspar['insdogid'])){$insdogid = $djspar['insdogid'];}
            if(isset($djspar['insnum'])){$insnum = $djspar['insnum'];}
            if(isset($djspar['inssump'])){$inssump = $djspar['inssump'];}
            if(isset($djspar['valinssump'])){$valinssump = $djspar['valinssump'];}
            if(isset($djspar['inssumpr'])){$inssumpr = $djspar['inssumpr'];}
            if(isset($djspar['valinssumpr'])){$valinssumpr = $djspar['valinssumpr'];}
            if(isset($djspar['curscb'])){$curscb = $djspar['curscb'];}
            if(isset($djspar['curscbdt'])){$curscbdt = $djspar['curscbdt'];}
            if(isset($djspar['ratepr'])){$ratepr = $djspar['ratepr'];}
            if(isset($djspar['raterf'])){$raterf = $djspar['raterf'];}
            if(isset($djspar['ratedr'])){$ratedr = $djspar['ratedr'];}
            if(isset($djspar['chtovar'])){$chtovar = $djspar['chtovar'];}
            if(isset($djspar['chtp'])){$chtp = $djspar['chtp'];}
            if(isset($djspar['chdost'])){$chdost = $djspar['chdost'];}
            if(isset($djspar['chuv'])){$chuv = $djspar['chuv'];}
            if(isset($djspar['inscom'])){$inscom = $djspar['inscom'];}
            if(isset($djspar['inssum'])){$inssum = $djspar['inssum'];}
            if(isset($djspar['inssumval'])){$inssumval = $djspar['inssumval'];}
            if(isset($djspar['tovarss'])){$tovarss = $djspar['tovarss'];}
            if(isset($djspar['tpss'])){$tpss = $djspar['tpss'];}
            if(isset($djspar['dostss'])){$dostss = $djspar['dostss'];}
            if(isset($djspar['uvss'])){$uvss = $djspar['uvss'];}
            if(isset($djspar['inssumprrf'])){$inssumprrf = $djspar['inssumprrf'];}
            if(isset($djspar['inssumprdr'])){$inssumprdr = $djspar['inssumprdr'];}
            if(isset($djspar['dtid'])){$dtid = $djspar['dtid'];}
            //echo $curscbdt."<br>";
            $curscbdt = str_replace(" ","0",str_replace(".","-",$curscbdt));
            $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
            $dbh->exec('SET CHARACTER SET utf8');
            //echo $insdogid."_".$ratepr."<br>";
            if(($insdogid>0)&&($ratepr==0))
            {
                $sql = "select f_ctgtype,f_valstr from ".DBPref."categs where f_objecttype=3 and f_objectid=".$insdogid." and f_ctgtype in (2,3,4)";
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                while($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    if($row['f_ctgtype']==2){$ratepr=(float)$row['f_valstr'];}
                    if($row['f_ctgtype']==3){$raterf=(float)$row['f_valstr'];}
                    if($row['f_ctgtype']==4){$ratedr=(float)$row['f_valstr'];}
                }
            }
            if($insdogid>0)
            {
                $sql = "select ifnull(f_ensnum,'') f_ensnum from ".DBPref."ensures where f_id=".$insid;
                //error_log("\n$sql\n",0);
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    if(strlen($row['f_ensnum']<2))
                    {
                        $sql = "select f_dogname from ".DBPref."dogs where f_id=".$insdogid;
                        $res = $dbh->query($sql);
                        if($row = $res->fetch(PDO::FETCH_ASSOC))
                        {
                            $insnum = $row['f_dogname']."/";
                        }
                    }
                }
            }
            //if(($curscb==1)&&($valinssump!=643))
            //  {
            $curscb=getCBRate($valinssump,$curscbdt);
            //echo $curscb;
            //  }
            //if(strlen($inscom)==0)
            //  {
            //echo $insid;
            $cspecid = 0;
            if($insid>0)
            {
                $sql = "select f_objid,f_com from ".DBPref."ensures where f_objtype=2 and f_id=".$insid." ";
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    $inscom  = str_replace("\n","\\n",str_replace("\"","&quot;",$row['f_com']));
                    $cspecid = $row['f_objid'];
                }
            }
            //echo $inscom."-com<br>";
            if(strlen($inscom)<2)
            {
                $sql = "select concat((select f_name from ".DBPref."spr where f_type=38 and f_num=r.f_p1country),'/',".
                    "              case when r.f_p1pointtype=2 then (select f_name from ".DBPref."spr where f_type=40 and f_num=r.f_p1point) else (select f_name from ".DBPref."spr where f_type=39 and f_num=r.f_p1city) end,' - ',".
                    "              (select f_name from ".DBPref."spr where f_type=38 and f_num=r.f_p2country),'/',".
                    "              case when r.f_p2pointtype=2 then (select f_name from ".DBPref."spr where f_type=40 and f_num=r.f_p2point) else (select f_name from ".DBPref."spr where f_type=39 and f_num=r.f_p2city) end,' - ',".
                    "              (select f_name from ".DBPref."spr where f_type=42 and f_num=r.f_routetype)) route ".
                    "from ".DBPref."routes r where r.f_postid=(select f_postid from ".DBPref."specs where f_id=(select f_objid from ".DBPref."ensures where f_objtype=2 and f_id=".$insid.")) order by r.f_parentid";
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                while($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    if(strlen($inscom>0)){$inscom=$inscom."; ";}
                    $inscom = $inscom.$row['route'];
                }
            }
            //  }
            //echo $inssum."<br>";
            //if($inssum==0)//считаем суммы
            //  {
            //echo $inscom;
            $ccurs=1;
            //echo $inscom;
            //$sql = "select f_faktprice,f_faktpriceval from ".DBPref."dt where f_specid=(select f_objid from ".DBPref."ensures where f_objtype=2 and f_id=".$insid.")";
            if(($dtid==0)&&($cspecid>0))
            {
                $sql = "select f_id from ".DBPref."dt where f_specid=".$cspecid." or f_specid in ".
                    "  (select f_id from ".DBPref."specs where f_parentspecid=".$cspecid.")";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    $dtid = $row['f_id'];
                }
            }
            $sql = "select f_faktprice,f_faktpriceval from ".DBPref."dt where f_id=".$dtid;
            //echo $sql."<br>";
            $res = $dbh->query($sql);
            while($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                if(($row['f_faktpriceval']!=$valinssump)&&($row['f_faktpriceval']!=643))
                {$ccurs=getCBRate($row['f_faktpriceval'],$curscbdt);}
                else{$ccurs=$curscb;}
                $tovars  = $tovars+round(($row['f_faktprice']*$ccurs),2);
                if(strlen($tovarss)>0){$tovarss=$tovarss."&plus;";}
                $tovarss = $tovarss.round(($row['f_faktprice']*$ccurs),2);
            }
            if($chtovar==0){$tovars=0;}

            $sql = "select f_sumstr,f_sumstrval,f_sumipr,f_sumiprval,f_sumspr,f_sumsprval,f_sumndsr,f_sumndsrval ".
                //"from ".DBPref."dt where f_specid=".
                //"  (select f_objid from ".DBPref."ensures where f_objtype=2 and f_id=".$insid.")";
                "from ".DBPref."dt where f_id=".$dtid;
            //echo $sql."<br>";
            $res = $dbh->query($sql);
            while($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                $ccurs=1;
                //echo $row['f_sumstrval'];
                if(($row['f_sumstrval']!=$valinssump)&&($row['f_sumstrval']!=643))
                {$ccurs=getCBRate($row['f_sumstrval'],$curscbdt);}
                elseif($row['f_sumstrval']==643){$ccurs=1;}
                else{$ccurs=$curscb;}
                $tps  = $tps+round(($row['f_sumstr']*$ccurs),2);
                if(strlen($tpss)>0){$tpss=$tpss."&plus;";}
                $tpss = $tpss.$tps;
                $ccurs=1;
                if(($row['f_sumiprval']!=$valinssump)&&($row['f_sumiprval']!=643))
                {$ccurs=getCBRate($row['f_sumiprval'],$curscbdt);}
                elseif($row['f_sumiprval']==643){$ccurs=1;}
                else{$ccurs=$curscb;}
                $tps  = $tps+round(($row['f_sumipr']*$ccurs),2);
                $tpss = $tpss."&plus;".round(($row['f_sumipr']*$ccurs),2);
                $ccurs=1;
                if(($row['f_sumsprval']!=$valinssump)&&($row['f_sumsprval']!=643))
                {$ccurs=getCBRate($row['f_sumsprval'],$curscbdt);}
                elseif($row['f_sumsprval']==643){$ccurs=1;}
                else{$ccurs=$curscb;}
                $tps  = $tps+round(($row['f_sumspr']*$ccurs),2);
                $tpss = $tpss."&plus;".round(($row['f_sumspr']*$ccurs),2);
                $ccurs=1;
                if(($row['f_sumndsrval']!=$valinssump)&&($row['f_sumndsrval']!=643))
                {$ccurs=getCBRate($row['f_sumndsrval'],$curscbdt);}
                elseif($row['f_sumndsrval']==643){$ccurs=1;}
                else{$ccurs=$curscb;}
                $tps  = $tps+round(($row['f_sumndsr']*$ccurs),2);
                $tpss = $tpss."&plus;".round(($row['f_sumndsr']*$ccurs),2);
            }
            if($chtp==0){$tps=0;}

            $sql = "select f_sum,f_val from ".DBPref."spec_invoices where f_winsures=1 and f_parenttype=2 and f_specid=".
                "  (select f_objid from ".DBPref."ensures where f_objtype=2 and f_id=".$insid.")";
            //echo $sql."<br>";
            $scdosts = 0;
            $res = $dbh->query($sql);
            while($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                $ccurs=1;
                if(($row['f_val']!=$valinssump)&&($row['f_val']!=643))
                {$ccurs=getCBRate($row['f_val'],$curscbdt);}
                elseif($row['f_val']!=643)
                {$ccurs=getCBRate($row['f_val'],$curscbdt);}
                elseif($row['f_val']==643){$ccurs=1;}
                else
                {$ccurs=$curscb;}
                $scdosts = round(($row['f_sum']*$ccurs),2);
                $dosts   = $dosts+$scdosts;
                //echo $dosts."<br>";
                if(strlen($dostss)>0){$dostss=$dostss."&plus;";}$dostss=$dostss.$scdosts;
            }
            if($chdost==0){$dosts=0;}

            $uvs  = round(($tovars*0.1),2);
            $uvss = "".$uvs."";
            if($chuv==0){$uvs=0;}
            $inssum = $tovars+$tps+$dosts+$uvs;
            //  }
            //if($inssumpr==0)
            //  {
            //if($inssum>0)
            //  {
            $inssumpr   = round(($inssum*$ratepr/100),2);
            //$inssumprdr = round(($inssumpr*$ratedr),2);
            $inssumprdr = round(($inssum*$ratedr/100),2);
            $inssumprrf = $inssumpr-$inssumprdr;
            //  }
            //  }
        }
        $vinssum=round($inssum/getCBRate($inssumval,$curscbdt),2);
        $inssum =round($vinssum*getCBRate($inssumval,$curscbdt),2);
        $sdtid = "";
        if($dtid>0){$sdtid = "\"dtid\":\"".$dtid."\",";}
        $retval = "{".$sdtid."\"insnum\":\"".$insnum."\",\"vinssum\":\"".$vinssum."\",\"inssum\":\"".$inssum."\",\"inssumpr\":\"".$inssumpr."\",\"inssumprrf\":\"".$inssumprrf."\",\"inssumprdr\":\"".$inssumprdr."\",\"curscb\":\"".$curscb."\",\"ratepr\":\"".$ratepr."\",\"raterf\":\"".$raterf."\",".
            "\"ratedr\":\"".$ratedr."\",\"inscom\":\"".str_replace("\n"," ",str_replace("\t"," ",$inscom))."\",\"tovarss\":\"".$tovarss."\",\"tpss\":\"".$tpss."\",\"dostss\":\"".$dostss."\",\"uvss\":\"".$uvss."\"}";
        //return json_encode($retval);
        return $retval;
    }
    catch (Exception $e)
    {return "";}
}

//найти информацию по клиенту
function getClntInf($what,$from,$parm,$isorg,$orgid,$tw)
{
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    $dbh->exec('SET CHARACTER SET utf8');
    if($tw==1){$retval = 0;}
    elseif($tw==2){$retval = "";}
    $tbase="";$tbased="";
    if($orgid>0)
    {
        $sql = "select f_typecode from ".DBPref."client_codes where f_contrid=".$orgid;
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC)){$tbase = " and cc.f_typecode=".$row['f_typecode'];$tbased = " and cc.f_nbd1c=".$row['f_typecode'];}
    }
    elseif($orgid<0)
    {$tbase = " and cc.f_typecode=".(-1*$orgid);$tbased = " and cc.f_nbd1c=".(-1*$orgid);}
    $sql = "";
    if($what==1)//ищем ИД клиента
    {
        if($from==1)//ищем по коду 1С
        {
            $sql = "select cc.f_contrid rv from ".DBPref."client_codes cc,".DBPref."clients c where cc.f_contrid=c.f_id and cc.f_code='".$parm."' and c.f_isourorg=".$isorg." ".$tbase;
        }
    }
    elseif($what==2)//ищем код 1С
    {
        if($from==2)//ищем по ИД
        {
            $sql = "select cc.f_code rv from ".DBPref."client_codes cc,".DBPref."clients c where cc.f_contrid=c.f_id and cc.f_contrid=".$parm." and c.f_isourorg=".$isorg." ".$tbase;
        }
    }
    //echo $sql."<br>";
    if(strlen($sql)>0)
    {
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$retval = $row['rv'];}
        else
        {
            //echo $what."<br>";
            $sql = "";
            if($what==1)//ищем ИД клиента по доп кодам
            {
                $sql = "select cc.f_clntid rv from ".DBPref."client_dopcodes1c cc,".DBPref."clients c where cc.f_clntid=c.f_id and cc.f_code1c='".$parm."' ".$tbased;
            }
            elseif($what==2)//ищем код 1С клиента по ИД
            {
                $sql = "select cc.f_code1c rv from ".DBPref."client_dopcodes1c cc,".DBPref."clients c where cc.f_clntid=c.f_id and cc.f_clntid='".$parm."' ".$tbased;
            }
            if(strlen($sql)>0)
            {
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                {$retval = $row['rv'];}
            }
        }
    }
    //echo $retval."<br>";
    return $retval;
}

//echo "begin<br>";
//echo $c1cwsdl."<br>";
//заполнить временную таблицу маршрута
function getTblRoute($postid,$logid,$dbh,$specid=0)
  {
  $mbreak=0;$mkol=0;
  //$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
  //указываем, мы хотим использовать utf8
  //$dbh->exec('SET CHARACTER SET utf8');
  $sql = "CREATE TEMPORARY TABLE tmp_route".$logid." 
            (f_id int(11),f_pid int(11),f_level int(11),f_ktkid varchar(16),f_pdt date,f_pcountry int(11),f_pcity int(11),
            f_ppoint int(11),f_ppointtype int(11),f_routetype int(11),f_status int(11),f_piscustom int(11),f_isdopp int(11),f_parentid int(11)) DEFAULT CHARSET=utf8";
  //echo $sql."<br>";
  $dbh->exec($sql);
  //if($specid>0)
  //  {
  //  $sql = "select r.f_id,r.f_parentid,r.f_routetype,r.f_status,r.f_p2iscustom,
  //            case when r.f_p1dt is null or r.f_p1dt='0000-00-00' then r.f_p1dtp else r.f_p1dt end p1dt,
  //            case when r.f_p2dt is null or r.f_p2dt='0000-00-00' then r.f_p2dtp else r.f_p2dt end p2dt,
  //            r.f_p1country,r.f_p1city,r.f_p1point,r.f_p1pointtype, 
  //            r.f_p2country,r.f_p2city,r.f_p2point,r.f_p2pointtype 
  //          from ".DBPref."routes r,".DBPref."routes_spec s
  //          where r.f_id=s.f_routeid and s.f_specid=".$specid." and r.f_postid=".$postid." and
  //            r.f_parentid=ifnull((select min(f_parentid) from ".DBPref."routes where f_id in 
  //              (select f_routeid from ".DBPref."routes_spec where f_specid=".$specid.")),0)";
  //  }
  //else
  //  {
//    $sql = "select r.f_id,r.f_parentid,r.f_routetype,r.f_status,r.f_p2iscustom,
//              case when r.f_p1dt is null or r.f_p1dt='0000-00-00' then r.f_p1dtp else r.f_p1dt end p1dt,
//              case when r.f_p2dt is null or r.f_p2dt='0000-00-00' then r.f_p2dtp else r.f_p2dt end p2dt,
//              r.f_p1country,r.f_p1city,r.f_p1point,r.f_p1pointtype, 
//              r.f_p2country,r.f_p2city,r.f_p2point,r.f_p2pointtype 
//            from ".DBPref."routes r 
//            where r.f_postid=".$postid." and r.f_parentid=ifnull((select min(f_parentid) from ".DBPref."routes where f_postid=".$postid."),0)";
    // sdid 1429
    $sql = "select r.f_id,r.f_parentid,r.f_routetype,r.f_status,r.f_p2iscustom,
              case when r.f_p1dt is null or r.f_p1dt='0000-00-00' then '' else r.f_p1dt end p1dt,
              case when r.f_p2dt is null or r.f_p2dt='0000-00-00' then '' else r.f_p2dt end p2dt,
              r.f_p1country,r.f_p1city,r.f_p1point,r.f_p1pointtype, 
              r.f_p2country,r.f_p2city,r.f_p2point,r.f_p2pointtype 
            from ".DBPref."routes r 
            where r.f_postid=".$postid." and r.f_parentid=ifnull((select min(f_parentid) from ".DBPref."routes where f_postid=".$postid."),0)";
    // ~sdid 1429
  //  }
  //echo $sql."<br>";
  $res = $dbh->query($sql);
  while(($row = $res->fetch(PDO::FETCH_ASSOC))&&($mbreak==0))
    {
    $clevel=0;
    //echo "route<br>";
    $sql = "insert into tmp_route".$logid." ".
        "(f_id,f_pid,f_level,f_pdt,f_pcountry,f_pcity,f_ppoint,f_ppointtype,f_routetype,f_isdopp) values (".
        $row['f_id'].",".$row['f_parentid'].",".$clevel.",'".
        $row['p1dt']."',".$row['f_p1country'].",".$row['f_p1city'].",".$row['f_p1point'].",".$row['f_p1pointtype'].",".
        $row['f_routetype'].",1)";
    //echo $sql."<br>";
    $dbh->exec($sql);
    $clevel++;
    $sql = "insert into tmp_route".$logid." ".
        "(f_id,f_pid,f_level,f_pdt,f_pcountry,f_pcity,f_ppoint,f_ppointtype,f_routetype,f_status,f_piscustom,f_isdopp) values (".
        $row['f_id'].",".$row['f_parentid'].",".$clevel.",'".
        $row['p2dt']."',".$row['f_p2country'].",".$row['f_p2city'].",".$row['f_p2point'].",".$row['f_p2pointtype'].",".
        $row['f_routetype'].",".$row['f_status'].",".$row['f_p2iscustom'].",0)";
    //echo $sql."<br>";
    $dbh->exec($sql);
    $kall=1;$clevel++;$cpar=$row['f_id'];$ckolb=0;$breaktmpr=0;
    while(($kall==1)&&($breaktmpr==0)&&($mbreak==0))
      {
      $k=2;$fldd=",r.f_routetype,r.f_status,r.f_p2iscustom ";$fldd1=",f_status,f_piscustom";
      $flds = "r.f_id,r.f_parentid,r.f_routetype,".
          //sdid 1664
          //"case when r.f_p".$k."dt is null or r.f_p".$k."dt='0000-00-00' then r.f_p".$k."dtp else r.f_p".$k."dt end pdt,".
          "r.f_p".$k."dt pdt,".
          //~sdid 1664
          "r.f_p".$k."country pcountry,r.f_p".$k."city pcity,r.f_p".$k."point ppoint,r.f_p".$k."pointtype ppointtype ".$fldd;
      //if($specid>0)
      //  {$sql1 = "select ".$flds." from ".DBPref."routes r,".DBPref."routes_spec s 
      //            where r.f_parentid=".$cpar." and r.f_postid=".$postid." and r.f_id=s.f_routeid and s.f_specid=".$specid." ";}
      //else
      //  {
        $sql1 = "select ".$flds." from ".DBPref."routes r where r.f_parentid=".$cpar." and r.f_postid=".$postid." ";
      //  }
      //echo $sql1."<br>";
      $ckolb1=0;$breaktmpr1=0;
      $res1 = $dbh->query($sql1);$isb=0;
      while(($row1 = $res1->fetch(PDO::FETCH_ASSOC))&&($breaktmpr1==0)&&($mbreak==0))
        {
        $cpar = $row1['f_id'];
        $fldd2=",".$row1['f_status'].",".$row1['f_p2iscustom'];
        //echo $row1['pcountry']."<br>";
        $sql = "insert into tmp_route".$logid." ".
            "(f_id,f_pid,f_level,f_pdt,f_pcountry,f_pcity,f_ppoint,f_ppointtype,f_routetype".$fldd1.") values (".
            $row1['f_id'].",".$row1['f_parentid'].",".$clevel.",'".
            $row1['pdt']."',".$row1['pcountry'].",".$row1['pcity'].",".$row1['ppoint'].",".$row1['ppointtype'].",".
            $row1['f_routetype'].$fldd2.")";
        //echo $sql."<br>";
        $dbh->exec($sql);
        $ckolb1++;
        if($ckolb1>80){echo "break post id1:$postid";$breaktmpr1=1;}
        $isb=1;
        $mkol++;if($mkol>120){echo "mainbreak1 post id:$postid";$mbreak=1;}
        }
      if($isb==1){$clevel++;}
      else{$kall=0;}
      $ckolb++;
      if($ckolb>80){echo "break post id:$postid";$breaktmpr=1;}
      $mkol++;if($mkol>120){echo "mainbreak2 post id:$postid";$mbreak=1;}
      }
    $mkol++;if($mkol>120){echo "mainbreak3 post id:$postid";$mbreak=1;}
    }
  /*
  //$sql = "select distinct(concat(f_ktkid,f_carnum)) ktk from ".DBPref."routes where f_postid=".$postid;
  //$sql = "select distinct(f_ktkid) ktk from ".DBPref."routes where f_postid=".$postid;
  //$sql = "select distinct(f_carnum) ktk,1 d from ".DBPref."routes where f_postid=".$postid." and f_carnum<>'_' ".
  //       "union ".
  //       "select distinct(f_ktkid) ktk,2 d from ".DBPref."routes_ktk where f_routeid in (select f_id from ".DBPref."routes where f_postid=".$postid.")";
  //echo $sql."<br>";
  //$res = $dbh->query($sql);
  //while($row = $res->fetch(PDO::FETCH_ASSOC))
  //  {
    //echo "-----<br>";
    $kall = 1;$fpr=0;$clevel=0;$cfid=0;
    while($kall==1)
      {
      if($clevel==0)
        {
        if($row['d']==1)
          {$sql = "select r.f_id,r.f_parentid,r.f_carnum ktkid,case when r.f_p1dt is null or r.f_p1dt='0000-00-00' then r.f_p1dtp else r.f_p1dt end pdt,".
                  "r.f_p1country,r.f_p1city,r.f_p1point,r.f_p1pointtype ".
                  "from ".DBPref."routes r where r.f_carnum='".$row['ktk']."' and f_postid=".$postid." and f_parentid=".$cfid;}
        elseif($row['d']==1)
          {$sql = "select r.f_id,r.f_parentid,r.f_carnum,case when r.f_p1dt is null or r.f_p1dt='0000-00-00' then r.f_p1dtp else r.f_p1dt end pdt,".
                  "r.f_p1country,r.f_p1city,r.f_p1point,r.f_p1pointtype ".
                  "from ".DBPref."routes r,".DBPref."routes_ktk rk where rk.f_routesid=r_fid and rk.f_ktkid='".$row['ktk']."' and f_postid=".$postid." and f_parentid=".$cfid;}
        //echo $sql."<br>";
        $res1 = $dbh->query($sql);
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {
          //$cfid = $row1['f_id'];
          //echo $cfid."<br>";
          $sql = "insert into tmp_route".$logid." (f_id,f_pid,f_level,f_ktkid,f_pdt,f_pcountry,f_pcity, ".
                 "f_ppoint,f_ppointtype) values (".$row1['f_id'].",".$row1['f_parentid'].",".$clevel.",'".$row1['ktkid']."','".
                 $row1['pdt']."',".$row1['f_p1country'].",".$row1['f_p1city'].",".$row1['f_p1point'].",".
                 $row1['f_p1pointtype'].")";
          //echo $sql."<br>";
          $dbh->exec($sql);
          $clevel++;
          }
        else{$kall=0;}
        //$kall=0;
        }
      if($kall==1)
        {
        $sql = "select r.f_id,r.f_parentid,r.f_ktkid,case when r.f_p2dt is null or r.f_p2dt='0000-00-00' then r.f_p2dtp else r.f_p2dt end pdt,".
               "r.f_p2country,r.f_p2city,r.f_p2point,r.f_p2pointtype,r.f_routetype,r.f_status,r.f_p2iscustom ".
               //"from ".DBPref."routes where f_ktkid=".$row['ktk']." and f_postid=".$postid." and f_parentid=".$cfid;
               "from ".DBPref."routes r where (r.f_carnum='".$row['ktk']."' or (select count(*) from ".DBPref."routes_ktk where f_routeid=r.f_id and f_ktkid='".$row['ktk']."')>0) and r.f_postid=".$postid." and r.f_parentid=".$cfid;
        //echo $sql."<br>";
        $res1 = $dbh->query($sql);
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {
          $cfid = $row1['f_id'];
          $sql = "insert into tmp_route".$logid." (f_id,f_pid,f_level,f_ktkid,f_pdt,f_pcountry,f_pcity, ".
                 "f_ppoint,f_ppointtype,f_routetype,f_status,f_piscustom) values (".$row1['f_id'].",".$row1['f_parentid'].",".$clevel.",".
                 $row1['f_ktkid'].",'".$row1['pdt']."',".$row1['f_p2country'].",".$row1['f_p2city'].",".
                 $row1['f_p2point'].",".$row1['f_p2pointtype'].",".$row1['f_routetype'].",".$row1['f_status'].",".$row1['f_p2iscustom'].")";
          //echo $sql."<br>";
          $dbh->exec($sql);
          $clevel++;
          }
        else{$kall=0;}
        //$kall=0;
        }
      }
  //  }*/
  }

//получить маршрут доставки для отображения
function getPrintRoute($postid,$specid=0)
  {
  $route = "";
  if($_SESSION['loginid'])
    {
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    //$route = "<table><tr>";
    //$route = "<div class=\"modal-body\"><div class=\"row\">";
    $route = "";
    getTblRoute($postid,$_SESSION['loginid'],$dbh,$specid);
    $ml   = 0;
    $minl = 0;
    if($specid>0)
      {
      $sql = "select ifnull(max(f_level),0) ml from tmp_route".$_SESSION['loginid']." t,".DBPref."routes_spec s where t.f_id=s.f_routeid and s.f_specid=$specid";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$ml=$row['ml'];}
      $sql = "select ifnull(min(f_level),0) ml from tmp_route".$_SESSION['loginid']." t,".DBPref."routes_spec s where t.f_id=s.f_routeid and s.f_specid=$specid";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$minl=$row['ml'];}
      }
    else
      {
      $sql = "select max(f_level) ml from tmp_route".$_SESSION['loginid']."";
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$ml=$row['ml'];}
      //echo $ml."<br>";
      }
    $i=$minl;$isfr=0;$fbreak=0;
    //if($ml>10){$ml=10;}
    while(($i<=$ml)&&($fbreak==0))
      {
      $curcol = "";
      if($specid>0)
        {
        $sql = "select distinct t.f_routetype,t.f_pcity,t.f_ppoint,date_format(t.f_pdt,'%d.%m.%Y') f_pdt,t.f_status,
                  t.f_level,t.f_piscustom,t.f_isdopp,t.f_pid,t.f_id,
                  (select f_name from ".DBPref."spr where f_type=39 and f_num=t.f_pcity) pcity, 
                  (select f_name from ".DBPref."spr where f_type=40 and f_num=t.f_ppoint) ppoint 
                from tmp_route".$_SESSION['loginid']." t,".DBPref."routes_spec s where t.f_id=s.f_routeid and s.f_specid=$specid and t.f_level=".$i;
        }
      else
        {
        $sql = "select distinct t.f_routetype,t.f_pcity,t.f_ppoint,date_format(t.f_pdt,'%d.%m.%Y') f_pdt,t.f_status,t.f_level,f_piscustom,".
          "  (select f_name from ".DBPref."spr where f_type=39 and f_num=t.f_pcity) pcity, ".
          "  (select f_name from ".DBPref."spr where f_type=40 and f_num=t.f_ppoint) ppoint ".
          "from tmp_route".$_SESSION['loginid']." t where t.f_level=".$i;
        }
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      $kk=0;
      $ccity = "";$sbreak=0;
      while(($row = $res->fetch(PDO::FETCH_ASSOC))&&($sbreak==0))
        {
        $tsql = "";
        if($specid>0)
          {
          if((strlen($route)==0)&&($isfr<1)&&($row['f_isdopp']==0)&&($row['f_pid']>0))//строим первую точку
            {
            $sql2 = "select t.f_p1dt f_pdt,
                       (select f_name from ".DBPref."spr where f_type=39 and f_num=t.f_p1city) pcity, 
                       (select f_name from ".DBPref."spr where f_type=40 and f_num=t.f_p1point) ppoint 
                     from veda_routes t where t.f_id=".$row['f_id'];
            //$tsql = $tsql.$sql2."|";
            $res1 = $dbh->query($sql2);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ctr = "<i class=\"fa fa-star\"></i> ";
              if(strlen($row['ppoint'])>2)
                {$ccity = $ccity."<span class=\"info-box-text\">".$ctr.$row1['ppoint']." (".$row1['f_pdt'].")"."</span>";}
              else
                {$ccity = $ccity."<span class=\"info-box-text\">".$ctr.$row1['pcity']." (".$row1['f_pdt'].")"."</span>";}
              $route = $route."<div class=\"col-1\"><div class=\"info-box bg-teal\"><div class=\"info-box-content\">".$ccity."</div></div></div>&nbsp;";
              $ccity = "";
              }
            $isfr = 1;
            }
          }
        $curz1  = "";
        $curz2  = "";
        //$curcol = "black";
        $curcol = " bg-teal";
        $kr="";
        if($kk>0){$kr="<br>";}
        $ctr = "";
        if($row['f_level']>0)
          {
          if($row['f_piscustom']==1)
            {$curz1 = "<strong>";$curz2 = "</strong>";$ctr = "<i class=\"fa fa-flag\"></i> ";}
          if($row['f_status']==5)
            //{$curcol = "green";}
            {$curcol = " bg-green";}
          else
            //{$curcol = "gray";}
            {$curcol = " bg-gray";}
//sdid 1227
/*          if($row['f_routetype']==1){$ctr = $ctr." <i class=\"fa fa-ship\"></i> ";}
          elseif(($row['f_routetype']==2)||($row['f_routetype']==5)){$ctr = $ctr." <i class=\"fa fa-train\"></i> ";}
          elseif($row['f_routetype']==3){$ctr = $ctr." <i class=\"fa fa-plane\"></i> ";}
          elseif(($row['f_routetype']==4)||($row['f_routetype']==6)){$ctr = $ctr." <i class=\"fa fa-truck\"></i> ";}
*/
          if($row['f_routetype']==1){$ctr = $ctr." <i>Море</i> ";}
          elseif(($row['f_routetype']==2)||($row['f_routetype']==5)){$ctr = $ctr." <i>ЖД</i> ";}
          elseif($row['f_routetype']==3){$ctr = $ctr." <i>Авиа</i> ";}
          elseif(($row['f_routetype']==4)||($row['f_routetype']==6)){$ctr = $ctr." <i>Авто</i> ";}
//~sdid 1227
          }
//sdid 1227
//        else{$ctr = "<i class=\"fa fa-star\"></i> ";}
//~sdid 1227
        //$ccity = $ccity.$kr.$curz1."<font color=\"".$curcol."\">".$row['pcity']."(".$row['f_pdt'].")"."</font>".$curz2;
        if(strlen($row['ppoint'])>2)
          {$ccity = $ccity."<span class=\"info-box-text\">".$ctr.$row['ppoint']." (".$row['f_pdt'].")"."</span>";}
        else
          {$ccity = $ccity."<span class=\"info-box-text\">".$ctr.$row['pcity']." (".$row['f_pdt'].")"."</span>";}
        $kk++;
        if($kk>60){$sbreak=1;
          $route = $route."<div class=\"col-1\"><div class=\"info-box".$curcol."\"><div class=\"info-box-content\">first break</div></div></div>&nbsp;";}
        }
      //$route = $route."<div class=\"col-md-3 col-sm-6 col-12\"><div class=\"info-box".$curcol."\"><div class=\"info-box-content\">".$ccity."</div></div></div>";
      //$route = $route."<div class=\"col-1\"><div class=\"info-box".$curcol."\"><div class=\"info-box-content\">".$isfr."_".$tsql."_".$ccity."</div></div></div>&nbsp;";
      $route = $route."<div class=\"col-1\"><div class=\"info-box".$curcol."\"><div class=\"info-box-content\">".$ccity."</div></div></div>&nbsp;";
      $i++;
      if($i>60){$fbreak=1;
        $route = $route."<div class=\"col-1\"><div class=\"info-box".$curcol."\"><div class=\"info-box-content\">first break</div></div></div>&nbsp;";}
      }
    //$route = $route."</tr></table>";
    $route = $route."</div></div>";
    $route = "<div class=\"modal-body\"><div class=\"row\">".$route;
    //$sql = "select t.*,(select k.f_num from ".DBPref."ktk k where k.f_id=t.f_ktkid) ktk, ".
    //       "(select f_name from ".DBPref."spr where f_type=39 and f_num=t.f_pcity) pcity ".
    //       "from tmp_route".$_SESSION['loginid']." t order by t.f_level,t.f_ktkid";
    //echo $sql."<br>";
    //$res = $dbh->query($sql);
    //while($row = $res->fetch(PDO::FETCH_ASSOC))
    //  {
    //  echo $row['ktk']."|".$row['pcity']."|".$row['f_level']."|".$row['f_ktkid']."|".$row['f_id']."|".$row['f_pid']."|".
    //       $row['f_pdt']."|".
    //       $row['f_pcountry']."|".$row['f_pcity']."|".$row['f_ppoint']."|".$row['f_ppointtype']."|".$row['f_routetype']."|".
    //       $row['f_status']."|".$row['f_piscustom']."<br>";
    //  }
    }
  return $route;
  }

//получить дату следующего рабочего дня хранимой процедурой по календарю выходных дней
function nextWorkDay($dt,$daycount)
{
    try
    {
        $dbh = new PDO("mysql:host=".DBHost.";dbname=".DataBase, UserName, Password);
        $dbh->exec("SET CHARACTER SET utf8");
        $sql = "select nextWorkDay('".$dt."',".$daycount.") dt from dual";
        //echo $sql;
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            return $row['dt'];
        }
        return $dt;
    }
    catch (PDOException $e)
    {return $dt;}
}

//создать <select> для выбора записей пользования ктк
function getSelKtkUseVal($ord,$did,$mstyle,$fid,$wnz,$djspar)
{
    try
    {
        $specid = "";
        $wsr    = 0;
        //echo $djspar."<br>";
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            if(isset($djspar['specid']))
            {$specid = $djspar['specid'];}
            if(isset($djspar['wsr']))
            {$wsr = $djspar['wsr'];}
        }
        $sfid    = "";
        if(strlen($specid)>0)
        {
            $sfid = " and c.f_specid in (".$specid.") ";
        }
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        if($wsr==0)
        {$response = "<select class='selval' ".$smstyle." id='getSelCertificatesVal".$did."'>";}
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        $sql = "select c.f_id,".
            "  concat(k.f_num,'/',spr.f_dopprstr,' ',d.f_dogname,'/',s.f_num,'/',o.f_abbr,'/',".
            "  case ".
            "    when s.f_typez=1 then ct.f_name ".
            "    else cl.f_cname ".
            "  end,'/ (ИД ',c.f_id,')') selval ".
            "from ".DBPref."ktk_use c,".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr,".
            "  ".DBPref."contacts ct,".DBPref."ktk k ".
            "where k.f_id=c.f_ktkid and c.f_specid=s.f_id and spr.f_type=33 and spr.f_num=s.f_typez and ct.f_id=s.f_contactid and o.f_id=d.f_orgid and ".
            "  cl.f_id=d.f_contrid and s.f_dogid=d.f_id ".$sfid." ".$ord;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['selval']."</option>";}
        if($wsr==0)
        {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelCertificatesVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//получить выборку записей финансирвоания
function getSelLnsValExt($curNum,$spec,$ord,$did)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($spec)>0)
        {$spec = " and f_specid in (".$spec.") ";}
        else
        {$spec = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelLnsVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,concat('ДС № ',ifnull(f_num,''),' от ',ifnull(f_dt,''),' (ИД ',f_id,')') f_num ".
            "FROM ".DBPref."lns where f_id>=0 ".$curNum." ".$spec." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelLnsValExt".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//получить выборку кассовых операций
function getSelCashVal($specid,$idoper,$ord,$did,$mstyle,$fid,$wnz)
{
    try
    {
        $sfid = "";
        if(isset($fid))
        {
            if(strlen($fid)>0)
            {
                if(strcmp($fid,"0")==0){$sfid = "";}else{$sfid = " and s.f_id in (".$fid.") ";}}else{$sfid = "";}}
        //echo $fid."<br>";
        $sorgid = "";
        if(isset($specid)){if($specid>0){$sorgid = " and s.f_specid=".$specid." ";}}
        $scontrid = "";
        if(isset($idoper)){if($idoper>0){$scontrid = " and s.f_idoper=".$idoper." ";}}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $response = "<select class='selval' ".$smstyle." id='getSelCashVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        $sql = "select s.f_id,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_insum,s.f_outsum,s.f_val, ".
            "  (SELECT f_uslstr FROM ".DBPref."spr where f_type=4 and f_num=s.f_val) val ".
            "from ".DBPref."cash s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." ".$ord;
        //echo $sql."<br>";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>+".$row['f_insum']."/-".$row['f_outsum']."".$row['val']."/".$row['f_dt']."/".$row['f_id']."</option>";}
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelCashVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//получить выборку видов операций
function getSelOperListVal($did,$wsr,$oprst,$poprid,$pforit,$pisforfd,$mstyle,$selid,$wdis,$fdop,$curclass="")
  {
  //echo $did."<br>";echo $wsr."<br>";echo $oprst."<br>";echo $poprid."<br>";echo $pforit."<br>";echo $pisforfd."<br>";echo $mstyle."<br>";echo $selid."<br>";echo $wdis."<br>";
  //echo 1;
  try
    {
    //sdid2460
    $dofilter = false;
    $opers_filter = "";
    if(isset($_SESSION['loginid']))
      {
      $loginid = $_SESSION['loginid'];
      $dbconn = dbconnect();
      $sql = "SELECT COUNT(*) cnt FROM ".DBPref."spr spr, ".DBPref."usr_grp usrgrp, ".DBPref."users u WHERE spr.f_type=63 AND spr.f_num=usrgrp.f_grpid AND spr.f_isext=1 AND usrgrp.f_usrid=u.f_id AND u.f_id=$loginid";
      //echo "$sql<br>";
      $conn = $dbconn->query($sql);
      if($row = $conn->fetch(PDO::FETCH_ASSOC))
        {
        if($row['cnt'] > 0)
          {
          $dofilter = true;
          $sql_ = "SELECT GROUP_CONCAT(DISTINCT usrgrp_opers.f_idoper SEPARATOR ',') opers FROM ".DBPref."usrgrp_opers usrgrp_opers, ".DBPref."usr_grp usr_grp, ".DBPref."users u WHERE u.f_id=$loginid AND usr_grp.f_usrid=u.f_id AND usrgrp_opers.f_usrgrpnum=usr_grp.f_grpid";
          $conn_ = $dbconn->query($sql_);
          if($row_ = $conn_->fetch(PDO::FETCH_ASSOC))
            {
            $opers_filter = $row_['opers'];
            if(strlen($opers_filter) > 0)
              {
              $opers_filter = " and t.f_id in ($opers_filter) ";
              }
            else
              {
              if(isset($selid))
                {
                $opers_filter = " and t.f_id=$selid ";
                }
              else
                {
                $opers_filter = " and t.f_id IN (0) ";
                }
              }
            }
          }
        }
      }
    //~sdid2460
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $sclas = "selval";
    if(strlen($curclass)>0){$sclas = $curclass;}
    if(isset($ws2))
      {
      if($ws2>0)
        {$sclas = "getSelOperListVal2".$did;}
      }
    $wdisstr = "";
    if(isset($wdis))
      {
      if($wdis==1)
        {$wdisstr = " disabled=\"disabled\" ";}
      }
    $response = "";
    if($wsr>0)
      {$response = "<select  id='getSelOperListVal".$did."' ".$wdisstr." class='".$sclas."' ".$smstyle.">";}
    if(($pisforfd==0)||($pisforfd==3))
      {$response = $response."<option value=0>-</option>";}
    $sfdop = "";$sfdopo = "";
    if($fdop==1)
      {$sfdop = " and t.f_isdopoper=".$fdop." ";$sfdopo = " or t.f_isdopoper=1 ";}
    elseif($fdop==2)
//sdid 629
//      {$sfdop = " and t.f_isdopoper=1 and t.f_subtype=0 ";$sfdopo = " and t.f_isdopoper=1 and t.f_subtype=0 ";}
      {$sfdop = " and t.f_isdopoper=1 and (t.f_subtype=0 or t.f_wmakedohoper=1) ";$sfdopo = " and t.f_isdopoper=1 and (t.f_subtype=0 or t.f_wmakedohoper=1) ";}
//~sdid 629
    if($poprid>0)
      {
      //подключаемся к базе
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      //указываем, мы хотим использовать utf8
      $dbh->exec('SET CHARACTER SET utf8');
      //$response = $response."<option value=0>-</option>";
      //sdid 1583
      /*if($pisforfd>0)
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.")";}
      else
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 and t.f_subtype>=0 and t.f_type=".
          "  (select f_type from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.")) order by f_name";}
      */
      if($pisforfd>0)
        //sdid2460
        //{$sql = "select t.f_id,t.f_name,t.f_c1doctype from ".DBPref."typeopers t 
        //         where t.f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.")";}
        {$sql = "select t.f_id,t.f_name,t.f_c1doctype from ".DBPref."typeopers t 
                 where t.f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.") $opers_filter ";}
        //~sdid2460
      else
        //sdid2460
        //{$sql = "select t.f_id,t.f_name,t.f_c1doctype from ".DBPref."typeopers t 
        //         where t.f_isuse=1 and t.f_subtype>=0 and 
        //           (t.f_type=(select f_type from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.")) or 
        //            t.f_id=case when (select f_c1doctype from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."))=4 
        //                             or 
        //                             (select f_subtype from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."))=0
        //                        then 389 else -1 end)
        //           and t.f_id<>386 and t.f_id<>387 order by f_name";}
        //3. В диалоге создания подоперации внести изменения в выборку доступных операций, отбирать либо с тем же видом, не нулевым подвидом,
        // если не задано поле п.1.
        //   + операции, где указано в поле п.1. - операция с которой делают подоперацию
        //   + доп операции, которые добираются сейчас дополнительно
        {
        $sql = "SELECT 
                t.f_id,
                t.f_name,
                t.f_c1doctype
              FROM ".DBPref."typeopers t 
              WHERE
                t.f_isuse = 1
                AND 
                CASE 
                  WHEN t.f_parenttypeoper=0 and t.f_parenttypeoperstr='0' 
                  THEN
                    t.f_subtype>0 
                    AND (t.f_type=(select f_type from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."))
                         or t.f_id=case 
                                 when (select f_c1doctype from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."))=4 
                                       or
                                      f_subtype=0
                                 then 389 
                                    else -1 
                               end)
                  WHEN t.f_parenttypeoper>0 and t.f_parenttypeoper=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid.") THEN
                    1=1
                  WHEN t.f_parenttypeoper=0 and t.f_parenttypeoperstr<>'0' and 
                    (select count(*) from ".DBPref."typeopers where f_parenttypeoperstr like concat('%,',(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."),',%'))>0 then
                    t.f_id in (select f_id from veda_typeopers where f_parenttypeoperstr like concat('%,',(select f_idoper from veda_spec_invoices where f_id=".$poprid."),',%'))
                  ELSE
                    t.f_subtype>0
                    AND t.f_id=case 
                                 when (select f_c1doctype from ".DBPref."typeopers where f_id=(select f_idoper from ".DBPref."spec_invoices where f_id=".$poprid."))=4 
                                       or
                                      f_subtype=0
                                 then 389 
                                    else -1 
                               end
                  END
                AND t.f_id<>386 
                $opers_filter 
                AND t.f_id<>387 
              ORDER BY 
                f_name";
      }
      //~20.05.2024
      //~sdid2460
      //sdid 1583
      //if($_SESSION['loginid']==2){echo "$sql<br>";}
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $sels = "";
        if(isset($selid))
          {
          if($row['f_id']==$selid)
            {$sels = "selected";}
          }
        $response = $response."<option ".$sels." value=".$row['f_id'].">".$row['f_name']."</option>";
        //echo $retval;
        }
        //echo $retval;
      }
    elseif($oprst>0)
      {
      $spforit = "";
      if($pforit==1)//Логист.
        {$spforit = " or t.f_islogist=1 ";}
      elseif($pforit==2)//Спец.
        {$spforit = " or t.f_isspec=1 ";}
      elseif($pforit==3)//Дог.
        {$spforit = " or t.f_isdog=1 ";}
      elseif($pforit==4)//Серт.
        {$spforit = " and t.f_issert=1 ";}
      elseif($pforit==5)//Страх.
        {$spforit = " or t.f_isens=1 ";}
      elseif($pforit==6)//КП
        {$spforit = " and t.f_iskp=1 ";}
      elseif($pforit==7)//ДТ
        {$spforit = " or t.f_isdt=1 ";}
      elseif($pforit==8)//поиск
        {$spforit = " and t.f_issearch=1 ";}
      //подключаемся к базе
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      //указываем, мы хотим использовать utf8
      $dbh->exec('SET CHARACTER SET utf8');
      if($oprst==84)
        //sdid2460
        //{$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 and t.f_id in (72,73,74,117) order by t.f_name";}
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 $opers_filter and t.f_id in (72,73,74,117) order by t.f_name";}
        //~sdid2460
      //elseif($oprst==902)
      //  {$oprst=2;$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 and t.f_id in (193,196) order by t.f_name";}
      elseif($oprst==1003)
        //sdid2460
        //{$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 and t.f_id in (".$selid.") order by t.f_name";}
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 $opers_filter and t.f_id in (".$selid.") order by t.f_name";}
        //~sdid2460
      elseif($oprst==1004)
        //sdid2460
        //{$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 order by t.f_name";}
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 $opers_filter order by t.f_name";}
        //~sdid2460
      //sdid 1275
      elseif($pforit==9)//Страх.
        //sdid2460
        //{$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 and t.f_isens=1 order by t.f_name";}
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 $opers_filter and t.f_isens=1 order by t.f_name";}
        //~sdid2460
      //~sdid 1275
      else
//sdid 629
/*        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 ".$sfdop." and ".
                "  (t.f_subtype=0 ".$sfdopo." or (select count(*) from ".DBPref."typeopers where f_isuse=1 and f_type=t.f_type and f_subtype=0)=0) and ".
                "  (t.f_typeist=".$oprst." ".$spforit.") order by t.f_name";}
*/
        //sdid2460
        //{$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 ".$sfdop." and ".
        //        "  ((t.f_subtype=0 or t.f_wmakedohoper=1) ".$sfdopo." or (select count(*) from ".DBPref."typeopers where f_isuse=1 and f_type=t.f_type and (f_subtype=0 or t.f_wmakedohoper=1))=0) and ".
        //        "  (t.f_typeist=".$oprst." ".$spforit.") order by t.f_name";}
        {$sql = "select t.f_id,t.f_name from ".DBPref."typeopers t where t.f_isuse=1 ".$sfdop." and ".
                "  ((t.f_subtype=0 or t.f_wmakedohoper=1) ".$sfdopo." or (select count(*) from ".DBPref."typeopers where f_isuse=1 and f_type=t.f_type and (f_subtype=0 or t.f_wmakedohoper=1))=0) and ".
                "  (t.f_typeist=".$oprst." ".$spforit.") $opers_filter order by t.f_name";}
          //~sdid2460

//~sdid 629
      //echo "$sql<br>";
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $sels = "";
        if(isset($selid))
          {
          if($row['f_id']==$selid)
            {$sels = "selected";}
          }
        $response = $response."<option ".$sels." value=".$row['f_id'].">".$row['f_name']."</option>";
        }
      }
    if($wsr>0)
    {$response = $response."</select>";}
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    if($wsr>0)
    {echo "<select class='selval' id='getSelOperListVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";}
    else
    {echo "<option value='0'>Database error: ".$e->getMessage()."</option>";}
    }
  }


//получить выборку всех пунктов маршрутов
function getSelRoutePointsVal($did,$mstyle,$selid,$ws2,$wdis,$djspar)
{
    //echo 1;
    try
    {
        $wz = 0;
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            if(isset($djspar['wz'])){$wz = $djspar['wz'];}
        }
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $sclas = "selval";
        if(isset($ws2))
        {
            if($ws2>0)
            {$sclas = "getSelRoutePointsVal2".$did;}
        }
        $wdisstr = "";
        if(isset($wdis))
        {
            if($wdis==1)
            {$wdisstr = " disabled=\"disabled\" ";}
        }
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        $response = "<select ".$wdisstr." class='".$sclas."' ".$smstyle." id='getSelRoutePointsVal".$did."'>";
        if($wz==1)
        {$response=$response."<option value='0'>-</option>";}
//sdid 962
/*        $sql = "(select concat(s38.f_type,LPAD(s38.f_num,5,'0')) num,s38.f_name fname from ".DBPref."spr s38 where s38.f_type=38 and s38.f_num>0) 
                union 
                (select concat(s39.f_type,LPAD(s39.f_num,5,'0')) num,concat(s38.f_name,' / ',s39.f_name) fname from ".DBPref."spr s39,".DBPref."spr s38 where s39.f_namedop=s38.f_num and s38.f_type=38 and s39.f_type=39 and s39.f_num>0) 
                union 
                (select concat(s40.f_type,LPAD(s40.f_num,5,'0')) num,concat(s38.f_name,' / ',s41.f_name,' / ',s40.f_name) fname 
                 from ".DBPref."spr s41,".DBPref."spr s40,".DBPref."spr s38 
                 where s41.f_type=41 and s40.f_type=40 and s40.f_uslint=s41.f_num and s40.f_num>10000 and s40.f_namedop=s38.f_num and 
                   s38.f_type=38 and s38.f_num>0) 
                union 
                (select concat(s40.f_type,LPAD(s40.f_num,5,'0')) num,concat(s38.f_name,' / ',s39.f_name,' / ',s41.f_name,' / ',s40.f_name) fname 
                 from ".DBPref."spr s41,".DBPref."spr s40,".DBPref."spr s38,".DBPref."spr s39 
                 where s41.f_type=41 and s41.f_num=s40.f_uslint and s40.f_type=40 and s40.f_num<10000 and s40.f_namedop=s39.f_num and s39.f_type=39 and 
                   s39.f_num>0 and s39.f_namedop=s38.f_num and s38.f_type=38 and s38.f_num>0) 
                order by fname";
*/
        $sql = "(select concat(s38.f_type,LPAD(s38.f_num,5,'0')) num,s38.f_name fname from ".DBPref."spr s38 where s38.f_type=38 and s38.f_num>0) ".
            " union ".
            "(select concat(s39.f_type,LPAD(s39.f_num,5,'0')) num,concat(s38.f_name,' / ',s39.f_name) fname from ".DBPref."spr s39,".DBPref."spr s38 where s39.f_namedop=s38.f_num and s38.f_type=38 and s39.f_type=39 and s39.f_num>0) ".
            " union ".
            "(select concat(s40.f_type,LPAD(s40.f_num,5,'0')) num,concat(s38.f_name,' / ',s39.f_name,' / ',s41.f_name,' / ',s40.f_name) fname ".
            "  from ".DBPref."spr s41,".DBPref."spr s40,".DBPref."spr s38,".DBPref."spr s39 ".
            "  where s41.f_type=41 and s41.f_num=s40.f_uslint and s40.f_type=40 and s40.f_num>10000 and s40.f_namedop=s39.f_num ".
            "    and s39.f_type=39 and s39.f_num>0 and s39.f_namedop=s38.f_num and s38.f_type=38 and s38.f_num>0) ".
            //"order by fname";
            " union ".
            "(select concat(s40.f_type,LPAD(s40.f_num,5,'0')) num,concat(s38.f_name,' / ',s39.f_name,' / ',s41.f_name,' / ',s40.f_name) fname ".
            "  from ".DBPref."spr s41,".DBPref."spr s40,".DBPref."spr s38,".DBPref."spr s39 ".
            "  where s41.f_type=41 and s41.f_num=s40.f_uslint and s40.f_type=40 and s40.f_num<10000 and s40.f_namedop=s39.f_num ".
            "    and s39.f_type=39 and s39.f_num>0 and s39.f_namedop=s38.f_num and s38.f_type=38 and s38.f_num>0) ".
            "order by fname";
//~sdid 962

        //echo $sql."<br>";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $sels = "";
            if(isset($selid))
            {
                if($row['num']==$selid)
                {$sels = "selected";}
            }
            $response = $response."<option ".$sels." value='".$row['num']."'>".$row['fname']."</option>";
        }
        $response = $response."</select>";
        //echo $response;
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelRoutePointsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//сформирвоать строку для символа операции в строке
function getSpanStrOpr($sym)
{
    $bcgcolor  = "#99CC99";
    $btnw      = "info";
    if(isset($_SESSION['dialogtemplate']))
      {if($_SESSION['dialogtemplate']>0)
        {if(isset($_SESSION['dialogcolors']))
          {$btnw   = $_SESSION['dialogcolors'];}
        if(isset($_SESSION['dialogcolorscode']))
          {$bcgcolor  = $_SESSION['dialogcolorscode'];}
        }}
    if(strcmp(substr($sym,0,1),"1")==0)
    {$bcgcolor = "gray";$sym=substr($sym,1,(strlen($sym)-1));$btnw = "warning";}
    if(strcmp(substr($sym,0,1),"2")==0)
    {$bcgcolor = "gray";$sym=substr($sym,1,(strlen($sym)-1));$btnw = "danger";}
    if(strcmp(substr($sym,0,1),"3")==0)
    {$bcgcolor = "gray";$sym=substr($sym,1,(strlen($sym)-1));$btnw = "light";}
    if(strcmp(substr($sym,0,1),"4")==0)
    {$bcgcolor = "gray";$sym=substr($sym,1,(strlen($sym)-1));$btnw = "dark";}
    $retv = "";
    $sm="lg";
    if((oprsymmfaws>0)&&(oprsymmfaws<1))
      {$sm="xs";}
    elseif(oprsymmfaws>=1)
      {$sm=oprsymmfaws."x";}
    //$retv = "<span class='fa fa-".$sm." fa-".$sym."'";
    $retv = "<span class=\"badge badge-info mb-1 float-left\" style=\"background-color: ".$bcgcolor."\"><i class=\"fa fa-".$sm." fa-".$sym."\"";
    $retv = "<button type=\"button\" class=\"btn btn-".$btnw." float-right\" style=\"background-color: ".$bcgcolor."\"><i class=\"fa fa-".$sm." fa-".$sym."\"";
    if((oprsymwanim>0)&&(oprsymmfaws>0))
    {$smn=(oprsymmfaws+1)."x";$retv = $retv." onmouseover=\"this.className='fa fa-".$smn." fa-".$sym."'\" onmouseout=\"this.className='fa fa-".$sm." fa-".$sym."'\" ";}
    //$retv = $retv."></span>";
    //$retv = $retv."></i></span>";
    $retv = $retv."></i></button>";
    return $retv;
}

//получить тело шаблона письма
function getPostShablon($shid)
{
    $retv = "";
    try
    {
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        $sql = "select f_namedop from ".DBPref."spr where f_type=62 and f_num=".$shid;
        //echo $sql."|";
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$retv = $row['f_namedop'];}
        return $retv;
    }
    catch (PDOException $e)
    {return "";}
}

//sdid 703
//получить тело шаблона темы (Subject) письма
function getPostSubjShablon($shid)
{
    $retv = "";
    try
    {
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        $sql = "select f_namedop from ".DBPref."spr where f_type=150 and f_num=".$shid;
        //echo $sql."|";
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$retv = $row['f_namedop'];}
        return $retv;
    }
    catch (PDOException $e)
    {return "";}
}

//~sdid 703

//sdid - 164
//Получаем sql запрос для дополнительной информации по статусам
function getExtendedInfoQuery($operation_type_id, $operation_id, $issubj=0)
  {
  // sdid 1539 здесь и далее во всех operation_type_id запросах
  $sqlGetShipmentInfo = "
                  ,
                  c.f_dirmain shipment_path,
                  (SELECT MAX(r.f_p2dt) FROM ".DBPref."routes r WHERE r.f_postid=c.f_postid AND r.f_p2dt>0) shipment_arrival
                  ";
  // sdid ~1539

  // sdid 1553 здесь и далее во всех operation_type_id запросах
  $sqlGetOperTO = ",(SELECT group_concat(distinct(CONCAT(users.f_name1,' ',users.f_name2)) SEPARATOR ';') from ".DBPref."users users, ".DBPref."dt dt WHERE users.f_id=dt.f_operto AND dt.f_specid=c.f_id) operto";
  // sdid ~1553
  // sdid 1690 здесь и далее во всех operation_type_id запросах
  // менеджер по спацификации
  $sqlGetOperID = ", case when c.f_operid>0 then (SELECT ifnull(CONCAT(u.f_name1,' ',u.f_name2),'') from ".DBPref."users u WHERE u.f_id=c.f_operid) else '' end operid ";
  // Комментарий для ОТО готов (да/нет)
  //sdid 2290
  //$sqlGetComIsPrep = ", case when c.f_comisprep=1 then 'да' else 'нет' end comisprep ";
  $sqlGetComIsPrep = ", case when c.f_parentspecid>0 and c.f_subtype=3
                               then 
                                 case when (select f_comisprep from veda_specs where f_id=c.f_parentspecid)=1 then 'да' else 'нет' end
                             else
                                 case when c.f_comisprep=1 then 'да' else 'нет' end 
                         end comisprep ";
  //~sdid 2290
  // sdid ~1690
  //sdid 2084 tz2 2 здесь и далее во всех operation_type_id запросах
  //sdid 2290
  /*$sqlBlockUvedTO1 = "
                     , case when (c.f_dtarrivalto is not null and c.f_dtarrivalto<>'0000-00-00' and c.f_dtarrivalto<>'0000-00-00 00:00:00' or c.f_chkvvoz=1 or c.f_chktnved=1 or c.f_chkmark=1 or length(c.f_com_to)>0)
                              then
                                 concat((case when c.f_dtarrivalto is not null and c.f_dtarrivalto<>'0000-00-00' and c.f_dtarrivalto<>'0000-00-00 00:00:00'
                                               then concat('Дата прибытия в пункт ТО: ',DATE_FORMAT(c.f_dtarrivalto,'%d.%m.%Y %H:%i'),'<br>')
                                             else ''
                                         end),
                                         case when (c.f_chkvvoz=1 or c.f_chktnved=1 or c.f_chkmark=1 or length(c.f_com_to)>0)
                                                then concat('Задачи для ОТ:<br>',
                                                            #####
                                                            (case when length(c.f_com_to)>0
                                                                    then concat('- ',c.f_com_to,'<br>')
                                                                  else ''
                                                             end),

                                                            (case when c.f_chkvvoz=1
                                                                    then '- Проверить требуются ли разрешительные документы для ввоза<br>'
                                                                  else ''
                                                             end),
                                                            
                                                            (case when c.f_chktnved=1
                                                                    then '- Проверить и определить коды ТНВЭД<br>'
                                                                  else ''
                                                             end),
                                                            
                                                            (case when c.f_chkmark=1
                                                                    then '- Проверить маркировку<br>'
                                                                  else ''
                                                             end)
                                                            #####
                                                           )
                                              else ''
                                         end
                                       )
                            else ''
                       end blockuvedto1 
                     ";*/
  $sqlBlockUvedTO1 = "
                     ,
                     case when c.f_parentspecid>0 and c.f_subtype=3
                            then 
                     ##########################################################################################################################################
                            ( 
                            select 
                            case when cc.f_dtarrivalto is not null 
                                        and cc.f_dtarrivalto <>'0000-00-00' 
                                                and cc.f_dtarrivalto <>'0000-00-00 00:00:00' 
                                                 or cc.f_chkvvoz = 1 
                                                 or cc.f_chktnved = 1 
                                                 or cc.f_chkmark = 1 
                                                 or length(cc.f_com_to)>0
                                   then
                                     concat((case when cc.f_dtarrivalto is not null 
                                                   and cc.f_dtarrivalto <>'0000-00-00' 
                                                   and cc.f_dtarrivalto <>'0000-00-00 00:00:00'
                                                    then concat('Дата прибытия в пункт ТО: ',
                                                                DATE_FORMAT(cc.f_dtarrivalto,'%d.%m.%Y %H:%i'),'<br>')
                                                  else ''
                                              end),
                                             case when cc.f_chkvvoz = 1 
                                                    or cc.f_chktnved = 1 
                                                    or cc.f_chkmark = 1 
                                                    or length(cc.f_com_to) > 0
                                                    then concat('Задачи для ОТ:<br>',
                                                                 (case when length(cc.f_com_to) > 0
                                                                         then concat('- ',cc.f_com_to,'<br>')
                                                                       else ''
                                                                   end),
                                                                 (case when cc.f_chkvvoz = 1
                                                                         then '- Проверить требуются ли разрешительные документы для ввоза<br>'
                                                                       else ''
                                                                   end),
                                                                 (case when cc.f_chktnved = 1
                                                                         then '- Проверить и определить коды ТНВЭД<br>'
                                                                       else ''
                                                                   end),
                                                                 (case when cc.f_chkmark = 1
                                                                         then '- Проверить маркировку<br>'
                                                                       else ''
                                                                   end)
                                                               )
                                                  else ''
                                              end
                                            )
                                 else ''
                            end 
                            from veda_specs cc where cc.f_id=c.f_parentspecid
                            )
                     ##########################################################################################################################################                                             
                          else
                     ##########################################################################################################################################
                            case when (c.f_dtarrivalto is not null 
                                  and c.f_dtarrivalto<>'0000-00-00' 
                                  and c.f_dtarrivalto<>'0000-00-00 00:00:00' 
                                   or c.f_chkvvoz=1 
                                   or c.f_chktnved=1 
                                   or c.f_chkmark=1 
                                   or length(c.f_com_to)>0)
                                   then
                                     concat((case when c.f_dtarrivalto is not null and c.f_dtarrivalto<>'0000-00-00' and c.f_dtarrivalto<>'0000-00-00 00:00:00'
                                                    then concat('Дата прибытия в пункт ТО: ',DATE_FORMAT(c.f_dtarrivalto,'%d.%m.%Y %H:%i'),'<br>')
                                                  else ''
                                              end),
                                             case when (c.f_chkvvoz=1 or c.f_chktnved=1 or c.f_chkmark=1 or length(c.f_com_to)>0)
                                                    then concat('Задачи для ОТ:<br>',
                                                                 (case when length(c.f_com_to)>0
                                                                         then concat('- ',c.f_com_to,'<br>')
                                                                       else ''
                                                                   end),
                                                                 (case when c.f_chkvvoz=1
                                                                         then '- Проверить требуются ли разрешительные документы для ввоза<br>'
                                                                       else ''
                                                                   end),
                                                                 (case when c.f_chktnved=1
                                                                         then '- Проверить и определить коды ТНВЭД<br>'
                                                                       else ''
                                                                   end),
                                                                 (case when c.f_chkmark=1
                                                                         then '- Проверить маркировку<br>'
                                                                       else ''
                                                                   end)
                                                               )
                                                  else ''
                                              end
                                            )
                                 else ''
                                            end 
                     ##########################################################################################################################################                                             
                     end blockuvedto1 
                      ";
  //~sdid 2290
  //~sdid 2084 tz2 2
  $sql = "";
  if($operation_type_id == 1){ //Информация о ТО
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=34 AND spr.f_num=dt.f_status) new_status,
                    CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    c.f_tovar spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                    WHERE r.f_postid=c.f_postid AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND dt.f_specid=c.f_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_dt dt, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    dt.f_id=$operation_id AND dt.f_specid=c.f_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 2){ //Доставка
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=43 AND spr.f_num=shipments.f_status) new_status,
                    GROUP_CONCAT(DISTINCT CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') SEPARATOR ';<br>') spec,
                    GROUP_CONCAT(DISTINCT c.f_tovar SEPARATOR ';<br>') spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND c.f_postid=$operation_id AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND c.f_postid=$operation_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_shipments shipments, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    shipments.f_id=$operation_id AND c.f_postid=$operation_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 3){ //Спецификация
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT f_name FROM veda_spr spr WHERE spr.f_type=6 AND spr.f_num=c.f_status) new_status,
                    CONCAT(spr.f_dopprstr,' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    CASE WHEN c.f_subtype=4 THEN '-' ELSE c.f_tovar END spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ')
                        FROM veda_ktk k,veda_routes r,veda_routes_ktk rk
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(distinct r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_specs c, veda_dogs d, veda_clients clnt, veda_clients org, veda_spr spr
                WHERE
                    spr.f_type=33 AND spr.f_num=c.f_typez AND org.f_id=d.f_orgid AND clnt.f_id=d.f_contrid AND d.f_id=c.f_dogid AND c.f_id=$operation_id";
    }
    else if($operation_type_id == 4){ //Страховка
        $sql = "SELECT
                    0 f_cursoper,
                    f_sumens,f_sumens1,f_sumens2,f_enssum f_sumins, -- sdid 2730
                    (SELECT spr.f_uslstr FROM ".DBPref."spr spr WHERE spr.f_type=4 AND spr.f_num=f_enssumval) suminsval, -- sdid 2730
                    (SELECT spr.f_uslstr FROM ".DBPref."spr spr WHERE spr.f_type=4 AND spr.f_num=f_sumensval) sumensval,c.f_id specid, -- sdid 2730
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=19 AND spr.f_num=ensures.f_status) new_status,
                    CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    CONCAT('<a href=\'?pgid=15&obid=',c.f_id,'\'>',spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')</a>') specref, -- sdid2730
                    c.f_tovar spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND ensures.f_objid=c.f_id AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND ensures.f_objid=c.f_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid 
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_ensures ensures, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                ensures.f_id=$operation_id AND (ensures.f_objtype=1 OR ensures.f_objtype=2) AND ensures.f_objid=c.f_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 5){ //Финансирование
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=96 AND spr.f_num=lns.f_status) new_status,
                    CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    c.f_tovar spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND lns.f_specid=c.f_id AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND lns.f_specid=c.f_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid 
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_lns lns, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    lns.f_id=$operation_id AND lns.f_specid=c.f_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 6){ //Операция
        $sql = "SELECT
                    si.f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=61 AND spr.f_num=si.f_status) new_status,
                    CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    c.f_tovar spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND si.f_specid=c.f_id AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND si.f_specid=c.f_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_spec_invoices si, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    si.f_id=$operation_id AND si.f_specid=c.f_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 7){ //Сертификат
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=23 AND spr.f_num=certificates.f_status) new_status,
                    CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
                    c.f_tovar spec_tovar,
                    (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND certificates.f_specid=c.f_id AND length(r.f_tn)>1) f_konosament,
                    (
                        SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                        FROM veda_ktk k, veda_routes r, veda_routes_ktk rk, veda_specs c
                        WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND certificates.f_specid=c.f_id
                    ) ktk_ts,
                    (
                        SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid
                    ) ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_certificates certificates, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    certificates.f_id=$operation_id AND certificates.f_specid=c.f_id AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
    else if($operation_type_id == 8){ //КТК
        $sql = "SELECT
                    0 f_cursoper,
                    case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                    #c.f_com_to, # sdid 2003 #sdid 2290
                    (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=37 AND spr.f_num=ktk.f_status) new_status,
                    GROUP_CONCAT(DISTINCT CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') SEPARATOR ';<br>') spec,
                    GROUP_CONCAT(DISTINCT c.f_tovar SEPARATOR '; ') spec_tovar,
                    ktk.f_num ktk_ts,
                    GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; '),
                    GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ') ts
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
                    veda_ktk ktk, veda_routes r, veda_routes_ktk rk, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                    ktk.f_id=$operation_id AND ktk.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid AND org.f_id=d.f_orgid AND d.f_id=c.f_dogid AND clnt.f_id=d.f_contrid AND spr.f_type=33 AND spr.f_num=c.f_typez";
    }
//sdid 887
    else if($operation_type_id == 9){ //Маршруты
//sdid 1214
        $sql = "select f_specid from veda_routes_spec where f_routeid=".$operation_id;
        $dbh = dbconnect();
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
//--------------------------
            if($issubj==1){$sqldop = "";}
            else{$sqldop = "<br>";}
            $sql = "SELECT
	         0 f_cursoper,
                 case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                 #c.f_com_to, # sdid 2003 #sdid 2290
                 (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=43 AND spr.f_num=rr.f_status) new_status,
                 GROUP_CONCAT(DISTINCT CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') SEPARATOR ';$sqldop') spec,
                 GROUP_CONCAT(DISTINCT c.f_tovar SEPARATOR ';<br>') spec_tovar,
	         ifnull(( SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                    WHERE r.f_postid=c.f_postid AND c.f_postid=rr.f_postid AND length(r.f_tn)>1
                 ),'') f_konosament,
                 ifnull(( SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                   FROM veda_ktk k, 
                        veda_routes r, 
                        veda_routes_ktk rk, 
                        veda_specs c
                   WHERE k.f_id=rk.f_ktkid 
                     AND r.f_id=rk.f_routeid 
		     AND r.f_postid=c.f_postid 
		     AND r.f_id=".$operation_id."
                 ),'') ktk_ts,
                 ifnull((
                   SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                   FROM veda_routes r
                   WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                 ),'') ts,
                 (select rh.f_com from veda_routes_hist rh where rh.f_id=rr.f_id and rh.f_hid=(select max(f_hid) from veda_routes_hist where f_id=rr.f_id) 
                 ) com,
                 DATE_FORMAT((select rh.f_dttmupd from veda_routes_hist rh 
                                                 where rh.f_id=rr.f_id 
                                                   and rh.f_hid=(select max(f_hid) from veda_routes_hist where f_id=rr.f_id) ), 
                            '%d.%m.%Y %H:%i'
                 ) dtupd,			
                 ifnull((select concat(u.f_name1, ' ', u.f_name2) from veda_users u where u.f_id=(select rh.f_userid from veda_routes_hist rh 
                                                                                                             where rh.f_id=rr.f_id 
                                                                                                               and rh.f_hid=(select max(f_hid) 
                                                                                                                               from veda_routes_hist where f_id=rr.f_id) )
                 ),'') username,
                 (select f_name from veda_spr where f_type=42 and f_num=rr.f_routetype) rtname      
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                FROM
	          veda_routes rr, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr, veda_routes_spec rs
                WHERE
                  rr.f_id=rs.f_routeid
                  AND c.f_postid=rr.f_postid 
                  AND org.f_id=d.f_orgid 
                  AND d.f_id=c.f_dogid 
                  AND clnt.f_id=d.f_contrid 
                  AND spr.f_type=33 
                  AND spr.f_num=c.f_typez
                  AND c.f_id=rs.f_specid
                  AND rs.f_routeid=".$operation_id;
//--------------------------
          }  
        else
          {
            $sql = "SELECT
	         0 f_cursoper,
                 case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
                 #c.f_com_to, # sdid 2003 #sdid 2290
                     (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=43 AND spr.f_num=rr.f_status) new_status,
                     GROUP_CONCAT(DISTINCT CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') SEPARATOR ';<br>') spec,
                     GROUP_CONCAT(DISTINCT c.f_tovar SEPARATOR ';<br>') spec_tovar,
	         ( SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                        FROM veda_routes r
                        WHERE r.f_postid=c.f_postid AND c.f_postid=rr.f_postid AND length(r.f_tn)>1
                     ) f_konosament,
                     ( SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                       FROM veda_ktk k, 
                            veda_routes r, 
                            veda_routes_ktk rk, 
                            veda_specs c
                       WHERE k.f_id=rk.f_ktkid 
                             AND r.f_id=rk.f_routeid 
			 AND r.f_postid=c.f_postid 
			 AND r.f_id=".$operation_id."
                     ) ktk_ts,
                     (
                       SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                       FROM veda_routes r
                       WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                     ) ts,
                     (select rh.f_com from veda_routes_hist rh where rh.f_id=rr.f_id and rh.f_hid=(select max(f_hid) from veda_routes_hist where f_id=rr.f_id) 
                     ) com,
                     DATE_FORMAT((select rh.f_dttmupd from veda_routes_hist rh 
                                                     where rh.f_id=rr.f_id 
                                                       and rh.f_hid=(select max(f_hid) from veda_routes_hist where f_id=rr.f_id) ), 
                                '%d.%m.%Y %H:%i'
                     ) dtupd,			
                     (select concat(u.f_name1, ' ', u.f_name2) from veda_users u where u.f_id=(select rh.f_userid from veda_routes_hist rh 
                                                                                                                 where rh.f_id=rr.f_id 
                                                                                                                   and rh.f_hid=(select max(f_hid) 
                                                                                                                                   from veda_routes_hist where f_id=rr.f_id) )
                     ) username,
                     (select f_name from veda_spr where f_type=42 and f_num=rr.f_routetype) rtname      
                    ".$sqlGetShipmentInfo."
                    ".$sqlGetOperTO."
                    ".$sqlGetOperID."
                    ".$sqlGetComIsPrep."
                    ".$sqlBlockUvedTO1."
                    FROM
	          veda_routes rr, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                    WHERE
                      rr.f_id=".$operation_id." 
                      AND c.f_postid=rr.f_postid 
                      AND org.f_id=d.f_orgid 
                      AND d.f_id=c.f_dogid 
                      AND clnt.f_id=d.f_contrid 
                      AND spr.f_type=33 
                      AND spr.f_num=c.f_typez";
          }



/*        $sql = "SELECT
	         0 f_cursoper,
                 (SELECT spr.f_name FROM veda_spr spr WHERE spr.f_type=43 AND spr.f_num=rr.f_status) new_status,
                 GROUP_CONCAT(DISTINCT CONCAT(spr.f_dopprstr, ' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') SEPARATOR ';<br>') spec,
                 GROUP_CONCAT(DISTINCT c.f_tovar SEPARATOR ';<br>') spec_tovar,
	         ( SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
                    FROM veda_routes r
                    WHERE r.f_postid=c.f_postid AND c.f_postid=rr.f_postid AND length(r.f_tn)>1
                 ) f_konosament,
                 ( SELECT GROUP_CONCAT(DISTINCT k.f_num SEPARATOR '; ')
                   FROM veda_ktk k, 
                        veda_routes r, 
                        veda_routes_ktk rk, 
                        veda_specs c
                   WHERE k.f_id=rk.f_ktkid 
                         AND r.f_id=rk.f_routeid 
			 AND r.f_postid=c.f_postid 
			 AND r.f_id=".$operation_id."
                 ) ktk_ts,
                 (
                   SELECT GROUP_CONCAT(DISTINCT r.f_carnum SEPARATOR '; ')
                   FROM veda_routes r
                   WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
                 ) ts
                FROM
	          veda_routes rr, veda_specs c, veda_clients org, veda_dogs d, veda_clients clnt, veda_spr spr
                WHERE
                  rr.f_id=".$operation_id." 
                  AND c.f_postid=rr.f_postid 
                  AND org.f_id=d.f_orgid 
                  AND d.f_id=c.f_dogid 
                  AND clnt.f_id=d.f_contrid 
                  AND spr.f_type=33 
                  AND spr.f_num=c.f_typez";*/
//~sdid 1214
    }
//~sdid 887
  // sdid 2023
  elseif($operation_type_id == 10) // Корректировка
    {
    $sql = "SELECT
              0 f_cursoper,
              case when c.f_parentspecid>0 and c.f_subtype=3 then (select f_com_to from veda_specs where f_id=c.f_parentspecid) else c.f_com_to end f_com_to, #sdid 2290
              #c.f_com_to, # sdid 2003 #sdid 2290
              CONCAT('<a href=# onclick=".site_name."?pgid=290&obid=',cor.f_id,'>корректровке №',cor.f_num,'</a>') f_num, 
              (SELECT f_name FROM veda_spr179 spr WHERE spr.f_num=cor.f_status) new_status,
              CONCAT(spr.f_dopprstr,' ',d.f_dogname,'/',c.f_num,'/',org.f_abbr,'/',clnt.f_cname,' (ИД ',c.f_id,')') spec,
              CASE WHEN c.f_subtype=4 THEN '-' ELSE c.f_tovar END spec_tovar,
              (SELECT GROUP_CONCAT(DISTINCT r.f_tn SEPARATOR '; ')
               FROM veda_routes r
               WHERE r.f_postid=c.f_postid AND length(r.f_tn)>1) f_konosament,
                 (
                 SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ')
                 FROM veda_ktk k,veda_routes r,veda_routes_ktk rk
                 WHERE k.f_id=rk.f_ktkid AND r.f_id=rk.f_routeid AND r.f_postid=c.f_postid
                 ) ktk_ts,
              (
               SELECT GROUP_CONCAT(distinct r.f_carnum SEPARATOR '; ')
               FROM veda_routes r
               WHERE r.f_postid=c.f_postid and length(r.f_carnum)>0
              ) ts
              ".$sqlGetShipmentInfo."
              ".$sqlGetOperTO."
              ".$sqlGetOperID."
              ".$sqlGetComIsPrep."
              ".$sqlBlockUvedTO1."
            FROM
              veda_specs c, veda_dogs d, veda_clients clnt, veda_clients org, veda_spr spr, veda_corrects cor 
            WHERE
              spr.f_type=33 AND spr.f_num=c.f_typez AND org.f_id=d.f_orgid AND clnt.f_id=d.f_contrid AND d.f_id=c.f_dogid AND c.f_id=cor.f_specid AND cor.f_id=$operation_id";
    }
    // ~ sdid 2023
  return $sql;
  }

function getExtendedInfoTemplate($mail_template, $operation_type_id, $operation_id, $issubj=0)
  {
  //if($_SESSION['loginid']==2){echo $sql."|";}
  /*
  Получаем шаблон письма с расширенной информацией по (см. $operation_types):
  Тип операции: {operation_type}
  Новый статус записи: {new_status}
  Спецификация: {spec}
  Товар из карточки спецификации: {spec_tovar}
  Номер КТК/Номер ТС: {ktk_ts}
  */
//sdid 887

/*  $operation_types = array(
    0 => "Не задана",
    1 => "Информация о ТО",
    2 => "Доставка",
    3 => "Спецификация",
    4 => "Страховка",
    5 => "Финансирование",
    6 => "Операция",
    7 => "Сертификат",
    8 => "КТК"
    );
*/
  $operation_types = array(
    0 => "Не задана",
    1 => "Информация о ТО",
    2 => "Доставка",
    3 => "Спецификация",
    4 => "Страховка",
    5 => "Финансирование",
    6 => "Операция",
    7 => "Сертификат",
    8 => "КТК",
    9 => "Маршрут"
    ,10=>"Корректировка" // sdid 2023
    );

//~sdid 887

  $sql = getExtendedInfoQuery($operation_type_id, $operation_id, $issubj);
  //if($_SESSION['loginid']==2){echo $operation_type_id."|".$operation_id."|".$sql."|";}
  //echo "|-|".$operation_type_id."|".$operation_id."|".$sql."|-|";
  try
    {
    if(strlen($sql)>0)
      {
      $dbh = dbconnect();
      $res = $dbh->query($sql);
      
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $cursoper = "";
        if($row['f_cursoper']>0){$cursoper = "Курс покупки: ".$row['f_cursoper'];}
        $mail_template = str_replace("{operation_type}", $operation_types[$operation_type_id],
                           str_replace("{new_status}", $row['new_status'],
                             str_replace("{spec}", $row['spec'],
                               str_replace("{spec_tovar}", $row['spec_tovar'],
                                 //sdid - 381
                                 str_replace("{f_konosament}", $row['f_konosament'],
                                 //~sdid - 381
                                   str_replace("{ktk_ts}", $row['ktk_ts']." ".$row['ts'], 
                                     str_replace("{cursoper}", $cursoper, $mail_template)))))));
        // sdid 2023
        $mail_template = str_replace("{correct_new_status}",$row['new_status'],$mail_template);
        if(isset($row['f_num']))
          {$mail_template = str_replace("{correct_num}",$row['f_num'],$mail_template);}
        // ~ sdid 2023
        $mail_template = str_replace("{comto}", $row['f_com_to'], $mail_template); // sdid 2003
        // sdid 1539
        $shipment_path = "<br>Путь поставки: не определен";
        $path_data = $row['shipment_path'];
        if (isset($path_data))
          {
          if (strlen($path_data)>0)
            {$shipment_path = "<br>Путь поставки: ".$path_data;}
          }
        $shipment_arrival = "<br>Дата прибытия: отсутствует";
        $arrival_data = $row['shipment_arrival'];
        if (isset($arrival_data))
          {
          if (strcmp($arrival_data, "0000-00-00")!=0)
            {$shipment_arrival = "<br>Дата прибытия поставки: ".$arrival_data;}
          }
        $mail_template = str_replace("{shipment_arrival}",$shipment_arrival, $mail_template);
        $mail_template = str_replace("{shipment_path}",$shipment_path, $mail_template);
        // ~ sdid 1539
        // sdid 1553
        $operto = "<br>Ответственный сотрудник ТО: не назначен";
        $operto_data = $row['operto'];
        if (isset($operto_data))
          {
          if (strlen($operto_data)>0)
            {$operto = "<br>Ответственный сотрудник ТО: " . $operto_data;}
          }
        $mail_template = str_replace("{operto}",$operto, $mail_template);
        // ~ sdid 1553
//sdid 1214
        if($operation_type_id==9)
          {
          $mail_template = str_replace("{dtupd}",$row['dtupd'], $mail_template);
          $mail_template = str_replace("{com}",$row['com'], $mail_template);
          $mail_template = str_replace("{username}",$row['username'], $mail_template);
          $mail_template = str_replace("{rtname}",$row['rtname'], $mail_template);
          $mail_template = str_replace("{routeid}",$operation_id, $mail_template);
          //$mail_template = str_replace("{routeid}","<a href=\"".$redirect_uri."?pgid=77&obid=".$operation_id."\">".$operation_id."</a><br>", $mail_template);
          }
//~sdid 1214
        // sdid 2730
        if($operation_type_id==4)
          {
          $mail_template = str_replace("{ensure}",$operation_id, $mail_template);
          $mail_template = str_replace("{f_sumins}",number_format($row['f_sumins'], 2, '.', ' '), $mail_template);
          $mail_template = str_replace("{suminsval}",$row['suminsval'], $mail_template);
          $mail_template = str_replace("{f_sumens}",number_format($row['f_sumens'], 2, '.', ' '), $mail_template);
          $mail_template = str_replace("{f_sumens1}",number_format($row['f_sumens1'], 2, '.', ' '), $mail_template);
          $mail_template = str_replace("{f_sumens2}",number_format($row['f_sumens2'], 2, '.', ' '), $mail_template);
          $mail_template = str_replace("{sitename}",site_name, $mail_template);
          $mail_template = str_replace("{sumensval}",$row['sumensval'], $mail_template);
          $mail_template = str_replace("{spec}",$row['spec'], $mail_template);
          $mail_template = str_replace("{specid}",$row['specid'], $mail_template);
          }
        // ~ sdid 2730
//sdid 1690
$mail_template = str_replace("{manager}",$row['operid'], $mail_template);
$mail_template = str_replace("{comyesno}",$row['comisprep'], $mail_template);
//~sdid 1690
        //sdid 2084 tz2 2 
        $mail_template = str_replace("{blockuvedto1}",$row['blockuvedto1'], $mail_template);
        //sdid 2084 tz2 2 
        }
      }
    //return ($template_style . $mail_template);
    return ($mail_template);
    }
  catch(Exception $e)
    {
    return $mail_template;
    }
  }
//~sdid - 164

//тело шаблона для операции
function lGetPostShablon($temple,$dogid,$val,$oprsum,$invid, $operation_type_id=0, $operation_id=0)
  {
  $tmpsh = "";
  //sdid - 164
  if($temple != 0 && $temple != ''){
    $tmpsh = getPostShablon($temple);
    }
  //~sdid - 164
  //if($_SESSION['loginid']==2){echo $temple."|".$tmpsh."|";}
  if(strlen($tmpsh)>0)
    {
    //sdid - 164
    if($temple == 11)
      {
      $tmpsh = getExtendedInfoTemplate($tmpsh, $operation_type_id, $operation_id);
      }
    elseif($temple == 12)
      {
      $sql = "
              SELECT 
                concat(
                  case 
                    when ctl.f_valstr='0' then 'Сторнируемая операция: '
                    when ctl.f_valstr='1' then 'Сторнирующая операция: '
                    when ctl.f_valstr='2' then 'Корректирующся операция: '
                    else ''
                  end,
                  concat('<a href=\'#\' title=\'\' onclick=formCtgList(35,0,0,0,',sil.f_id,')>',
                    case 
                      when sil.f_parentid=4 then (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=sil.f_specid) 
                      else (select concat(d.f_dogname,'/',ss.f_num,'/',cl.f_cname) 
                            from ".DBPref."specs ss,".DBPref."dogs d,".DBPref."clients cl 
                            where cl.f_id=d.f_contrid and ss.f_dogid=d.f_id and ss.f_id=sil.f_specid) 
                    end,'/',substr(sil.f_dttmcr,1,10),'/',sil.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=sil.f_idoper),'/',sil.f_sum,' ',
                    (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=sil.f_val),'/',sil.f_id,'</a>'),
                    case 
                      when ctl.f_valstr='2' then concat('Закрывающий документ: ',(select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',
                                                   a.f_sum,' ',(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=a.f_val),' (',
                                                   (select f_name from ".DBPref."spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                                                      DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>') 
                                                    from ".DBPref."akts a, ".DBPref."akts_details ad,".DBPref."akts_details_opers ado 
                                                    where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=sil.f_id))
                      else ''
                    end) opern
              FROM (".DBPref."categs ct,".DBPref."spec_invoices si) 
              left join ".DBPref."categs ctl on ctl.f_ctgtype=22 and ctl.f_objecttype=5 and ctl.f_valint=ct.f_valint
              LEFT JOIN ".DBPref."spec_invoices sil on sil.f_id=ctl.f_objectid
              where ct.f_ctgtype=22 and ct.f_objecttype=5 and ct.f_objectid=si.f_id and si.f_id=".$operation_id."
              ";
      //echo $sql."|";
      $ltmpsh = "\n";
      $dbh = dbconnect();
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $ltmpsh = $ltmpsh.$row['opern']."\n";
        }
      $tmpsh = str_replace("{opern}",$ltmpsh,$tmpsh);
      //echo $tmpsh."|";
      }
    //sdid 1214
    elseif($temple == 13)
      {
         $tmpsh = getExtendedInfoTemplate($tmpsh, $operation_type_id, $operation_id);
      }
    //~sdid 1214
    // sdid 2023
    elseif($temple == 14)
      {
      $tmpsh = getExtendedInfoTemplate($tmpsh, $operation_type_id, $operation_id);
      }
    // ~ sdid 2023
    // sdid 2730
    elseif($temple == 15)
      {
      $tmpsh = getExtendedInfoTemplate($tmpsh, $operation_type_id, $operation_id);
      }
    // ~ sdid 2730
    else
      {
      //~sdid - 164
      //sdid 3173
      //$sql = "select (select f_dogname from ".DBPref."dogs where f_id=".$dogid.") contrnum,".
      //    "       (select f_namedop from ".DBPref."spr where f_type=4 and f_num=".$val.") val, ".
      //    "       (select concat(s.f_sum,'/',(SELECT f_name FROM ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_num,'/',s.f_dt,'/',(select f_cname FROM ".DBPref."clients where f_id=s.f_contrid)) from ".DBPref."schets s where s.f_id=".$invid.") invid ".
      //    "from dual";
      /*$sql = "select (select f_dogname from ".DBPref."dogs where f_id=".$dogid.") contrnum,
                     (select f_namedop from ".DBPref."spr where f_type=4 and f_num=".$val.") val, 
                     (select concat(s.f_sum,'/',(SELECT f_name FROM ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_num,'/',s.f_dt,'/',(select f_cname FROM ".DBPref."clients where f_id=s.f_contrid)) from ".DBPref."schets s where s.f_id=".$invid.") invid,
                     (select ifnull(concat('Счет ',b.f_bankname,'/',v.f_dopprstr,'/',a.f_acc),'')  
                      from veda_bank_accounts a,veda_banks b,veda_spr v, veda_spec_invoices si 
                      where v.f_type=4 and v.f_num=a.f_val and b.f_bic=a.f_bic and a.f_id=si.f_orgaccid and si.f_id=$operation_id) schetorg
              from dual";*/
      $sql = "select (select f_dogname from ".DBPref."dogs where f_id=".$dogid.") contrnum,
                     (select f_namedop from ".DBPref."spr where f_type=4 and f_num=".$val.") val, 
                     (select concat(s.f_sum,'/',(SELECT f_name FROM ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_num,'/',s.f_dt,'/',(select f_cname FROM ".DBPref."clients where f_id=s.f_contrid)) from ".DBPref."schets s where s.f_id=".$invid.") invid,
                     (select ifnull(b.f_bankname,'')  
                      from veda_bank_accounts a,veda_banks b,veda_spr v, veda_spec_invoices si 
                      where v.f_type=4 and v.f_num=a.f_val and b.f_bic=a.f_bic and a.f_id=si.f_orgaccid and si.f_id=$operation_id) schetorg
              from dual";
      //~sdid 3173
      //echo $sql."|";
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      $dbh->exec('SET CHARACTER SET utf8');
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $dirs = "Путь поставки: ";
        //sdid 3173
        $paymentinfo = ""; 
        $schetorg = ""; 
        if($temple==2) {$schetorg = $row['schetorg'];} 
        //~sdid 3173
        if($invid>0)
          {
          $sql  = "select ifnull((select f_num from ".DBPref."schets where f_operid=i.f_id and f_type=2 and f_doptype=1),i.f_invnum) invnum, ".
              "  case when i.f_parenttype=2 then ".
              "    (select f_dirmain from ".DBPref."specs where f_id=i.f_specid) ".
              "  else '_' end dirmain ".
              "from ".DBPref."spec_invoices i where i.f_id=".$operation_id;
          $res1 = $dbh->query($sql);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            $dirs=$row1["invnum"]." ".$dirs.$row1["dirmain"];
            }
          //sdid 3173
          if($temple==2)
            {
            $sql = "select ifnull(GROUP_CONCAT((select concat(b.f_bankname,'/',v.f_dopprstr,'/',a.f_acc,' сумма ',FORMAT(ahd.f_clssum,2,'ru_RU')) 
                                                from veda_bank_accounts a,veda_banks b,veda_spr v 
                                                where v.f_type=4 and v.f_num=a.f_val and b.f_bic=a.f_bic and a.f_id=ah.f_accid) SEPARATOR ', '),'') paymentinfo
                    from veda_acchist ah, veda_acchist_docs ahd 
                    where ah.f_id=ahd.f_acchistid and ahd.f_doctype=1 and ahd.f_docid=$invid";
            $res2 = $dbh->query($sql);
            if($row2 = $res2->fetch(PDO::FETCH_ASSOC)) {$paymentinfo=$row2['paymentinfo'];}
            }
          //~sdid 3173
          }
        //sdid 3173
        //$tmpsh=
        //    str_replace("{invoicenumvalbuy}",$row['invid'],
        //        str_replace("{directioninv}",$dirs,
        //            str_replace("{direction}",$dirs,
        //                str_replace("{invoicenum}",$row['invid'],
        //                    str_replace("{contrnum}",$row['contrnum'],
        //                        str_replace("{val}",$row['val'],
        //                            str_replace("{sum}",number_format($oprsum,2, ',', ' '),$tmpsh)))))));
        $tmpsh=
            str_replace("{invoicenumvalbuy}",$row['invid'],
                str_replace("{directioninv}",$dirs,
                    str_replace("{direction}",$dirs,
                        str_replace("{invoicenum}",$row['invid'],
                            str_replace("{contrnum}",$row['contrnum'],
                                str_replace("{val}",$row['val'],
                                    str_replace("{paymentinfo}",$paymentinfo,
                                        str_replace("{schetorg}",$schetorg,
                                            str_replace("{sum}",number_format($oprsum,2, ',', ' '),$tmpsh)))))))));
        //~sdid 3173
        }
      }
    }
  return $tmpsh;
  }

//sdid 703
//тело шаблона темы (Subject) для операции
function lGetPostSubjShablon($temple,$dogid,$val,$oprsum,$invid, $operation_type_id=0, $operation_id=0)
  {
  $tmpsh = "";

  if($temple != 0 && $temple != ''){
    $tmpsh = getPostSubjShablon($temple);
    }
  if(strlen($tmpsh)>0)
    {
    //if($temple == 1)
    //  {
      //echo $tmpsh."|".$operation_type_id."|".$operation_id;
      $tmpsh = getExtendedInfoTemplate($tmpsh, $operation_type_id, $operation_id, 1);
    //  }
    }
  return $tmpsh;
  }
//~sdid 703


//sdid 887
// Отправить письмо-уведомление, используя шаблоны Темы и Тела письма
// operId   - Тип операции из lib.php -> getExtendedInfoTemplate() 
// $uvedNum - Номер Уведомления из справочника "Настройка уведомлений"
// $sprId - Номер справочника: Справочники -> Статусы + Настройка уведомлений. Разрпабатываем пока функционал по Настройка уведомлений
// $menuId - id таблицы меню
// $curidx - id записи таблицы меню
  function mSendMailByTemplates($userId, $subject, $postbody, $menuId, $curidx, $sprId, $operId, $uvedNum)
           //mSendMailByTemplates($_SESSION['loginid'], $subject, $postbody, 77, $curidx, 151, 9, 1); }                     
  {
    $mgrp = 0; // id группы, на которую отправять
    $musr = 0; // id пользователя, на которого отправять

    try
    {
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      $dbh->exec('SET CHARACTER SET utf8');

      $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=".$sprId." and f_num=".$uvedNum;
      // sdid 2023
      if($menuId == 290)
        {
        $sql = "select  f_name, 
                         f_alert f_uslint, 
                         f_recipient f_namedop, 
                         f_rectype f_uslstr, 
                         f_template f_dopprint, 
                         f_subjtemplate f_dopprstr1 
                  from ".DBPref."spr179 where f_num=".$uvedNum;
        }
      // ~ sdid 2023
      $res = $dbh->query($sql);
      //$i = 0;
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        if($row['f_uslint']==1) // выставлен флаг использовать этот шаблон
          {
            if(strcmp($row['f_uslstr'],"2")==0)
              {$mgrp=$row['f_namedop']; $musr=0;}
            else
              {$mgrp=0; $musr=$row['f_namedop'];}

            if($row['f_dopprstr1']>0 && $operId>0 && $curidx>0) // шаблон Subject
              { $subject = $subject . lGetPostSubjShablon($row['f_dopprstr1'], 0, 0, 0, 0, $operId, $curidx);}

            if($operId>0 && $curidx>0) // шаблон Body
              { $postbody = $postbody . "<br>" . lGetPostShablon($row['f_dopprint'], 0, 0, 0, 0, $operId, $curidx);}

            mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",$menuId,$curidx);
          }
          else
          {
            return 0; 
          }
          //$i++;
        }
        //if($i == 0) {return 0;}
      else
        {
          return 0;
        }
    } 
    catch (PDOException $e)
    {
      return 0;
    }
    return 1;
  }
//~sdid 887


//получить таблицу данных об операциях
function getTblUslVal($fid,$djspar)
{
    $usls = "<table><tr><td colspan=6></td></tr></table>";
    try
    {
        $redtbl = 0;
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            if(isset($djspar['redtbl'])){$redtbl = $djspar['redtbl'];}
        }
        if($fid>0)
        {
            $usls = "";
            $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
            $dbh->exec('SET CHARACTER SET utf8');
            $sql1 = "select i.f_idoper,i.f_com,i.f_sum,i.f_val,i.f_idoper,i.f_id,i.f_isvozm,".
                "  (select f_name from ".DBPref."typeopers where f_id=i.f_idoper) type, ".
                "  (select f_name from ".DBPref."spr where f_type=4 and f_num=i.f_val) val ".
                "from ".DBPref."spec_invoices i where i.f_parenttype=1 and i.f_specid=".$fid." order by i.f_num_oper";
            //echo $sql1."<br>";
            $res1 = $dbh->query($sql1);
            $i1=0;
            //$usls = $usls."<table>";
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
                $vozm = "";
                if($row1['f_isvozm']==1){$vozm = "checked";}
                $usls = $usls."<tr    id='getTblUslValtr".$i1."'>".
                    "<td id='getTblUslValtr".$i1."td0'><input id='getTblUslValtr".$i1."td0v' style='width: 80px;' disabled value='".$row1['f_id']."'/></td>".
                    "<td id='getTblUslValtr".$i1."td1'>".getSelOperListVal("usl".$i1,1,1,0,0,0,"width: 270px;",$row1['f_idoper'],0,0)."</td>".
                    "<td id='getTblUslValtr".$i1."td2'><input id='getTblUslValtr".$i1."td2v' style='width: 270px;' value='".$row1['f_com']."'/></td>".
                    "<td id='getTblUslValtr".$i1."td3'><input id='getTblUslValtr".$i1."td3v' type='number' min='0.00' step='0.01' style='width: 120px;text-align:right;' value='".$row1['f_sum']."'/></td>".
                    "<td id='getTblUslValtr".$i1."td4'>".getSelSprVal(-1,4,"",1,"","uslv".$i1,"style='width: 80px;'",$row1['f_val'],0,0,"")."</td>".
                    "<td id='getTblUslValtr".$i1."td5'><input id='getTblUslValtr".$i1."td5v' type='checkbox' style='width: 60px;' ".$vozm."/></td>".
                    "<td colspan=1 style='text-align:center;width: 60px;'>".
                    "<a href='#' onclick=delCurEl(document.getElementById('getTblUslValtr".$i1."')) title='Удалить контакт')>".getSpanStrOpr("minus")."</a>".
                    //"onclick=alert('getTblAddrValtr".$i1."')/></td></tr>";}
                    "</td></tr>";
                $i1++;
            }
            //$usls = $usls."</table>";
        }
        return $usls;
    }
    catch (PDOException $e)
    {
        return "<table><tr><td colspan=6>".$e->getMessage()."</td></tr></table>";
    }
}

//получить таблицу адресных данных
function getTblAddrVal($cntid,$djspar)
{
    $contacts = "";
    try
    {
        $redtbl = 0;
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            if(isset($djspar['redtbl']))
            {$redtbl = $djspar['redtbl'];}
        }
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        if($redtbl==0)
        {$contacts = $contacts."<a href='#' onclick=formAdrList(1,".$cntid.")><table>";}
        elseif($redtbl==2)
        {$contacts = $contacts."";}
        $sql1 = "select a.f_type,a.f_addr,a.f_descr,a.f_id,".
            "  (select f_name from ".DBPref."spr where f_type=58 and f_num=a.f_type) type ".
            "from ".DBPref."addres a where (a.f_clnttype=2 and ".
            "  a.f_clntid in (select f_id from ".DBPref."clients where f_contactid=".$cntid.")) or (a.f_clnttype=1 and a.f_clntid=".$cntid.") ";
        //echo $sql1."<br>";
        $res1 = $dbh->query($sql1);
        $i1=0;
        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
        {
            if($redtbl==0)
            {$contacts = $contacts."<tr><td title='".$row1['type']."'>".$row1['type']."</td><td title='".$row1['f_addr']."'>".$row1['f_addr']."</td><td title='".$row1['f_descr']."'>".$row1['f_descr']."</td></tr>";}
            elseif($redtbl==1)
            {$contacts = $contacts."<tr id='getTblAddrValtr".$i1."'>".
                "<td id='getTblAddrValtr".$i1."td0'><input style='width: 60px;' id='getTblAddrValtr".$i1."td0v' disabled value='".$row1['f_id']."'/></td>".
                "<td id='getTblAddrValtr".$i1."td1'>".getSelSprVal(-1,58,"",1,"","adr".$i1,"",$row1['f_type'],0,0,"")."</td>".
                "<td id='getTblAddrValtr".$i1."td2'><input style='width: 250px;' id='getTblAddrValtr".$i1."td2v' value='".$row1['f_addr']."'/></td>".
                "<td id='getTblAddrValtr".$i1."td3'><input style='width: 300px;' id='getTblAddrValtr".$i1."td3v' value='".$row1['f_descr']."'/></td>".
                "<td colspan=1 style='text-align:center;width: 100%;'>".
                "<a href='#' onclick=delCurEl(document.getElementById('getTblAddrValtr".$i1."')) title='Удалить контакт')>".getSpanStrOpr("minus")."</a>".
                //"onclick=alert('getTblAddrValtr".$i1."')/></td></tr>";}
                "</td></tr>";}
            elseif($redtbl==2)
            {$contacts = $contacts.$row1['type'].": ".$row1['f_addr']."; ".$row1['f_descr']."\n";}
            $i1++;
        }
        if($redtbl==0)
        {$contacts = $contacts."</table></a>";}
        elseif($redtbl==2)
        {$contacts = $contacts."";}
        return $contacts;
    }
    catch (PDOException $e)
    {
        return "<table><tr><td colsapn=4 >".$e->getMessage()."</td></tr></table>";
    }
}

//создать <select> для выбора параметров
function getSelPrmVal($typ,$did,$ord)
{
    try
    {
        if($typ>0){$typ = " and s.f_type in (".$typ.") ";}
        else{$typ = "";}
        if($did>0){$did = " and s.f_id in (".$did.") ";}
        else{$did = "";}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT s.f_id,s.f_name FROM ".DBPref."prms s where s.f_id>=0 ".$typ." ".$did." ".$ord;
        //echo $sql;
        $response = "<select>";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['f_name']."</option>";}
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}
//создать <select> для выбора спецификаций
function getSelSpecVal1($curNum,$tp,$ord,$did,$wnz)
{
    try
    {
        if($wnz==1){$wnz="";$swnz="s.f_id>0";}
        else{$wnz="0,";$swnz="s.f_id>=0";}
        if(strlen($curNum)>0){$curNum = " and s.f_id in (".$wnz."".$curNum.") ";}
        else{$curNum = "";}
        if(strlen($tp)>0){$tp = " and s.f_typez in (".$tp.") ";}
        else{$tp = "";}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $response = "<select class='selval' id='getSelSpecVal".$did."'>";
        $sql = "SELECT s.f_id,(concat((select f_dogname from ".DBPref."dogs where f_id=s.f_dogid),'/',s.f_num)) name FROM ".DBPref."specs s where ".$swnz." ".$curNum." ".$tp." ".$ord;
        //$sql = "SELECT f_id,concat(f_bic,'/',f_acc) f_cname FROM ".DBPref."bank_accounts where f_id>0 ".$curNum." ".$accid." ".$ufid." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['name']."</option>";}
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSpecVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора счетов
//sdid 721
//function getSelSchetsVal($orgid,$contrid,$ord,$did,$mstyle,$fid,$wnz)
function getSelSchetsVal($orgid,$contrid,$ord,$did,$mstyle,$fid,$wnz,$selid=0,$wlimcnt=0)
//~sdid 721
  {
  try
    {
    $ctxt = "";
    $sfid = "";
    $nosubschet = 0;
    if(isset($fid))
      {
      //echo $fid."<br>";
      if(strlen($fid)>0)
        {
        if(strcmp(substr($fid,0,1),"{")==0)
          {
          $djspar = json_decode($fid,true);
          if(isset($djspar['ctxt']))
            {
            $ctxt = $djspar['ctxt'];
            }
          if(isset($djspar['sfid']))
            {
            $sfid = " and s.f_id in (".$djspar['sfid'].") ";
            }
          if(isset($djspar['nosubschet']))
            {
            $nosubschet = $djspar['nosubschet'];
            }
          }
        else
          {
          if(strcmp($fid,"0")==0)
            {$sfid = "";}
          else
            {$sfid = " and s.f_id in (".$fid.") ";}
          }
        }
      else
        {$sfid = "";}
      }
    //echo $sfid."<br>";
    $sorgid = "";
    if($orgid>0){$sorgid = " and s.f_orgid=".$orgid." ";}
    $scontrid = "";
    if($contrid>0){$scontrid = " and s.f_contrid=".$contrid." ";}
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}
    $swlimcnt = "";
    if(($wlimcnt>0)&&($selid==0)&&($fid==0)){$swlimcnt = " limit ".$wlimcnt." ";}
    $dbh = dbconnect();
    //получаем список для выборки
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $response = "<select class='selval' ".$smstyle." id='getSelSchetsVal".$did."'>";
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    $nosubschetsql = "";
    if($nosubschet==1)
      {$nosubschetsql=" and s.f_maininv=0 ";}
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {
        $sql = "select s.f_id,".
             "  '-' cres ".
             "from ".DBPref."schets s where s.f_id=0 ".$sfid." ".$sorgid." ".$scontrid." ".$ord." ".$swlimcnt;
        }
      else
        {
        $sql = "select * from (".
             "select s.f_id,".
             "  concat(s.f_sum,' ',(SELECT f_uslstr FROM ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_num,'/',".
             "    DATE_FORMAT(s.f_dt,'%d.%m.%Y'),'/',(select f_cname FROM ".DBPref."clients where f_id=s.f_contrid),'/',s.f_id) cres ".
             "from ".DBPref."schets s where s.f_id>=0 ".$nosubschetsql." ".$sfid." ".$sorgid." ".$scontrid." ".$ord." ".$swlimcnt.") k where k.cres like '%".$ctxt."%'";
        }
      }
    else
      {
      $sql = "select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, ".
          "  (SELECT f_uslstr FROM ".DBPref."spr where f_type=4 and f_num=s.f_val) val, ".
          "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid ".
          "from ".DBPref."schets s where s.f_id>=0 ".$nosubschetsql." ".$sfid." ".$sorgid." ".$scontrid." ".$ord." ".$swlimcnt;
      }
    //if(isset($_SESSION['loginid']))
    //  {if($_SESSION['loginid']==2)
    //     {echo $sql."<br>";}}
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      if(strlen($ctxt)>0)
        {$cres = $row['cres'];}
      else
        {$cres = $row['f_sum']." ".$row['val']."/".$row['f_num']."/".$row['f_dt']."/".$row['contrid']."/".$row['f_id'];}
//sdid 721
      $sels = "";
      if(isset($selid))
        {
          if($row['f_id']==$selid) {$sels = "selected";}
        }
/*      $response = $response.
        "<option value='".$row['f_id']."'>".
          $cres.
        "</option>";
*/
//      $response = $response."<option value='".$row['f_id']."'>".$cres."</option>";
        $response = $response."<option ".$sels." value='".$row['f_id']."'>".$cres."</option>";
//~sdid 721
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelSchetsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора детализации счетов
function getSelSchetsDetailsVal($ord,$did,$mstyle,$fid,$wnz)
{
    try
    {
        $sfid = "";
        if(isset($fid)){if($fid>0){$sfid = " and s.f_id=".$fid." ";}}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $response = "<select class='selval' ".$smstyle." id='getSelSchetsDetailsVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        $sql = "select s.f_id,s.f_sum,s.f_grnd ".
            "from ".DBPref."schets_details s where s.f_id>=0 ".$sfid." ".$ord;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['f_sum']."/".$row['f_grnd']."/".$row['f_id']."</option>";}
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSchetsDetailsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора детализации актов
function getSelAktsDetailsVal($ord,$did,$mstyle,$fid,$wnz)
{
    try
    {
        $sfid = "";
        if(isset($fid)){if($fid>0){$sfid = " and s.f_id=".$fid." ";}}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $response = "<select class='selval' ".$smstyle." id='getSelAktsDetailsVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        $sql = "select s.f_id,
                  case 
                    when s.f_nds<>3 and 
                     (select sum((lad.f_sum+lad.f_ndssum)) from ".DBPref."akts_details lad where lad.f_aktid=s.f_aktid)>
                     (select a.f_sum from ".DBPref."akts a where a.f_id=s.f_aktid) then s.f_sum
                    else (s.f_sum+s.f_ndssum)
                  end asm,s.f_grnd ".
            "from ".DBPref."akts_details s where s.f_id>=0 ".$sfid." ".$ord;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['asm']."/".$row['f_grnd']."/".$row['f_id']."</option>";}
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelAktsDetailsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора операций
function getSelSpecsInvVal($ord,$did,$mstyle,$fid,$wnz,$orgid,$djspar)
  {
  try
    {
    $seloprval  = 0;//показывает вид выбранной прочей операций при выборе специфкации в форме прочей операции
    $ppv        = 0;//признак отбора операций покупки валюты, по которым есть остаток не переведенной валюты, для специфкации
    $selsprval  = 0;//значение выбранного значения справочника валюты
    $selspecinvval = 0;//значение выбранной операции-партнера
    $specid     = "";
    $wsr        = 0;
    $opspec     = 0;
    $kvitselect = " "; // ID-73
    $kvitgroup  = " ";
    $ctxt       = "";
    $specclntid = "";
    $subtype    = "";
    $selid      = 0; //sdid 3047
    $disableds  = "";//sdid3047
    $disabled   = 0; //sdid3047
    $sitypelist = ""; //sdid 3596
    $sinewoper    = 0; //sdid 3596
    $siparmslink = 0; //операция, из которой будем брать izvozm и nds //sdid 3596

    //error_log("\n\ndjspar = $djspar\n\n",0);
    //error_log("\n\nfid = $fid\n\n",0);
    //error_log("\n\norgid = $orgid\n\n",0);

    if(strlen($djspar)>0)
      {
      //echo "$djspar<br>";
      $djspar = json_decode($djspar,true);
      if(isset($djspar['specid']))
        {$specid = $djspar['specid'];}
      if(isset($djspar['wsr']))
        {$wsr = $djspar['wsr'];}
      if(isset($djspar['seloprval']))
        {$seloprval = $djspar['seloprval'];}
      if(isset($djspar['opspec']))
        {$opspec = $djspar['opspec'];}
      if(isset($djspar['kvit']))
        {
        if($djspar['kvit'] > 0)
          {$kvitselect = ", ifnull((select sum(f_sum) from " . DBPref . "akts_details_opers where f_operid=s.f_id), 0) kvitsum ";
           $kvitgroup = " having s.f_sum>kvitsum ";
          }
        } // ID-73 ~
      if(isset($djspar['ctxt']))
        {
        $ctxt = $djspar['ctxt'];
        if(strlen($ctxt)==0)
          {$ctxt="0";}
        }
      if(isset($djspar['specclntid']))
        {$specclntid = $djspar['specclntid'];}

      //error_log("\n\nspecclntid = $specclntid\n\n",0);

      if(isset($djspar['subtype']))
        {$subtype = $djspar['subtype'];}
      if(isset($djspar['selsprval']))
        {$selsprval = $djspar['selsprval'];}
      if(isset($djspar['ppv']))
        {$ppv = $djspar['ppv'];}
      if(isset($djspar['selspecinvval']))
        {$selspecinvval = $djspar['selspecinvval'];}
      if(isset($djspar['selid'])) //sdid 3047
        {$selid = $djspar['selid'];} //sdid 3047
      //echo "$specid<br>";
      if(isset($djspar['disabled']))      //sdid3047
        {$disabled = $djspar['disabled'];}//sdid3047

      //sdid 3596
      //$sitypelist = "";
      if(isset($djspar['sitypelist']))
        {$sitypelist = $djspar['sitypelist'];}
      if(isset($djspar['sinewoper']))
        {$sinewoper = $djspar['sinewoper'];}
      if(isset($djspar['siparmslink']))
        {$siparmslink = $djspar['siparmslink'];}
      //~sdid 3596
      }
    //sdid 3596
    $ssitypelist = "";
    $ssiparmslink = "";
    if(strlen($sitypelist)>0) {$ssitypelist = " and s.f_idoper in ($sitypelist) ";}
    if($siparmslink>0) {$ssiparmslink = " and s.f_isvozm=(select f_isvozm from veda_spec_invoices where f_id=$siparmslink) and s.f_nds=(select f_nds from veda_spec_invoices where f_id=$siparmslink) ";}
    //error_log("\n\nlib______ssitypelist = $ssitypelist\n\n",0);
    //~sdid 3596
    if($disabled==1)          //sdid3047
      {$disableds="disabled";}//sdid3047
    $sfid    = "";
    $sopspec = "";
    if(isset($fid))
      {
      if($fid>0)
        {$sfid = " and s.f_id in (".$fid.") ";}
      elseif($fid==-1)
        {
        if($orgid==2)
          {
          if(($seloprval==7)||($seloprval==16))//выбираем операции для "Перенос покупки валюты"//sdid2685
            {
            if(strlen($specid)>0)
              {
              //sdid2685
              if($selspecinvval>0)//ищем валютный контракт по операции-партнеру
              //sdid 3376
              //  {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=4 and s.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$selspecinvval) and s.f_id<>$selspecinvval";}
                {
                $sopspec = " and s.f_parenttype=2 
                             and s.f_specid=$specid 
                             and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=4 
                             and s.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$selspecinvval) 
                             and s.f_id<>$selspecinvval
                             and CASE 
                                   WHEN (select sp.f_status from veda_specs sp where sp.f_id=s.f_specid)<>6
                                     THEN 
                                       (select sp.f_status from veda_specs sp where sp.f_id=s.f_specid)<>6
                                   ELSE
                                       (select count(*) from veda_corrects c1, veda_corrects_opers co1 where c1.f_id=co1.f_correctid and co1.f_correctoperid=s.f_id and c1.f_status<>3 and c1.f_num<>0)>0
                                 END
                           ";
                }
              //~sdid 3376
              else
                {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=4 ";}
              //~sdid2685
              if($selsprval>0){$sopspec = $sopspec." and s.f_val=$selsprval ";}
              }
            }
          elseif($seloprval==8)//выбираем операции для "Перенос валютного перевода с покупкой валюты"
            {
            if(strlen($specid)>0)
              {
              if($selspecinvval>0)//ищем валютный контракт по операции-партнеру
                {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5 and s.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$selspecinvval) ";}
              else
                {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5 ";}
              if($selsprval>0){$sopspec = $sopspec." and s.f_val=$selsprval ";}
              }
            }
          //sdid2722
          //elseif($seloprval==11)//выбираем операции для "Перенос валютного перевода"
          elseif($seloprval==11 || $seloprval==17)//выбираем операции для "Перенос валютного перевода"
          //~sdid2722
            {
            if(strlen($specid)>0)
              {
              if($selspecinvval>0)//ищем валютный контракт по операции-партнеру
                {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5 and s.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$selspecinvval) ";}
              else
                {$sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5 ";}
              if($selsprval>0){$sopspec = $sopspec." and s.f_val=$selsprval ";}
              }
            }
          elseif($seloprval==9)//выбираем операции для "Перенос поступления средств в рублях"
            {
            if(strlen($specid)>0)
              {
              $sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=1 ";
              if($selsprval>0){$sopspec = $sopspec." and s.f_val=$selsprval ";} //sdid 3047
              }
            }
          elseif($seloprval==18)//выбираем операции типом "Платежное поручение"
            {
            if(strlen($specid)>0)
              {
              $sopspec = " and s.f_parenttype=2 and s.f_specid=$specid and (select f_c1doctype from ".DBPref."typeopers where f_id=s.f_idoper)=3 ";
              }
            }
          else
            {
            if(strlen($specid)>0)
              {$sfid = " and s.f_parenttype=2 and s.f_specid in (".$specid.") ";}
            if($opspec>0)
              {$sopspec = " and s.f_parenttype=2 and s.f_specid in (select f_id from ".DBPref."specs where f_status<>6) ";}
            }
          }
        }
      }
    $ppvs = "";
    if(($ppv==1)&&(strlen($specid)>0))
      {
      //sdid 3376
      //$ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
      //          and (select f_status from veda_specs where f_id=s.f_specid)<>6
      //          and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=4
      //          /*and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>
      //              (select ifnull(sum(ahd.f_clssum),0) from veda_spec_invoices si,veda_typeopers tsi,veda_acchist_docs ahd 
      //               where ahd.f_doctype=3 and ahd.f_docid=si.f_id and tsi.f_id=si.f_idoper and (si.f_parentid=s.f_parentid) and tsi.f_c1doctype=5)*/
      //          and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>0 ";

      $ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
                and CASE WHEN (select f_status from veda_specs where f_id=s.f_specid)<>6 
                           THEN
                             (select f_status from veda_specs where f_id=s.f_specid)<>6
                         ELSE    
                             (s.f_id in (select co.f_correctoperid 
                                           from veda_corrects_opers co, 
                                                veda_corrects c
                                          where co.f_correctid=c.f_id
                                            and c.f_specid=$specid
                                            and c.f_status<>3
                                            and c.f_num<>0 ### fix 20251204
                                        )
                             )
                    END 
                and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=4
                /*and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>
                    (select ifnull(sum(ahd.f_clssum),0) from veda_spec_invoices si,veda_typeopers tsi,veda_acchist_docs ahd 
                     where ahd.f_doctype=3 and ahd.f_docid=si.f_id and tsi.f_id=si.f_idoper and (si.f_parentid=s.f_parentid) and tsi.f_c1doctype=5)*/
                and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>0 ";
      //~sdid 3376
      }
    elseif(($ppv==2)&&(strlen($specid)>0))
      {
      $ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
                and (select f_status from veda_specs where f_id=s.f_specid)<>6
                and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5
                and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>0 ";
      }
    elseif(($ppv==3)&&(strlen($specid)>0))
      {
      $ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
                and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=1 ";
      if($selsprval>0){$ppvs = $ppvs." and s.f_val=$selsprval ";} //sdid 3047
      }
    //sdid2722
    elseif($ppv==5 && $specid > 0)
      {
      $ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
                and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=5
                and (select ifnull(sum(ahd.f_clssum),0) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>0 ";
      }
    //~sdid2722
    elseif($ppv==6 && $specid > 0)
      {
      $ppvs = " and s.f_parenttype=2 and s.f_specid=$specid 
                and (select f_c1doctype from veda_typeopers where f_id=s.f_idoper)=3 
                and (select count(*) from veda_acchist_docs ahd where ahd.f_doctype=3 and ahd.f_docid=s.f_id)>0 ";
      }
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}
    if(strlen($specclntid)>0)
      //sdid 3376
      //{$specclntid = " and s.f_parenttype=2 and s.f_specid in (select sp.f_id from ".DBPref."specs sp,".DBPref."dogs dd where sp.f_dogid=dd.f_id and dd.f_contrid in (".$specclntid.")) ";}
      {
      $specclntid = " and CASE
                            WHEN (select f_status from veda_specs where f_id=s.f_specid)<>6
                              THEN (s.f_parenttype=2 and (select s.f_specid in (select sp.f_id from ".DBPref."specs sp,".DBPref."dogs dd where sp.f_dogid=dd.f_id and dd.f_contrid in ($specclntid))))
                            ELSE
                              (s.f_id in (select co.f_correctoperid 
                                            from veda_corrects_opers co, 
                                                 veda_corrects c
                                           where co.f_correctid=c.f_id
                                             and c.f_specid in (select sp.f_id from ".DBPref."specs sp,".DBPref."dogs dd where sp.f_dogid=dd.f_id and dd.f_contrid in ($specclntid))
                                             and c.f_status<>3
                                             and c.f_num<>0 ### fix 20251204
                                         )
                              )
                         END  
                    ";
      //error_log("\n\ntxt_specclntid = $specclntid\n\n",0);

      }
      //~sdid 3376
    //echo $subtype."<br>";
    if(strlen($subtype)>0)
      {$subtype = " and s.f_sub_type_oper=0 ";}
    //echo $subtype."<br>";

    $dbh = dbconnect();
    //получаем список для выборки
    $smstyle = "";
    $response = "";
    if(strlen($mstyle)>0)
    {$smstyle="style='".$mstyle."'";}
    if($wsr==0)
      {$response = "<select class='selval' ".$smstyle." id='getSelSpecsInvVal".$did."' $disableds>";}//sdid3047
    if($wnz==0)
    {$response = $response."<option value='0'>-</option>";}
    //sdid 3596
    if($sinewoper==1)
      {$response = $response."<option value='-1'>Новая операция</option>";}
    //~sdid 3596
    /*$sql = "select s.f_id, ".
         "  case when s.f_parentid=4 then ".
         "    (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=s.f_specid) else ".
         "    (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname) from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=s.f_specid) end dogn, ".
         "  substr(s.f_dttmcr,1,10) dt,s.f_num_oper num,".
         "  (select f_name from ".DBPref."typeopers where f_id=s.f_idoper) opname,s.f_sum, ".
         "  (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val) val ".
         "from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$sfid." ".$ord;*/
    //error_log("\n\nlib_lib.php_____sitypelist = $sitypelist\n\n",0);
    //sdid 3596
    $svozm = "";
    $svozmselect = "";
    if($seloprval==19) // Прочая операция - перенос банковских расходов
      {
      $svozmselect = ", case when s.f_isvozm=0 then 'Не указана'  
                             when s.f_isvozm=1 then 'Возм'
                             when s.f_isvozm=2 then 'Невозм'
                        end vozm ";
      }
    //~sdid 3596
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {
        $sql = "select s.f_id, ".
            "  '-' opn ".
            //sdid 3596
            //" from ".DBPref."spec_invoices s where s.f_id=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ord; //sdid 3594
            " from ".DBPref."spec_invoices s where s.f_id=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ssitypelist." ".$ssiparmslink." ".$ord; //sdid 3594
            //~sdid 3596
        }
      else
        {
        //sdid 3596
        if($seloprval==19)
          {
          $sql = "select * from (
                    select s.f_id, 
                      concat(
                        case 
                          when s.f_parenttype=4 then 
                            (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=s.f_specid) 
                          else 
                            (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname) from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl 
                             where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=s.f_specid) 
                        end,'/',
                        case when s.f_isvozm=0 then 'Не указана'  
                             when s.f_isvozm=1 then 'Возм'
                             when s.f_isvozm=2 then 'Невозм'
                        end,'/', 
                        substr(s.f_dttmcr,1,10),'/',s.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=s.f_idoper),'/',ifnull(s.f_sum,0),' ', 
                        (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_id) opn 
                    #sdid 3596
                    $svozmselect 
                    #from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs.$ord.") k  
                    from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs." ".$ssitypelist." ".$ssiparmslink." ".$ord.") k  
                    #~sdid 3596
                  where k.opn like '%".$ctxt."%' limit 100";
          }
        else
          {
        //~sdid 3596
          $sql = "select * from (
                    select s.f_id, 
                      concat(
                        case 
                          when s.f_parenttype=4 then 
                            (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=s.f_specid) 
                          else 
                            (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname) from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl 
                             where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=s.f_specid) 
                        end,'/',substr(s.f_dttmcr,1,10),'/',s.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=s.f_idoper),'/',ifnull(s.f_sum,0),' ', 
                        (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val),'/',s.f_id) opn 
                    #sdid 3596
                    $svozmselect 
                    #from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs.$ord.") k  
                    from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs." ".$ssitypelist." ".$ssiparmslink." ".$ord.") k  
                    #~sdid 3596
                  where k.opn like '%".$ctxt."%' limit 100";
        //sdid 3596
          }
        //~sdid 3596
        }
      }
    else
      {
      $sql = "select s.f_id, 
                case 
                  when s.f_parenttype=4 then 
                    (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=s.f_specid) 
                  else 
                    (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname) from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=s.f_specid) 
                end dogn, 
                case 
                  when s.f_parentid=4 then '' 
                  else 
                    ifnull((select GROUP_CONCAT(distinct(k.f_num) SEPARATOR ';') from ".DBPref."ktk k,".DBPref."routes r,".DBPref."routes_ktk rk,".DBPref."specs ss 
                            where ss.f_id=s.f_specid and r.f_postid=ss.f_postid and r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid),'') 
                end ktk, 
                case 
                  when s.f_parentid=4 then '' 
                else 
                  ifnull((select GROUP_CONCAT(distinct(r.f_konosament) SEPARATOR ';') from ".DBPref."routes r,".DBPref."specs ss 
                          where ss.f_id=s.f_specid and r.f_postid=ss.f_postid and LENGTH(r.f_konosament)>0),'') 
                end konosament, 
                substr(s.f_dttmcr,1,10) dt,s.f_num_oper num,
                (select f_name from ".DBPref."typeopers where f_id=s.f_idoper) opname,ifnull(s.f_sum,0) f_sum, 
                (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val) val
                $svozmselect #sdid 3596 
                ".$kvitselect. // ID-73
            //" from ".DBPref."spec_invoices s where s.f_id>=0".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs." ".$kvitgroup." ".$ord; // ID-73 //sdid 3596
            " from ".DBPref."spec_invoices s where s.f_id>=0".$sopspec." ".$specclntid." ".$subtype." ".$sfid." ".$ppvs." ".$ssitypelist." ".$kvitgroup." ".$ssiparmslink." ".$ord; // ID-73 /sdid 3596
            //error_log("\n\nsql_specsInv = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
      }
    //if($_SESSION['loginid']==2)
    //  {echo $sql."<br>";}
    //error_log("\n\nsql_specsInv = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $nadd=1;
      $cres = "";
      $csel = ""; //sdid 3047
      if(strlen($ctxt)>0)
        {
        if($selid==$row['f_id']) //sdid 3047
          {$csel="selected";} //sdid 3047
        $cres = str_replace("+","&plus;",$row['opn']);
        //$cres = $row['opn'];
        //$pos = strpos(mb_strtoupper($cres), mb_strtoupper($ctxt));
        //if($pos === false){$nadd=0;}
        }
      else
        {
        if($selid==$row['f_id']) //sdid 3047
          {$csel="selected";} //sdid 3047
        //sdid 3596
        $svozm = "";
        //$cres = $row['dogn']."/".$row['dt']."/".$row['num']."/".$row['opname']."/".$row['f_sum']." ".$row['val']."/".$row['f_id'];
        if($seloprval==19) {$svozm = $row['vozm']."/";}
        $cres = $row['dogn']."/".$svozm.$row['dt']."/".$row['num']."/".$row['opname']."/".$row['f_sum']." ".$row['val']."/".$row['f_id'];
        //~sdid 3596
        }
      if($nadd==1)
        {
        //$response = $response."<option value='".$row['f_id']."'>". //sdid 3047
        $response = $response."<option $csel value='".$row['f_id']."'>". //sdid 3047
                   $cres.
                   "</option>";
        }
      }
    if($wsr==0)
      {$response = $response."</select>";}
    return $response;
    }
  catch (PDOException $e)
    {echo "<select class='selval' id='getSelSpecsInvVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";}
  }

//создать <select> для выбора сертификатов
function getSelCertificatesVal($ord,$did,$mstyle,$fid,$wnz,$djspar)
{
    try
    {
        $specid = "";
        $wsr    = 0;
        //echo $djspar."<br>";
        if(strlen($djspar)>0)
        {
            $djspar = json_decode($djspar,true);
            if(isset($djspar['specid']))
            {$specid = $djspar['specid'];}
            if(isset($djspar['wsr']))
            {$wsr = $djspar['wsr'];}
        }
        $sfid    = "";
        if(strlen($specid)>0)
        {
            $sfid = " and c.f_specid in (".$specid.") ";
        }
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        if($wsr==0)
        {$response = "<select class='selval' ".$smstyle." id='getSelCertificatesVal".$did."'>";}
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        $sql = "select c.f_id,".
            "  concat(spr.f_dopprstr,' ',d.f_dogname,'/',s.f_num,'/',o.f_abbr,'/',".
            "  case ".
            "    when s.f_typez=1 then ct.f_name ".
            "    else cl.f_cname ".
            "  end,'/',".
            "  case ".
            "    when c.f_tovarid>0 and c.f_specid>0 then ".
            "      (select f_name from ".DBPref."spec_details where f_id=c.f_tovarid and f_specid=c.f_specid) ".
            "    when c.f_specid>0 then ".
            "      (select f_tovar from ".DBPref."specs where f_id=c.f_specid) ".
            "    else '' ".
            "  end,'/',(select f_name from ".DBPref."spr where f_type=22 and f_num=c.f_dockind),'/',".
            "  c.f_docnum,'/',DATE_FORMAT(c.f_docdt,'%d.%m.%Y'),' (ИД ',c.f_id,')') selval ".
            "from ".DBPref."certificates c,".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr,".
            "  ".DBPref."contacts ct ".
            "where c.f_specid=s.f_id and spr.f_type=33 and spr.f_num=s.f_typez and ct.f_id=s.f_contactid and o.f_id=d.f_orgid and ".
            "  cl.f_id=d.f_contrid and s.f_dogid=d.f_id ".$sfid." ".$ord;
        //echo $sql."<br>";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {$response = $response."<option value='".$row['f_id']."'>".$row['selval']."</option>";}
        if($wsr==0)
        {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelCertificatesVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора товаров по спецификации
function getSelSpecsDetVal($ord,$did,$mstyle,$fid,$wnz,$orgid,$djspar)
  {
  try
    {
    $response    = "";
    $specid      = "";
    $ctxt        = "";
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['specid']))
        {$specid = $djspar['specid'];}
      if(isset($djspar['ctxt']))
        {$ctxt=$djspar['ctxt'];}
      }
    $sfid    = "";
    if(isset($fid))
      {
      if($fid>0)
        {$sfid = " and s.f_id in (".$fid.") ";}
      }
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}

    $dbh = dbconnect();
    //получаем список для выборки
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    if($wsr==0)
      {$response = "<select class='selval' ".$smstyle." id='getSelSpecsDetVal".$did."'>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    $sql = "
           select 
             s.f_id,concat(d.f_dogname,'/',ss.f_num,'/',o.f_abbr,'/',cl.f_cname,'/',s.f_name,', № ',s.f_num,' (ИД ',s.f_id,')') fname 
           from veda_spec_details s,veda_specs ss,veda_dogs d,veda_clients o,veda_clients cl 
           where cl.f_id=d.f_contrid and o.f_id=d.f_orgid and d.f_id=ss.f_dogid and ss.f_id=s.f_specid
             $sfid $ord
           ";
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {
        $sql = "select s.f_id, 
                  '-' fname 
                from ".DBPref."spec_details s where s.f_id=0 ";
        }
      else
        {
        $sql = "select * from ($sql) k where fname like '%".$ctxt."%'";
        }
      }
    //echo $sql."<br>";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {$response = $response."<option value='".$row['f_id']."'>".$row['fname']."</option>";}
    if($wsr==0)
      {$response = $response."</select>";}
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelSpecsInvVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора операций расширенный
function getSelSpecsInvValExt($ord,$did,$mstyle,$fid,$wnz,$orgid,$djspar)
  {
  try
    {
    $response    = "";
    $specid      = "";
    $wsr         = 0;
    $opspec      = 0;
    $waddfltr    = 0;
    $waddfltrstr = "";
//sdid 688
    $winvschet   = 0;
//~sdid 688
    //ID-73
    //$kvitselect  = " ";
    $kvitseleq   = "";
    //$kvitgroup   = " ";
    //$isKvit      = false;
    //~ID-73
    //echo $kvitseleq."<br>";
    $ctxt        = "";
    //sdid 2725
    $idoper      = "";
    $selid       = 0;
    $aktdetid    = 0;
    $aktdetids   = "";
    //~sdid 2725
    if(strlen($djspar)>0)
      {
      //echo "$djspar<br>";
      $djspar = json_decode($djspar,true);
      if(isset($djspar['specid']))
        {$specid = $djspar['specid'];}
      if(isset($djspar['wsr']))
        {$wsr = $djspar['wsr'];}
      if(isset($djspar['opspec']))
        {$opspec = $djspar['opspec'];}
      if(isset($djspar['waddfltr']))
        {$waddfltr=$djspar['waddfltr'];}
      if(isset($djspar['waddfltrstr']))
        {$waddfltrstr=$djspar['waddfltrstr'];}
      //sdid 2725
      if(isset($djspar['idoper']))
        {$idoper = $djspar['idoper'];}
      if(isset($djspar['selid']))
        {$selid = $djspar['selid'];}
      //~sdid 2725
      //ID-73
      if(isset($djspar['kvit']))
        {
        if($djspar['kvit'] > 0)
          {
          //$kvitselect = ", ifnull((select sum(f_sum) from " . DBPref . "akts_details_opers where f_operid=s.f_id), 0) kvitsum ";
          $kvitseleq  = " and (
                               (
                                s.f_sum>ifnull(
                                       (select sum(f_sum) from " . DBPref . "akts_details_opers where f_operid=s.f_id and f_sum>0), 
                                       (s.f_sum-1)
                                      )
                               ) 
                          and ifnull((select count(*) from " . DBPref . "akts_details_opers where f_operid=s.f_id and f_sum=0),0)=0) ";
          //$kvitgroup  = " having s.f_sum>kvitsum ";
          //$isKvit = true;
          //echo $kvitseleq."|<br>|";
          }
        }
      //~ID-73
      if(isset($djspar['ctxt']))
        {$ctxt=$djspar['ctxt'];}
//sdid 688
      if(isset($djspar['winvschet']))
        {$winvschet=$djspar['winvschet'];}
//~sdid 688
      if(isset($djspar['aktdetid']))
        {
        $aktdetid=$djspar['aktdetid'];
        if($aktdetid>0)
          {$aktdetids=" and s.f_id in (select lsi.f_id FROM ".DBPref."spec_invoices lsi,
                                                        ".DBPref."detailobj ldo, 
                                                        ".DBPref."akts_details lad
                                       where lsi.f_parenttype=2 and lsi.f_specid=ldo.f_detailid and ldo.f_type=3 and ldo.f_objtype=83 
                                         and ldo.f_detailtype=15 and ldo.f_objid=lad.f_aktid and lad.f_id=$aktdetid)";}
        }
      }
    $sfid    = "";
    $sopspec = "";
    if(isset($fid))
      {
      if($fid>0)
        {$sfid = " and s.f_id in (".$fid.") ";}
      elseif($fid==-1)
        {
        if($orgid==2)
          {
          if(strlen($specid)>0)
            {
            $sfid = " and s.f_parenttype=2 and s.f_specid in (".$specid.") ";
            }
          if($opspec>0)
            {$sopspec = " and s.f_parenttype=2 and s.f_specid in (select f_id from ".DBPref."specs where f_status<>6) ";}
          }
        }
      }
        //sdid 2725
        $sidoper = "";
        if(strlen($idoper)>0){$sidoper = " and s.f_idoper in (".$idoper.") ";}
        //~sdid 2725
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}

        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        if($wsr==0)
        {$response = "<select class='selval' ".$smstyle." id='getSelSpecsInvVal".$did."'>";}
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
//sdid 688
        $invschet = "";
        $invschet_from = "";
        $invschet_where = "";

        if($winvschet==1)
          {
             $invschet ="case
			   when s.f_parentid=4 then # если операция по договору, то игнорируем
				   ''
				 else
				   case when t.f_subtype=0 then # если выбрали доходную операцию, то выбираем по ней счет поставщикам
					   ifnull((select GROUP_CONCAT(
						 case 
						   when ss.f_maininv=0 then # если счет не входит в состав агргированного
						     concat(ss.f_num,'(',(select f_name from veda_spr where f_type=12 and f_num=ss.f_status),')') 
						   else # если счет входит в состав агрегированного
						     (select concat(ssm.f_num,'(',(select f_name from veda_spr where f_type=12 and f_num=ssm.f_status),')') from veda_schets ssm where ssm.f_id=ss.f_maininv)
						 end
						 SEPARATOR ';') from veda_schets ss where ss.f_operid=s.f_id and ss.f_type=1),'')
					 else # если выбрали расходную операцию, то выбираем по ней доходную операцию (f_parentid), а из нее счет поставщикам
                                           case when s.f_parentid > 0 then 
					      ifnull((select GROUP_CONCAT(
					  	   case 
						     when ss.f_maininv=0 then # если счет не входит в состав агргированного 
						       concat(ss.f_num,'(',(select f_name from veda_spr where f_type=12 and f_num=ss.f_status),')') 
						     else # если счет входит в состав агрегированного
						       (select concat(ssm.f_num,'(',(select f_name from veda_spr where f_type=12 and f_num=ssm.f_status),')') from veda_schets ssm where ssm.f_id=ss.f_maininv)
						   end
						   SEPARATOR ';') from veda_schets ss where ss.f_operid=s.f_parentid),'')
                                           else 
                                             '' 
                                           end     
					 end
			 end,'/',";
        $invschet_from = " ,veda_typeopers t ";
        $invschet_where = " and t.f_id=s.f_idoper ";
          } 
//~sdid 688

        $sql = "select s.f_id,".
            "  concat(case ".
            "    when s.f_parentid=4 then ".
            "      (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl where cl.f_id=d.f_contrid and d.f_id=s.f_specid) ".
            "    else ".
            "      (select concat(d.f_dogname,'/',ss.f_num,'/',cl.f_cname) from ".DBPref."specs ss,".DBPref."dogs d,".DBPref."clients cl ".
            "       where cl.f_id=d.f_contrid and ss.f_dogid=d.f_id and ss.f_id=s.f_specid) ".
            "  end,'/',substr(s.f_dttmcr,1,10),'/',s.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=s.f_idoper),'/', ".
            "  case ".
            "    when s.f_parentid=4 then ".
            "      '' ".
            "    else ".
            "      ifnull((select GROUP_CONCAT(distinct(k.f_num) SEPARATOR ';') ".
            "              from ".DBPref."ktk k,".DBPref."routes r,".DBPref."routes_ktk rk,".DBPref."specs ss ".
            "              where ss.f_id=s.f_specid and r.f_postid=ss.f_postid and r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid),'') ".
            "  end,'/', ".
            "  case ".
            "    when s.f_parentid=4 then ".
            "      '' ".
            "    else ".
            "      ifnull((select GROUP_CONCAT(distinct(r.f_tn) SEPARATOR ';') from ".DBPref."routes r,".DBPref."specs ss ".
            "       where ss.f_id=s.f_specid and r.f_postid=ss.f_postid and LENGTH(r.f_tn)>1),'') ".
//sdid 688
//            "  end,'/', ".
            "  end,'/', ".$invschet.
//~sdid 688
            "  s.f_sum,' ', ".
            "  (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val),
               #sdid 1415
               '/',case 
                 when s.f_parentid=4 then '' 
                 else ifnull((select GROUP_CONCAT(distinct(r.f_carnum) SEPARATOR ';') from ".DBPref."routes r,".DBPref."specs ss,".DBPref."spec_invoices si 
                       where r.f_postid=ss.f_postid and ss.f_id=si.f_specid and si.f_id=s.f_id and LENGTH(r.f_carnum)>1),'') 
               end,
               '/',case 
                 when s.f_parentid=4 then '' 
                 else ifnull((select GROUP_CONCAT(distinct(r.f_p2adr) SEPARATOR ';') from ".DBPref."routes r,".DBPref."specs ss,".DBPref."spec_invoices si 
                       where r.f_postid=ss.f_postid and ss.f_id=si.f_specid and si.f_id=s.f_id and LENGTH(r.f_p2adr)>1),'') 
               end,
               #~sdid 1415
               '/',s.f_id) fname ".
//sdid 688
//            "from ".DBPref."spec_invoices s where s.f_id>=0 ".$sopspec." ".$sfid." ".$kvitseleq." ".$ord;
            //sdid 2725
            //"from ".DBPref."spec_invoices s ".$invschet_from." where s.f_id>=0 ".$invschet_where.$sopspec." ".$sfid." ".$kvitseleq." ".$ord;
            "from ".DBPref."spec_invoices s ".$invschet_from." where s.f_id>=0 $aktdetids ".$invschet_where.$sopspec." ".$sfid." ".$sidoper." ".$kvitseleq." ".$ord;
            //~sdid 2725 $sidoper
//~sdid 688
        if($waddfltr==1)
          {
          $sql = "select * from (".
              $sql.
              ") k where fname like '%".$waddfltrstr."%'";
          if(strlen($ctxt)>0)
            {
            if(strcmp($ctxt,"0")==0)
              {
              $sql = "select s.f_id, ".
                  "  '-' fname ".
                  " from ".DBPref."spec_invoices s where s.f_id=0 ";
              }
            else
              {
              $sql = $sql." and f_name like '%".$ctxt."%'";
              }
            }
          }
        elseif(strlen($ctxt)>0)
          {
          if(strcmp($ctxt,"0")==0)
            {
            $sql = "select s.f_id, ".
                "  '-' fname ".
                " from ".DBPref."spec_invoices s where s.f_id=0 ";
            }
          else
            {
            $sql = "select * from (".
                $sql.
                ") k where fname like '%".$ctxt."%'";
            }
          }
        //echo $sql."<br>";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          //sdid 2725
          $csel = "";
          if($selid==$row['f_id'])
            {$csel="selected";}
          //$response = $response."<option value='".$row['f_id']."'>".
          $response = $response."<option ".$csel." value='".$row['f_id']."'>".
          //~sdid 2725
              //$row['dogn']."/".$row['dt']."/".$row['num']."/".$row['opname']."/".$row['ktk']."/".$row['konosament']."/".
              //$row['f_sum']." ".$row['val']."/".$row['f_id'].
              $row['fname'].
              "</option>";
          }
        if($wsr==0)
          {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSpecsInvVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора актов
//function getSelAktsVal($orgid,$contrid,$ord,$did,$mstyle,$fid,$wnz) sdid 1887
function getSelAktsVal($orgid,$contrid,$ord,$did,$mstyle,$fid,$wnz=0,$ctxt="") // sdid 1887
  {
  try
    {
    //if($_SESSION['loginid']==2)
    //  {echo "1<br>";}
    $sfid = "";
    if(isset($fid)){if($fid>0){$sfid = " and s.f_id=".$fid." ";}}
    $sorgid = "";
    if($orgid>0){$sorgid = " and s.f_orgid=".$orgid." ";}
    $scontrid = "";
    if($contrid>0){$scontrid = " and s.f_contrid=".$contrid." ";}
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $response = "<select class='selval' ".$smstyle." id='getSelAktsVal".$did."'>";
    //if($wnz==0)
    //  {$response = $response."<option value='0'>-</option>";}
    if(strlen($ctxt) > 0) // sdid 1887
      {
      $sql = "SELECT g.* FROM (SELECT ga.*, CONCAT(ga.f_sum, '/', ga.val, '/', ga.f_num, '/', ga.f_dt, '/', ga.contrid) cnct FROM (" .
             "  select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, ".
            "  (SELECT f_name FROM ".DBPref."spr where f_type=4 and f_num=s.f_val) val, ".
            "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid ".
            "from ".DBPref."akts s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." ".$ord .
             " ) ga) g WHERE g.cnct LIKE '%".$ctxt."%' LIMIT 100";
      }
    else
      {
      $sql = "select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, " .
             "  (SELECT f_name FROM " . DBPref . "spr where f_type=4 and f_num=s.f_val) val, " .
             "  (select f_cname FROM " . DBPref . "clients where f_id=s.f_contrid) contrid " .
             "from " . DBPref . "akts s where s.f_id>=0 " . $sfid . " " . $sorgid . " " . $scontrid . " " . $ord . " LIMIT 100";
      } // ~ sdid 1887
    //if($_SESSION['loginid']==2)
    //  {echo $sql."<br>";}
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {$response = $response."<option value='".$row['f_id']."'>".$row['f_sum']."/".$row['val']."/".$row['f_num']."/".$row['f_dt']."/".$row['contrid']."</option>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelAktsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//sdid 2725
function getSelAktsValExt($orgid,$contrid,$ord,$did,$mstyle,$fid,$wnz=0,$ctxt="",$djspar="") 
  {
  try
    {
    $sfid = "";
    if(isset($fid)){if($fid>0){$sfid = " and s.f_id=".$fid." ";}}
    $sorgid = "";
    if($orgid>0){$sorgid = " and s.f_orgid=".$orgid." ";}
    $scontrid = "";
    if($contrid>0){$scontrid = " and s.f_contrid=".$contrid." ";}
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}
    $curclass = "";
    $ftypeAkt = 0;
    $specid   = 0;
    $specinvoiceid = 0; //sdid 3596
    $noselz = 0;        //sdid 3596
    $seloprval = 0;     //sdid 3596
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['ftypeAkt']))
        {$ftypeAkt = $djspar['ftypeAkt'];}
      if(isset($djspar['specid']))
        {$specid = $djspar['specid'];}
      if(isset($djspar['curclass']))
        {$curclass = $djspar['curclass'];}
      //sdid 3596
      if(isset($djspar['specinvoiceid']))
        {$specinvoiceid = $djspar['specinvoiceid'];}
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['seloprval']))
        {$seloprval = $djspar['seloprval'];}
      //~sdid 3596
      }
    $sftypeAkt = "";
    if($ftypeAkt>0){$sftypeAkt = " and s.f_type=".$ftypeAkt." ";}
    $sspecid = "";
    if($specid>0){$sspecid = " and ifnull((select count(*) from ".DBPref."akts_details sad,".DBPref."akts_details_tovars sadt,".DBPref."spec_details ssd where sad.f_aktid=s.f_id and sad.f_id=sadt.f_akts_detailsid and sadt.f_specdetailsid=ssd.f_id and ssd.f_specid=$specid),0)>0 ";}
    $sclas = "selval";
    if(strlen($curclass)>0){$sclas = $curclass;}
    //sdid 3596
    $sspecinvoiceid = "";
    if($specinvoiceid>0) 
      {
      $sspecinvoiceid = " and s.f_id in (select DISTINCT a.f_id 
                                          from veda_akts a, veda_akts_details ad, veda_akts_details_opers ado
                                         where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=$specinvoiceid ) ";
      }
    //specinvoiceid
    //~sdid 3596
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $response = "<select class='$sclas' ".$smstyle." id='getSelAktsVal".$did."'>";//sdid2725
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //sdid 3596
    $cntrows = 0;
    if($seloprval==19)
      {
      //if($specinvoiceid>0)
      if(strlen($ctxt) > 0) // sdid 1887
        {
        $sql = " SELECT g.* FROM 
                             (SELECT ga.*, CONCAT(ga.f_num, '/Разнесено=', round(ga.f_sum,2), ' ', ga.val, '/Акт=', round(ga.aktsum,2), ' ', ga.val,'/', ga.f_dt, '/', ga.f_id) cnct 
                                FROM (
                                     select s.f_id,
                                            s.f_num,
                                            DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,
                                            s.f_sum aktsum,
                                            s.f_val, 
                                            (SELECT f_uslstr FROM veda_spr where f_type=4 and f_num=s.f_val) val, 
                                            ad.f_sum
                                      from veda_akts s, veda_akts_details ad, veda_akts_details_opers ado 
                                     where s.f_id>=0 and s.f_nds>0 $sftypeAkt and s.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid>0 and ado.f_operid=$specinvoiceid $ord 
                                      ) ga
                             ) g 
                  WHERE g.cnct LIKE '%".$ctxt."%' LIMIT 100
               ";

        $sqlcnt = " SELECT count(*) cnt FROM 
                             (SELECT ga.*, CONCAT(ga.f_num, '/Разнесено=', round(ga.f_sum,2), ' ', ga.val, '/Акт=', round(ga.aktsum,2), ' ', ga.val,'/', ga.f_dt, '/', ga.f_id) cnct 
                                FROM (
                                     select s.f_id,
                                            s.f_num,
                                            DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,
                                            s.f_sum aktsum,
                                            s.f_val, 
                                            (SELECT f_uslstr FROM veda_spr where f_type=4 and f_num=s.f_val) val, 
                                            ad.f_sum
                                      from veda_akts s, veda_akts_details ad, veda_akts_details_opers ado 
                                     where s.f_id>=0 and s.f_nds>0 $sftypeAkt and s.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid>0 and ado.f_operid=$specinvoiceid 
                                      ) ga
                             ) g 
                  WHERE g.cnct LIKE '%".$ctxt."%' LIMIT 100
                 ";
        $rescnt = $dbh->query($sqlcnt);
        if($rowcnt = $rescnt->fetch(PDO::FETCH_ASSOC)) {$cntrows = $rowcnt['cnt'];}


        /*$sql = "SELECT g.* FROM (SELECT ga.*, CONCAT(ga.f_sum, '/', ga.val, '/', ga.f_num, '/', ga.f_dt, '/', ga.contrid) cnct FROM (" .
               "  select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, ".
              "  (SELECT f_uslstr FROM ".DBPref."spr where f_type=4 and f_num=s.f_val) val, ".
              "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid ".
              //"from ".DBPref."akts s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." $sftypeAkt $sspecid ".$ord .//sdid2725 //sdid 3596
              "from ".DBPref."akts s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." $sftypeAkt $sspecid $sspecinvoiceid ".$ord .//sdid2725 //sdid 3596
               " ) ga) g WHERE g.cnct LIKE '%".$ctxt."%' LIMIT 100";*/
        }
      else
        {
        $sql = " 
                 SELECT ga.*, CONCAT(ga.f_num, '/Разнесено=', round(ga.f_sum,2), ' ', ga.val, '/Акт=', round(ga.aktsum,2), ' ', ga.val,'/', ga.f_dt, '/', ga.f_id) cnct 
                   FROM (
                        select s.f_id,
                               s.f_num,
                               DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,
                               s.f_sum aktsum,
                               s.f_val, 
                               (SELECT f_uslstr FROM veda_spr where f_type=4 and f_num=s.f_val) val, 
                               ad.f_sum
                         from veda_akts s, veda_akts_details ad, veda_akts_details_opers ado 
                        where s.f_id>=0 and s.f_nds>0 $sftypeAkt and s.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid>0 and ado.f_operid=$specinvoiceid $ord LIMIT 100
                         ) ga
               ";

        $sqlcnt = " 
                    select count(s.f_id) cnt
                     from veda_akts s, veda_akts_details ad, veda_akts_details_opers ado 
                    where s.f_id>=0 and s.f_nds>0 $sftypeAkt and s.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid>0 and ado.f_operid=$specinvoiceid
                 ";

        $rescnt = $dbh->query($sqlcnt);
        if($rowcnt = $rescnt->fetch(PDO::FETCH_ASSOC)) {$cntrows = $rowcnt['cnt'];}

        /*$sql = "select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, " .
               "  (SELECT f_name FROM " . DBPref . "spr where f_type=4 and f_num=s.f_val) val, " .
               "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid " .
               //"from " . DBPref . "akts s where s.f_id>=0 " . $sfid . " " . $sorgid . " " . $scontrid . " $sftypeAkt $sspecid ". $ord . " LIMIT 100";//sdid2725 //sdid 3596
               "from " . DBPref . "akts s where s.f_id>=0 " . $sfid . " " . $sorgid . " " . $scontrid . " $sftypeAkt $sspecid $sspecinvoiceid ". $ord . " LIMIT 100";//sdid2725 //sdid 3596
        */
        } // ~ sdid 1887
      }
    else
      {
    //~sdid 3596
      if(strlen($ctxt) > 0) // sdid 1887
        {
        $sql = "SELECT g.* FROM (SELECT ga.*, CONCAT(ga.f_sum, '/', ga.val, '/', ga.f_num, '/', ga.f_dt, '/', ga.contrid) cnct FROM (" .
               "  select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, ".
              "  (SELECT f_name FROM ".DBPref."spr where f_type=4 and f_num=s.f_val) val, ".
              "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid ".
              //"from ".DBPref."akts s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." $sftypeAkt $sspecid ".$ord .//sdid2725 //sdid 3596
              "from ".DBPref."akts s where s.f_id>=0 ".$sfid." ".$sorgid." ".$scontrid." $sftypeAkt $sspecid $sspecinvoiceid ".$ord .//sdid2725 //sdid 3596
               " ) ga) g WHERE g.cnct LIKE '%".$ctxt."%' LIMIT 100";
        }
      else
        {
        $sql = "select s.f_id,s.f_num,DATE_FORMAT(s.f_dt,'%d.%m.%Y') f_dt,s.f_sum,s.f_val, " .
               "  (SELECT f_name FROM " . DBPref . "spr where f_type=4 and f_num=s.f_val) val, " .
               "  (select f_cname FROM ".DBPref."clients where f_id=s.f_contrid) contrid " .
               //"from " . DBPref . "akts s where s.f_id>=0 " . $sfid . " " . $sorgid . " " . $scontrid . " $sftypeAkt $sspecid ". $ord . " LIMIT 100";//sdid2725 //sdid 3596
               "from " . DBPref . "akts s where s.f_id>=0 " . $sfid . " " . $sorgid . " " . $scontrid . " $sftypeAkt $sspecid $sspecinvoiceid ". $ord . " LIMIT 100";//sdid2725 //sdid 3596
        } // ~ sdid 1887
    //sdid 3596
      }
    //~sdid 3596
    error_log("\n\nsqlAkts = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $cursel = "";if(($fid>0)&&($row['f_id']==$fid)){$cursel = " selected ";}//sdid2827
      //sdid 3596
      //$response = $response."<option value='".$row['f_id']."' $cursel >".$row['f_sum']."/".$row['val']."/".$row['f_num']."/".$row['f_dt']."/".$row['contrid']."</option>";
      if($seloprval==19)
        {
        if($cntrows==1) {$cursel = " selected ";}
        $response = $response."<option value='".$row['f_id']."' $cursel >".$row['cnct']."</option>";
        }
      else {$response = $response."<option value='".$row['f_id']."' $cursel >".$row['f_sum']."/".$row['val']."/".$row['f_num']."/".$row['f_dt']."/".$row['contrid']."</option>";}
      //~sdid03596
      }
    //sdid 3596
    //$response = $response."</select>";
    if($noselz==0)
      {$response = $response."</select>";}
    //~sdid 3596
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelAktsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}
//~sdid 2725

//создать <select> для выбора спецификаций
function getSelSpecVal($curNum,$tp,$ord,$did,$wnz,$djspar,$wnoval=0,$curclass="",$mstyle="",$onchange="")//sdid3400
  {
  try
    {
    $csel      = "";
    $selid     = 0;
    $noselz    = 0;
    $orgid     = 0;
    $contrid   = 0;
    $contactid = 0;
    $ospecs    = 0; 
    $ospecs1   = 0; //sdid 3596
    $ctxt      = "";
    $limcnt    = "";
    $notinfid  = "";//sdid3047
    $disableds = "";//sdid3047
    $disabled  = 0; //sdid3047
    $valcontrfromleftkoper = 0;//id операции, откуда переносим средства sdid2685
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['orgid']))
        {$orgid = $djspar['orgid'];}
      if(isset($djspar['contrid']))
        {$contrid = $djspar['contrid'];}
      if(isset($djspar['contactid']))
        {$contactid = $djspar['contactid'];}
      if(isset($djspar['ospecs']))
        {$ospecs = $djspar['ospecs'];}
      if(isset($djspar['ospecs1'])) //sdid 3596
        {$ospecs1 = $djspar['ospecs1'];} //sdid 3596
      if(isset($djspar['selid']))
        {$selid = $djspar['selid'];}
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];if(strlen($ctxt)==0){$ctxt="0";}}
      if(isset($djspar['valcontrfromleftkoper']))                  //sdid2685
        {$valcontrfromleftkoper=$djspar['valcontrfromleftkoper'];} //sdid2685
      if(isset($djspar['limcnt']))
        {if($djspar['limcnt']>0){$limcnt = " limit ".$djspar['limcnt'];}}
      $notinfid  = "";//sdid3047
      if(isset($djspar['notinfid'])){if(strlen($djspar['notinfid'])>0){$notinfid = " and s.f_id not in (".$djspar['notinfid'].") ";}}//sdid3047
      if(isset($djspar['disabled']))      //sdid3047
        {$disabled = $djspar['disabled'];}//sdid3047
      }
    $contrids = "";
    if($contrid>0)
      {$contrids = " and cl.f_id=".$contrid." ";}
    $orgids = "";
    if($orgid>0)
      {$orgids = " and o.f_id=".$orgid." ";}
    $contactids = "";
    if($contactid>0)
      {$contactids = " and cl.f_id in (select f_id from ".DBPref."clients where f_contactid=".$contactid.") ";}
    $sospecs = "";
    //sdid3400
    $sonchange = "";
    if(strlen($onchange)>0)
      {$sonchange=" onchange='".$onchange."'";}
    //~sdid3400
    if($ospecs>0)
      //sdid 3376
      //{$sospecs = " and s.f_status<>6 ";}
      {
      if($valcontrfromleftkoper>0)
        {
        /*$sospecs = " 
                    and CASE WHEN s.f_status=6
                               THEN 
                               CASE WHEN (select count(*)
                                            from veda_corrects c1, veda_corrects_opers co1, veda_spec_invoices si1, veda_specs s1 
                                           where c1.f_status<>3
                                             and co1.f_correctid=c1.f_id
                                             and si1.f_id=co1.f_correctoperid
                                             and si1.f_parenttype=2
				             and s1.f_id=si1.f_specid
                                             #and si1.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$valcontrfromleftkoper)
                                             and s1.f_dogid=(select ss2.f_dogid from veda_spec_invoices ssi2, veda_specs ss2 where ssi2.f_id=$valcontrfromleftkoper and ss2.f_id=ssi2.f_specid and ssi2.f_parenttype=2)
                                             #and (select f_c1doctype from veda_typeopers where f_id=si1.f_idoper)=4
                                         )>0
                                     THEN
                                       s.f_id in (select distinct c1.f_specid
                                                    from veda_corrects c1, veda_corrects_opers co1, veda_spec_invoices si1, veda_specs s1 
                                                   where c1.f_status<>3
                                                     and co1.f_correctid=c1.f_id
                                                     and si1.f_id=co1.f_correctoperid
                                                     and si1.f_parenttype=2
						     and s1.f_id=si1.f_specid
                                                     #and si1.f_dogid=(select f_dogid from veda_spec_invoices where f_id=$valcontrfromleftkoper)
                                                     and s1.f_dogid=(select ss2.f_dogid from veda_spec_invoices ssi2, veda_specs ss2 where ssi2.f_id=$valcontrfromleftkoper and ss2.f_id=ssi2.f_specid and ssi2.f_parenttype=2)
                                                     #and (select f_c1doctype from veda_typeopers where f_id=si1.f_idoper)=4
                                                 )
                                     ELSE s.f_id in (select distinct ss.f_id 
                                                       from veda_specs ss
                                                      where ss.f_status<>6
                                                        and ss.f_dogid=(select ss2.f_dogid from veda_spec_invoices ssi2, veda_specs ss2 where ssi2.f_id=$valcontrfromleftkoper and ss2.f_id=ssi2.f_specid and ssi2.f_parenttype=2)) 
                               END
                             ELSE s.f_id in (select distinct ss.f_id 
                                               from veda_specs ss
                                              where ss.f_status<>6
                                                and ss.f_dogid=(select ss2.f_dogid from veda_spec_invoices ssi2, veda_specs ss2 where ssi2.f_id=$valcontrfromleftkoper and ss2.f_id=ssi2.f_specid and ssi2.f_parenttype=2))
                        END
                   ";*/
        $sospecs = " 
                   and CASE WHEN s.f_status<>6 
                              THEN 
                                s.f_status<>6
                            #WHEN (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3)>0)
                            WHEN (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3 and c3.f_num<>0)>0) ### fix 20251204
                              THEN 
                                #(s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3)>0)
                                (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3 and c3.f_num<>0)>0) ### fix 20251204
                            ELSE s.f_status<>6
                       END 
                   ";


        }
      else {$sospecs = " and s.f_status<>6 ";}

      }
      //~sdid 3376
    //sdid 3596
    if($ospecs1>0)
      {
      $sospecs = " 
                 and CASE WHEN s.f_status<>6 
                            THEN 
                              s.f_status<>6
                          #WHEN (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3)>0)
                          WHEN (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3 and c3.f_num<>0)>0) ### fix 20251204
                            THEN 
                              #(s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3)>0)
                              (s.f_status=6 AND (select count(*) from veda_corrects c3 where c3.f_specid=s.f_id and c3.f_status<>3 and c3.f_num<>0)>0) ### fix 20251204
                          ELSE s.f_status<>6
                     END 
                 ";
      }
    //~sdid 3596

    if($disabled==1)          //sdid3047
      {$disableds="disabled";}//sdid3047
    $swnz = "";
    //if($wnz==1){$wnz="";$swnz=" and s.f_id>0 ";}
    //else{$wnz="0,";$swnz=" and s.f_id>=0 ";}
    //if(strlen($curNum)>0){$curNum = " and s.f_id in (".$wnz."".$curNum.") ";}
    if(strlen($curNum)>0){$curNum = " and s.f_id in (".$curNum.") ";}
    else{$curNum = "";}
    if(strlen($tp)>0){$tp = " and s.f_typez in (".$tp.") ";}
    else{$tp = "";}
    if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
    else{$ord = "";}
    $valcontrfromleftkoperstr = "";                                                                                                                                      //sdid2685
    if($valcontrfromleftkoper>0) 
    //sdid 3376                                                                                                                                    //sdid2685
      //{$valcontrfromleftkoperstr = " and (select count(*) from ".DBPref."spec_invoices where f_id<>$valcontrfromleftkoper and f_parenttype=2 and f_specid=s.f_id and f_dogid=(select f_dogid from ".DBPref."spec_invoices where f_id=$valcontrfromleftkoper))>0 ";}//sdid2685
      {
      $valcontrfromleftkoperstr = " and d.f_id=(select ss2.f_dogid from veda_spec_invoices ssi2, veda_specs ss2 where ssi2.f_id=$valcontrfromleftkoper and ss2.f_id=ssi2.f_specid and ssi2.f_parenttype=2) 
                                    and s.f_id<>(select f_specid from veda_spec_invoices where f_id=$valcontrfromleftkoper) ";
      }
    //~sdid 3376                                                                                                                                    //sdid2685
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $sclas = "selval";
    if(strlen($curclass)>0)
      {$sclas = $curclass;}
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select class='".$sclas."' $sonchange $smstyle id='getSelSpecVal".$did."' $disableds>";}//sdid3047 sdid3400
    $swnoval = "";
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";$swnoval = " and s.f_id>0 ";}
    //if($_SESSION['loginid']==2)
    //  {echo $wnoval."<br>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //$sql = "SELECT s.f_id,(concat((select f_dogname from ".DBPref."dogs where f_id=s.f_dogid),'/',s.f_num)) name FROM ".DBPref."specs s where ".$swnz." ".$curNum." ".$tp." ".$ord;
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {
        $sql = "select s.f_id sid,
                  '-' cres 
                from ".DBPref."specs s where s.f_id=0";
        }
      else
        {
        //$row['typez']." ".$row['dogname']."/".$row['snum']."/".$row['abbr']."/".$row['ctname']." (ИД ".$row['sid'].")"
        $sql = "select * from (
                  select s.f_id sid,
                    concat(spr.f_dopprstr,' ',d.f_dogname,'/',s.f_num,'/',o.f_abbr,'/',
                           case when s.f_typez=1 then ct.f_name else cl.f_cname end,' (ИД ',s.f_id,')') cres
                  from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr,".DBPref."contacts ct 
                  where spr.f_type=33 and spr.f_num=s.f_typez and ct.f_id=s.f_contactid and 
                    o.f_id=d.f_orgid and cl.f_id=d.f_contrid and 
                    s.f_dogid=d.f_id $notinfid $valcontrfromleftkoperstr ".$swnoval." ".$sospecs." ".$orgids." ".$contrids." ".$contactids."".$swnz." ".$curNum." ".$tp." 
                  ) k where k.cres like '%".$ctxt."%'";//sdid2685 sdid3047
        }
      }
    else
      {
      $sql = "select s.f_typez,spr.f_dopprstr typez,d.f_orgid orgid,d.f_contrid contrid,d.f_dogname dogname,d.f_id dogid,s.f_num snum,".
        "  s.f_id sid,o.f_abbr abbr".
        "  ,cl.f_id ctid,case when s.f_typez=1 then ct.f_name else cl.f_cname end ctname ".
        //"  ,ct.f_id ctid,ct.f_name ctname ".
        "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr ".
        "  ,".DBPref."contacts ct ".
        "where spr.f_type=33 and spr.f_num=s.f_typez and ".
        //"  ct.f_id=cl.f_contactid and ".
        "  ct.f_id=s.f_contactid and ".
        "  o.f_id=d.f_orgid and cl.f_id=d.f_contrid and ".
        "  s.f_dogid=d.f_id $notinfid $valcontrfromleftkoperstr ".$swnoval." ".$sospecs." ".$orgids." ".$contrids." ".$contactids."".$swnz." ".$curNum." ".$tp." ".$ord." ".$limcnt;//sdid2685 sdid3047
      }
    //if($_SESSION['loginid']==2)
    //  {echo "$sql<br>";}
    //error_log("\n\nsqlSpecs = $sql\n\n", 3, "/var/www/html/veda/logs/test3376.log");
    //error_log("\n\nsqlSpecs = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      //{$response = $response."<option value='".$row['f_id']."'>".$row['name']."</option>";}
      {
      $csel = "";
      if(strlen($ctxt)>0)
        {
        if($selid==$row['sid'])
          {$csel="selected";}
        $response = $response."<option ".$csel." value='".$row['sid']."'>".$row['cres']."</option>";
        }
      else
        {
        if($selid==$row['sid'])
          {$csel="selected";}
        $response = $response."<option ".$csel." value='".$row['sid']."'>".$row['typez']." ".$row['dogname']."/".$row['snum']."/".$row['abbr']."/".$row['ctname']." (ИД ".$row['sid'].")</option>";
        }
      }
    if($noselz==0)
      {$response = $response."</select>";}
    return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSpecVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора спецификаций
function getSelSpecValExt($curNum,$tp,$ord,$did,$wnz,$djspar)
{
    try
    {
        $noselz    = 0;
        $orgid     = 0;
        $contrid   = 0;
        $contactid = 0;
        $ospecs    = 0;
        $ctxt      = "";
        if(strlen($djspar)>0)
          {
          $djspar = json_decode($djspar,true);
          if(isset($djspar['noselz']))
            {$noselz = $djspar['noselz'];}
          if(isset($djspar['orgid']))
            {$orgid = $djspar['orgid'];}
          if(isset($djspar['contrid']))
            {$contrid = $djspar['contrid'];}
          if(isset($djspar['contactid']))
            {$contactid = $djspar['contactid'];}
          if(isset($djspar['ospecs']))
            {$ospecs = $djspar['ospecs'];}
          if(isset($djspar['ctxt']))
            {$ctxt = $djspar['ctxt'];if(strlen($ctxt)==0){$ctxt="0";}}
          }
        $contrids = "";
        if($contrid>0)
        {$contrids = " and cl.f_id=".$contrid." ";}
        $orgids = "";
        if($orgid>0)
        {$orgids = " and o.f_id=".$orgid." ";}
        $contactids = "";
        if($contactid>0)
        {$contactids = " and cl.f_id in (select f_id from ".DBPref."clients where f_contactid=".$contactid.") ";}
        $sospecs = "";
        if($ospecs>0)
        {$sospecs = " and s.f_status<>6 ";}
        $swnz = "";
        //if($wnz==1){$wnz="";$swnz=" and s.f_id>0 ";}
        //else{$wnz="0,";$swnz=" and s.f_id>=0 ";}
        //if(strlen($curNum)>0){$curNum = " and s.f_id in (".$wnz."".$curNum.") ";}
        if(strlen($curNum)>0){$curNum = " and s.f_id in (".$curNum.") ";}
        else{$curNum = "";}
        if(strlen($tp)>0){$tp = " and s.f_typez in (".$tp.") ";}
        else{$tp = "";}
        if(strlen($ord)>0){$ord = " ORDER BY ".$ord;}
        else{$ord = "";}
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        if($noselz==1)
        {$response = "";}
        else
        {$response = "<select class='selval' id='getSelSpecVal".$did."'>";}
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        //$sql = "SELECT s.f_id,(concat((select f_dogname from ".DBPref."dogs where f_id=s.f_dogid),'/',s.f_num)) name FROM ".DBPref."specs s where ".$swnz." ".$curNum." ".$tp." ".$ord;
        if(strlen($ctxt)>0)
          {
          if(strcmp($ctxt,"0")==0)
            {
            $sql = "select s.f_id sid,
                      '-' cres 
                    from ".DBPref."specs s where s.f_id=0";
            }
          else
            {
            //$row['typez']." ".$row['dogname']."/".$row['snum']."/".$row['abbr']."/".$row['ctname']." (ИД ".$row['sid'].")"
            $sql = "select * from (
                      select s.f_id sid,
                        concat(spr.f_dopprstr,' ',d.f_dogname,'/',s.f_num,'/',o.f_abbr,'/',
                               case when s.f_typez=1 then ct.f_name else cl.f_cname end,' (ИД ',s.f_id,')') cres
                      from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr,".DBPref."contacts ct 
                      where spr.f_type=33 and spr.f_num=s.f_typez and ct.f_id=s.f_contactid and 
                        o.f_id=d.f_orgid and cl.f_id=d.f_contrid and 
                        s.f_dogid=d.f_id ".$sospecs." ".$orgids." ".$contrids." ".$contactids."".$swnz." ".$curNum." ".$tp." 
                      ) k where k.cres like '%".$ctxt."%'";
            }
          }
        else
          {
          $sql = "select s.f_typez,spr.f_dopprstr typez,d.f_orgid orgid,d.f_contrid contrid,d.f_dogname dogname,d.f_id dogid,s.f_num snum,".
            "  s.f_id sid,o.f_abbr abbr".
            "  ,cl.f_id ctid,case when s.f_typez=1 then ct.f_name else cl.f_cname end ctname, ".

            "      ifnull((select GROUP_CONCAT(distinct(k.f_num) SEPARATOR ';') ".
            "              from ".DBPref."ktk k,".DBPref."routes r,".DBPref."routes_ktk rk ".
            "              where r.f_postid=s.f_postid and r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid),'') ktks, ".
            "      ifnull((select GROUP_CONCAT(distinct(r.f_tn) SEPARATOR ';') from ".DBPref."routes r ".
            "       where r.f_postid=s.f_postid and LENGTH(r.f_tn)>1),'') konos ".

            //"  ,ct.f_id ctid,ct.f_name ctname ".
            "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl,".DBPref."clients o,".DBPref."spr spr ".
            "  ,".DBPref."contacts ct ".
            "where spr.f_type=33 and spr.f_num=s.f_typez and ".
            //"  ct.f_id=cl.f_contactid and ".
            "  ct.f_id=s.f_contactid and ".
            "  o.f_id=d.f_orgid and cl.f_id=d.f_contrid and ".
            "  s.f_dogid=d.f_id ".$sospecs." ".$orgids." ".$contrids." ".$contactids."".$swnz." ".$curNum." ".$tp." ".$ord;
          }
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
            //{$response = $response."<option value='".$row['f_id']."'>".$row['name']."</option>";}
          {
          if(strlen($ctxt)>0)
            {
            $response = $response."<option value='".$row['sid']."'>".$row['cres']."</option>";
            }
          else
            {
            $response = $response."<option value='".$row['sid']."'>".$row['typez']." ".$row['dogname']."/".$row['snum']."/".$row['abbr']."/".$row['ctname']." (ИД ".$row['sid'].")/".$row['ktks']."/".$row['konos']."</option>";
            }
          }
        if($noselz==0)
        {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSpecVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора платежных поручений
function getSelBnkAccsVal($curNum,$accid,$ord,$did,$wnz,$ufid,$mstyle,$saccid,$wbn,$ftcl,$djspar,$curclass="")//sdid3400
  {
  try
    {
    $noselz  = 0;
    $orgsccs = 0;
    $isclnt  = 0;
    $isimb   = 0;
    $ismain  = 0;
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['orgsccs']))
        {$orgsccs = $djspar['orgsccs'];}
      if(isset($djspar['isclnt']))
        {$isclnt = $djspar['isclnt'];}
      if(isset($djspar['isimb']))
        {$isimb = $djspar['isimb'];}
      if(isset($djspar['ismain']))
        {$ismain = $djspar['ismain'];}
      }
      if(strlen($curNum)>0)
        {$curNum = " and a.f_acc like %".$curNum."% ";}
      else
        {$curNum = "";}
      if(strlen($accid)>0)
        {$accid = " and a.f_id in (".$accid.") ";}
      else
        {$accid = "";}
      if(strlen($ufid)>0)
        {$ufid = " and a.f_clntid in (".$ufid.") ";}
      else
        {$ufid = "";}
      $sorgsccs = "";
      if($orgsccs>0)
        {$sorgsccs = " and a.f_clntid in (select f_id from ".DBPref."clients where f_isourorg=1) ";}
      $sisclnt = "";
      if($isclnt>0)
        {$sisclnt = " and a.f_clntid not in (select f_id from ".DBPref."clients where f_isourorg=1) ";}
      $sisimb = "";
      if($isimb>0)
        {$sisimb = " and a.f_isintermedbank=1 ";}
      $sismain = "";
      if($ismain>0)
        {$sismain = " and a.f_ismain=1 ";}
      $ftcls = "";
      if($ftcl)
        {
        $ftcls = " and a.f_clntid in (select f_objectid from ".DBPref."categs where f_objecttype=2 and f_ctgtype=1 and f_valstr in (".$ftcl."))";
        }
      $smstyle = "";
      if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
      if($noselz==1)
        {$response = "";}
      else
        {$response = "<select class='selval $curclass' ".$smstyle." id='getSelBnkAccsVal".$did."'>";}//sdid3400
      if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
      //подключаемся к базе
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      //указываем, мы хотим использовать utf8
      $dbh->exec('SET CHARACTER SET utf8');
      //получаем список для выборки
      if($wbn)
        {$sql = "select concat(b.f_bankname,'/',v.f_dopprstr,'/',a.f_acc) f_cname,a.f_id from ".DBPref."bank_accounts a,".DBPref."banks b,".DBPref."spr v where a.f_isuse=1 and v.f_num=a.f_val and v.f_type=4 and b.f_bic=a.f_bic and a.f_id>0 ".$curNum." ".$accid." ".$ufid." ".$ord." ".$ftcls." ".$sorgsccs." ".$sisclnt." ".$sisimb." ".$sismain;}
      else
        {$sql = "SELECT f_id,concat(a.f_bic,'/',a.f_acc) f_cname,a.f_id FROM ".DBPref."bank_accounts a where a.f_isuse=1 and a.f_id>0 ".$curNum." ".$accid." ".$ufid." ".$ord." ".$ftcls." ".$sorgsccs." ".$sisclnt." ".$sisimb;}
      $sql = "select concat(b.f_bankname,'/',v.f_dopprstr,'/',a.f_acc) f_cname,a.f_id from ".DBPref."bank_accounts a,".DBPref."banks b,".DBPref."spr v where a.f_isuse=1 and v.f_num=a.f_val and v.f_type=4 and b.f_bic=a.f_bic and a.f_id>0 ".$curNum." ".$accid." ".$ufid." ".$ord." ".$ftcls." ".$sorgsccs." ".$sisclnt." ".$sisimb." ".$sismain;
      //echo $sql;
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $csel="";
        if($saccid==$row['f_id'])
          {$csel="selected";}
        $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_cname'])."</option>";
        }
        if($noselz==0)
        {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelBnkAccsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора архива ЭДО
function getSelEDOVal($curNum,$contrid,$ord,$did,$wnz)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($contrid)>0)
        {$accid = " and f_contrid in (".$contrid.") ";}
        else
        {$accid = "";}
        $response = "<select class='selval' id='getSelEDOVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,concat(f_id,'/',f_numdocrec,' от ',f_dtdocrec) f_cname FROM ".DBPref."edoarh where f_id>0 ".$curNum." ".$accid." ".$ord;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_cname'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelEDOVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора корреспонденции
function getSelLetVal($curNum,$tp,$ord,$did)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and l.f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($tp)>0)
        {$tp = " and l.f_type in (".$tp.") ";}
        else
        {$curNum = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelLetVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT l.f_id f_id,concat(c.f_cname,'/',s.f_name,'/',l.f_num) f_num FROM ".DBPref."inoutlet l,".DBPref."spr s,".DBPref."clients c where  ".
            " l.f_orgid=c.f_id and s.f_type=53 and s.f_num=l.f_type ".$curNum." ".$tp." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelLetVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора Суда
function getSelSudVal($curNum,$ord,$did)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelSudVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,f_num FROM ".DBPref."sud where f_id>=0 ".$curNum." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelSudVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора KTK
function getSelLnsVal($curNum,$spec,$ord,$did)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($spec)>0)
        {$spec = " and f_specid in (".$spec.") ";}
        else
        {$spec = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelLnsVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,f_num FROM ".DBPref."lns where f_id>=0 ".$curNum." ".$spec." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelLnsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора пользователей личного кабинета клиента
function getSelRemoteClntVal($fid,$ord,$did,$wnz=1)
  {
  try
    {
    if(strlen($fid)>0)
      {$fid = " and t.f_id in (".$fid.") ";}
    else
      {$fid = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelRemoteClntVal".$did."'>";
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT 
              t.f_id,
              concat(t.f_login,' (ИД ',t.f_id,')') clnt
            FROM ".DBPref."remote_clnt t
            where t.f_id>0 $fid $ord";
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['clnt'])."</option>";
      }
    $response = $response."</select>";
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelRemoteClntVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//sdid 2084
//создать <select> для выбора договоров ТО (для спецификаций)
function getSelDogTOVal($curNum,$tp,$clid,$ord,$did,$orgid,$mstyle,$wnz=0,$selid,$djspar,$curclass="",$specid,$dogid=0)
  {
  try
    {
    $dtformat = "%d.%m.%Y";
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    if(strlen($curNum)>0)
      {$curNum = " and d.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($tp)>0)
      {$tp = " and d.f_dogtype in (0,".$tp.") ";}
    else
      {$tp = "";}
    if(strlen($clid)>0)
      {$clid = " and d.f_contrid in (".$clid.") ";}
    else
      {$clid = "";}
    if(strlen($orgid)>0)
      {$orgid = " and d.f_orgid in (".$orgid.") ";}
    else
      {$orgid = "";}
    $noselz = 0;
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      }
    //echo "$noselz<br>$selid<br>$specid<br>|<br>";
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select class='selval' id='getSelDogTOVal".$did."'>";}
    //sdid 2084 tz2 2
    //if($wnz==0)
    //  {$response = $response."<option value='0'>-</option>";}
    //~sdid 2084 tz2 2
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    //sdid 2084 tz2
    /*$sql1 = "
            select '0' f_id, 'Нет ТО' dogname from dual  
            UNION
            select '10' f_id, 'Текущая спецификация' dogname from dual 
           ";
    $sql2 = "";
    if($dogid>0)
      {
      $sql2 = "
              UNION 
              select 
                concat('2',d.f_id) f_id,
                concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
              from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
              where                         
                    d.f_orgid=(select dd.f_orgid from veda_dogs dd where dd.f_id=$dogid)
                and d.f_contrid=(select dd.f_contrid from veda_dogs dd where dd.f_id=$dogid)
                and d.f_dogname like '%ВА-ТП%' 
                and d.f_subtype=3 
                and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;
      }
    elseif(isset($specid))
      {
      $sql2 = "
              UNION 
              select 
                concat('2',d.f_id) f_id,
                concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
              from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
              where                         
                    d.f_orgid=(select dd.f_orgid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                and d.f_contrid=(select dd.f_contrid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                and d.f_dogname like '%ВА-ТП%' 
                and d.f_subtype=3 
                and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;
      }*/
    $dogsubtype = -1;
    if($dogid>0)
      {
      $sql0 = "select f_subtype from veda_dogs where f_id=$dogid";
      $res0 = $dbh->query($sql0);
      if($row0 = $res0->fetch(PDO::FETCH_ASSOC)) {$dogsubtype = $row0['f_subtype'];}  
      }
    $sql  = "";
    $sql1 = "";
    $sql2 = "";
    if(($dogsubtype==3) && ($dogid>0))
      {
      $sql = "
              select '20' f_id, 
              concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
              from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c
              where d.f_id=$dogid                      
              and o.f_id=d.f_orgid and c.f_id=d.f_contrid
            ";
      }
    else
      { //
     //sdid 2084 tz2 2
     /*$sql1 = "
             select '0' f_id, 'Не задано' dogname from dual  
             UNION
             select '10' f_id, 'Нет ТО' dogname from dual 
            ";*/
      if($wnz==1)
        {
        $sql1 = "
               select '10' f_id, 'Нет ТО' dogname from dual 
               ";
        }
      else
        {
        $sql1 = "
               select '0' f_id, 'Не задано' dogname from dual  
               UNION
               select '10' f_id, 'Нет ТО' dogname from dual 
               ";
        }
      //~sdid 2084 tz2 2   
      $sql2 = "";
      if($dogid>0)
        {
        $sql2 = "
               UNION
               select '20' f_id, #'Текущая спецификация' dogname from dual 
               concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c
               where d.f_id=$dogid                      
               and o.f_id=d.f_orgid and c.f_id=d.f_contrid
   
               UNION 
               select 
                 concat('3',d.f_id) f_id,
                 #concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
                 concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
               where                         
                 #    d.f_orgid=(select dd.f_orgid from veda_dogs dd where dd.f_id=$dogid)
                 d.f_contrid=(select dd.f_contrid from veda_dogs dd where dd.f_id=$dogid)
                 #and d.f_dogname like '%ВА-ТП%' 
                 and d.f_subtype=3 
                 and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord." limit 10";
        }
      elseif(isset($specid))
        {
        $sql2 = "
               UNION
               select '20' f_id, #'Текущая спецификация' dogname from dual 
               concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c,".DBPref."specs s  
               where s.f_id=$specid and d.f_id=s.f_dogid                      
               and o.f_id=d.f_orgid and c.f_id=d.f_contrid
   
               UNION 
               select 
                 concat('3',d.f_id) f_id,
                 #concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
                 concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
               where                         
                 #    d.f_orgid=(select dd.f_orgid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                 d.f_contrid=(select dd.f_contrid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                 #and d.f_dogname like '%ВА-ТП%' 
                 and d.f_subtype=3 
                 and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord." limit 10";
        }
      }
    //$sql = $sql1.$sql2;
    $sql = $sql.$sql1.$sql2;
    if($selid==0)
      {
      $sql3 = "";
      if(isset($specid))
        {
        if($specid>0)
          {
          $sql3 = "select f_todog from ".DBPref."specs where f_id=$specid";
          $res = $dbh->query($sql3);
          if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
            $selid=$row['f_todog'];
            }
          }
        }
      }
    //~sdid 2084 tz2
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      //sdid 2084 tz2 2
      if(strcmp($row['f_id'],$selid)==0) {$csel=" selected='$selid' ";}
      //~sdid 2084 tz2 2
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['dogname'])."</option>";
      }
    if($noselz==0)
      {$response = $response."</select>";}
    //echo "$response<br>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDogTOVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }
//~sdid 2084

//sdid 2224
//создать <select> для выбора договоров Сертификации (для спецификаций)
function getSelDogCertVal($curNum,$tp,$clid,$ord,$did,$orgid,$mstyle,$wnz=0,$selid,$djspar,$curclass="",$specid,$dogid=0)
  {
  try
    {
    $dtformat = "%d.%m.%Y";
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    if(strlen($curNum)>0)
      {$curNum = " and d.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($tp)>0)
      {$tp = " and d.f_dogtype in (0,".$tp.") ";}
    else
      {$tp = "";}
    if(strlen($clid)>0)
      {$clid = " and d.f_contrid in (".$clid.") ";}
    else
      {$clid = "";}
    if(strlen($orgid)>0)
      {$orgid = " and d.f_orgid in (".$orgid.") ";}
    else
      {$orgid = "";}
    $noselz = 0;
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      }
    //echo "$noselz<br>$selid<br>$specid<br>|<br>";
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select class='selval' id='getSelDogCertVal".$did."'>";}
    //$response = "<select class='selval' id='getSelDogCertVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
   $dogtype = -1;
   if($dogid>0)
     {
     $sql0 = "select f_dogtype from veda_dogs where f_id=$dogid";
     $res0 = $dbh->query($sql0);
     if($row0 = $res0->fetch(PDO::FETCH_ASSOC)) {$dogtype = $row0['f_dogtype'];}  
     }
   $sql  = "";
   $sql1 = "";
   $sql2 = "";
   //if(($dogtype==1) && ($dogid>0))
   if($wnz==1)
     {
      $sql = "
              #select '10' f_id, 'Текущая спецификация' dogname from dual #sdid 2530
              select '10' f_id, 'Сертификат к этой заявке' dogname from dual #sdid 2530
              #concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
              #from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c
              #where d.f_id=$dogid                      
              #and o.f_id=d.f_orgid and c.f_id=d.f_contrid
            ";
     }
    else
     { 
     //if($wnz==1)
     //  {
       $sql1 = "
               select '0' f_id, 'Нет сертификации' dogname from dual 
               UNION
               #select '10' f_id, 'Текущая спецификация' dogname from dual #sdid 2530
               select '10' f_id, 'Сертификат к этой заявке' dogname from dual #sdid 2530
               ";
     //  }
     //else
     //  {
     //  $sql1 = "
     //          select '0' f_id, 'Не задано' dogname from dual  
     //          UNION
     //          select '10' f_id, 'Нет сертификации' dogname from dual 
     //          ";
     //  }
     }
   $sql2 = "";
   if($dogid>0)
     {
     //sdid 2530
     /*
     $sql2 = "
             #concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
             #from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c
             #where d.f_id=$dogid                      
             #and o.f_id=d.f_orgid and c.f_id=d.f_contrid
             UNION 
             select 
               concat('2',d.f_id) f_id,
               concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
             from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
             where                         
                   d.f_orgid=(select dd.f_orgid from veda_dogs dd where dd.f_id=$dogid)
               and d.f_contrid=(select dd.f_contrid from veda_dogs dd where dd.f_id=$dogid)
               and d.f_dogtype=1 
               and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;
      */
     $sql2 = "
             UNION 
             select concat('2',d.f_id) f_id, 'Сертификат через доп.заявку' dogname 
             from ".DBPref."dogs d where d.f_id=$dogid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;
     //~sdid 2530
     }
   elseif(isset($specid))
     {
     if($specid>0)
       {
       //sdid 2530
       /*
       $sql2 = "
               #concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
               #from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c,".DBPref."specs s  
               #where s.f_id=$specid and d.f_id=s.f_dogid                      
               #and o.f_id=d.f_orgid and c.f_id=d.f_contrid
               UNION 
               select 
                 concat('2',d.f_id) f_id,
                 concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')') dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
               where                         
                     d.f_orgid=(select dd.f_orgid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                 and d.f_contrid=(select dd.f_contrid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$specid)
                 and d.f_dogtype=1 
                 and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;
       */
       $sql2 = "
               UNION 
               select 
                 concat('2',ss.f_dogid) f_id, 'Сертификат через доп.заявку' dogname 
               from ".DBPref."specs ss where ss.f_id=$specid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;

       //~sdid 2530
       }
     }
    // }
    $sql = $sql.$sql1.$sql2;
    //echo $sql;
    if($selid==0)
      {
      $sql3 = "";
      if(isset($specid))
        {
        if($specid>0)
          {
          $sql3 = "select f_certdog from ".DBPref."specs where f_id=$specid";
          $res = $dbh->query($sql3);
          if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
            $selid=$row['f_certdog'];
            }
          }
        }
      }
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      if(strcmp($row['f_id'],$selid)==0) {$csel="selected";}
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['dogname'])."</option>";
      }
    $response = $response."</select>";
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDogCertVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }
//~sdid 2224

//создать <select> для выбора дополнительных соглашений
function getSelDogDSVal($fid,$doptype,$dogtype,$dogid,$ord,$did,$wnz=1)
  {
  try
    {
    if(strlen($fid)>0)
      {$fid = " and t.f_id in (".$fid.") ";}
    else
      {$fid = "";}
    if(strlen($doptype)>0)
      {$doptype = " and t.f_doptype in (".$doptype.") ";}
    else
      {$dogtype = "";}
    if(strlen($dogtype)>0)
      {$dogtype = " and t.f_dogtype in (".$dogtype.") ";}
    else
      {$dogtype = "";}
    if(strlen($dogid)>0)
      {$dogid = " and t.f_dogid in (".$dogid.") ";}
    else
      {$dogid = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelDogDSVal".$did."'>";
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT 
              t.f_id,
              concat((select f_name from veda_spr where f_type=152 and f_num=t.f_doptype),' ',t.f_dopname,' ',
                     (select f_name from veda_spr where f_type=59 and f_num=t.f_dogtype),' ',
                     case 
                       when t.f_dogtype=2 then 
                         (select concat(d.f_dogname,'/',s.f_num) from veda_dogs d,veda_specs s where s.f_dogid=d.f_id and s.f_id=t.f_dogid) 
                       else 
                         (select f_dogname from veda_dogs where f_id=t.f_dogid) 
                     end,' (ИД ',t.f_id,')') dogds
            FROM ".DBPref."dogs_ds t
            where t.f_id>0 $fid $dogtype $doptype $ord";
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['dogds'])."</option>";
      }
    $response = $response."</select>";
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDogDSVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора страховок
function getSelInsuresVal($curNum,$spec,$ord,$did)
  {   //echo 1;
  try
    {
    if(strlen($curNum)>0)
      {$curNum = " and t.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($spec)>0)
      {$spec = " and t.f_objtype=2 and t.f_objid in (".$spec.") ";}
    else
      {$spec = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelInsuresVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT t.f_id,concat(ifnull(t.f_ensnum,''),'/',ifnull(t.f_ensdt,''),' (ИД ',t.f_id,')') name 
            FROM ".DBPref."ensures t
            where t.f_id>0 ".$curNum." ".$spec." ".$ord;
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['name'])."</option>";
      }
    $response = $response."</select>";
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelInsuresVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора ДТ
function getSelDTValR($curNum,$spec,$ord,$did)
{   //echo 1;
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and t.f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($spec)>0)
        {$spec = " and t.f_specid in (".$spec.") ";}
        else
        {$spec = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelDTVal".$did."'>";
        //подключаемся к базе
        $dbh = dbconnect();
        //получаем список для выборки
        $sql = "SELECT t.f_id,concat(t.f_TDnum,' (',d.f_dogname,'/',s.f_num,')') f_TDnum ".
            "FROM ".DBPref."dt t,".DBPref."specs s,".DBPref."dogs d ".
            "where t.f_specid=s.f_id and s.f_dogid=d.f_id ".$curNum." ".$spec." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_TDnum'])."</option>";
        }
        $response = $response."</select>";
        //echo $response;
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelDTVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора ДТ
function getSelDTValExt($curNum,$spec,$ord,$did,$wnz=0)
  {   //echo 1;
  try
    {
    if(strlen($curNum)>0)
      {$curNum = " and t.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($spec)>0)
      {$spec = " and t.f_specid in (".$spec.") ";}
    else
      {$spec = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelDTVal".$did."'>";
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT t.f_id,concat(t.f_TDnum,' (',d.f_dogname,'/',s.f_num,') (ИД ',t.f_id,')') f_TDnum 
            FROM ".DBPref."dt t,".DBPref."specs s,".DBPref."dogs d 
            where t.f_specid=s.f_id and s.f_dogid=d.f_id ".$curNum." ".$spec." ".$ord;
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_TDnum'])."</option>";
      }
    $response = $response."</select>";
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDTVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора банков
function getSelBanksVal($bic,$ouracc,$ord,$did,$jstr,$wnz)
{
    try
    {
        if(strlen($bic)>0)
        {$bic = " and b.f_bic in (".$bic.") ";}
        else
        {$bic = "";}
        //echo $ouracc."<br>";
        if($ouracc>0)
        {$ouracc = " and b.f_bic in (select distinct f_valstr from ".DBPref."categs where f_ctgtype=5 and f_objecttype=3) ";}
        else
        {$ouracc = "";}
        //echo $ouracc."<br>";
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelBanksVal".$did."'>";
        //подключаемся к базе
        $dbh = dbconnect();
        //получаем список для выборки
        $sql = "SELECT b.f_id,b.f_bankname f_bankname ".
            "FROM ".DBPref."banks b ".
            "where b.f_id>0 ".$bic." ".$ouracc." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_bankname'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelBanksVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора пунктов меню
function getSelMenuAllVal($ord,$did,$jstr,$wnz,$forsearch=0)
  {
  try
    {
    $fid = 0;
    if(strlen($jstr)>0)
      {
      $jstr = json_decode($jstr,true);
      if(isset($jstr['fid']))
        {$fid = $jstr['fid'];}
      }
    $sfid = "";
    if($fid>0){$sfid = " and b.f_id=".$fid;}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelMenuAllVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT b.f_id,b.f_name 
            FROM ".DBPref."menu_all b 
            where b.f_id>0 ".$sfid.$ord;
    //echo $sql;
    $res = $dbh->query($sql);
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      if($forsearch==1)
        {$response = $response."<option ".$csel." value='".$row['f_name']."'>".$row['f_name']."</option>";}
      else
        {$response = $response."<option ".$csel." value='".$row['f_id']."'>".$row['f_name']."</option>";}
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelMenuAllVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//sdid 1785
//создать <select> для выбора страховых деклараций
function getSelDeclareVal($ord,$did,$jstr,$wnz)
  {
  try
    {
    if(strlen($jstr)>0)
      {
      $opars = json_decode($jstr,true);
      }
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelDeclareVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "select concat(insd.f_dogname,'/',(select f_abbr from veda_clients where f_id=insd.f_orgid),'/', 
                     case LENGTH(insdc.f_dt) when 1 then LPAD(insdc.f_dt,2,'0') else insdc.f_dt end) f_name,
                   insdc.f_id
              from veda_insuredeclare insdc,veda_dogs insd 
             where insdc.f_id>0 and insd.f_id=insdc.f_dogid ".$ord;
    $res = $dbh->query($sql);
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_name'])."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDeclareVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора массовых платежей
function getSelMasPayVal($ord,$did,$tid,$jstr,$wnz)
  {
  try
    {
    if(strlen($jstr)>0)
      {
      $opars = json_decode($jstr,true);
      }
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $stid = "";
    if($tid>0)
      {$stid = " and mp.f_type=".$tid;}    
    $response = "<select class='selval' id='getSelMasPayVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT concat(s144.f_name,' ',mpcl.f_cname,' ',mp.f_sum,' ',ms4.f_name,' (ИД ',mp.f_id,')') f_name,	mp.f_id f_id
              FROM veda_maspayfe mp, veda_clients mpcl, veda_spr ms4, veda_spr s144 
             WHERE mpcl.f_id=mp.f_clntid and ms4.f_type=4 and ms4.f_num=mp.f_val and s144.f_type=144 and s144.f_num=mp.f_type and mp.f_type=3 ".$stid.$ord;
    $res = $dbh->query($sql);
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_name'])."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelMasPayVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }
//~sdid 1785
  //создать <select> для выбора релизов
function getSelReleasesVal($ord,$did,$jstr,$wnz)
  {
  try
    {
    //sdid 1199
    $withfreektk=0;//релизы, у которых есть не занятые КТК
    if(strlen($jstr)>0)
      {
      $opars = json_decode($jstr,true);
      if(isset($opars['withfreektk']))
        {$withfreektk = $opars['withfreektk'];}
      }
    //~sdid 1199
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    //sdid 1199
    $sqlwithfreektk = "";
    if($withfreektk>0)
      {$sqlwithfreektk = " and b.f_ktkcnt>ifnull((select count(*) from ".DBPref."release_objects where f_releaseid=b.f_id and f_objtype=103),0) ";}
    //~sdid 1199
    $response = "<select class='selval' id='getSelReleasesVal".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    //sdid 1199
    $sql = "SELECT b.f_id,b.f_release_num f_name 
            FROM ".DBPref."releases b 
            where b.f_id>0 ".$sqlwithfreektk.$ord;
    //~sdid 1199
    //echo $sql;
    $res = $dbh->query($sql);
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_name'])."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelReleasesVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора объектолв релизов
function getSelReleaseObjectsVal($releaseid,$objtype,$ord,$did,$mstyle,$jstr,$wnz,$ctxt)
  {
  try
    {
    $sname = "ReleaseObjects";
    $sctxt="";
    if(strlen($ctxt)>0)
      {$sctxt=" and concat(r.f_release_num,', ',m.f_name,': ',k.f_num) like '%".$ctxt."%' ";}
    $sreleaseid = "";
    if($releaseid>0){$sreleaseid = " and b.f_releaseid=".$releaseid;}
    $sobjtype   = "";
    if($objtype>0){$sobjtype = " and b.f_objtype=".$objtype;}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $response = "<select class='selval' ".$smstyle." id='getSel".$sname."Val".$did."'>";
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    $sql = "SELECT b.f_id,concat(r.f_release_num,', ',m.f_name,': ',k.f_num) f_name 
            FROM ".DBPref."release_objects b,".DBPref."ktk k,".DBPref."menu_all m,".DBPref."releases r 
            where r.f_id=b.f_releaseid and b.f_objtype=m.f_id and b.f_objid=k.f_id ".$sreleaseid.$sobjtype.$sctxt.$ord;
    //echo $sql;
    $res = $dbh->query($sql);
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_name'])."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSel".$sname."Val".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора ДТ
function getSelDTVal($curNum,$spec,$ord,$did,$curclass="",$mstyle="")
  {
  try
    {
    if(strlen($curNum)>0)
      {$curNum = " and f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($spec)>0)
      {$spec = " and f_specid in (".$spec.") ";}
    else
      {$spec = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $sclas = "selval";
    if(strlen($curclass)>0)
      {$sclas = $curclass;}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $response = "<select class='".$sclas."' $smstyle id='getSelDTVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,f_TDnum FROM ".DBPref."dt where f_id>=0 ".$curNum." ".$spec." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_TDnum'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelDTVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//!!!перенесен в класс sdid1764
//создать <select> для выбора KTK
function getSelKTKVal($curNum,$st,$ord,$did,$mstyle,$wnz,$ctxt)
{
    try
    {
        $sctxt="";
        if(strlen($ctxt)>0)
          {$sctxt=" and k.f_num like '%".$ctxt."%' ";}
        if(strlen($curNum)>0)
        {$curNum = " and k.f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($st)>0)
        {$st = " and k.f_status in (".$st.") ";}
        else
        {$st = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $response = "<select class='selval' ".$smstyle." id='getSelKTKVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        //получаем список для выборки
        $sql = "SELECT k.f_id,k.f_num FROM ".DBPref."ktk k where k.f_id>=0 ".$curNum." ".$st." ".$sctxt." ".$ord;
        //$sql = "SELECT k.f_id,k.f_num,(select f_name from ".DBPref."spr where f_type=31 and f_num=k.f_typesize) ktp FROM ".DBPref."ktk k where k.f_id>=0 ".$curNum." ".$st." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            //$response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."/".$row['ktp']."</option>";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_num'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelKTKVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора платежных поручений
function getSelPPVal($curNum,$accid,$ord,$did,$wnz)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($accid)>0)
        {$accid = " and f_accid in (".$accid.") ";}
        else
        {$accid = "";}
        $response = "<select class='selval' id='getSelPPVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,concat(f_ppnum,'/',f_ppdt) f_cname FROM ".DBPref."acchist where f_id>0 ".$curNum." ".$accid." ".$ord;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_cname'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelPPVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//sdid2685
//создать <select> для выбора записи выписки
function getSelAccHistVal($curNum,$st,$ord,$did,$wnz,$mstyle="",$djspar="")
  {
  try
    {
    if(strlen($curNum)>0)
      {$curNum = " and ah.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($st)>0)
      {$st = " and ah.f_status in (".$st.") ";}
    else
      {$st = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $operidstr = "";
    $operidstr1 = ""; //sdid 3596
    $operid    = 0;
    $isvalstr  = "";
    $isval     = -1;
    $valstr    = "";
    $val       = 0;
    $ahtypestr = "";
    $ahtype    = 0;
    $incomestr = "";
    $income    = -1;
    $ws2       = 0;
    $disableds = "";
    $disabled  = 0;
    $wsr       = 0;
    $seloprval = 0; //sdid 3596
    //echo "$djspar<br>";
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['operid']))
        {$operid = $djspar['operid'];}
      if(isset($djspar['isval']))
        {$isval = $djspar['isval'];}
      if(isset($djspar['ahtype']))
        {$ahtype = $djspar['ahtype'];}
      if(isset($djspar['income']))
        {$income = $djspar['income'];}
      if(isset($djspar['val']))
        {$val = $djspar['val'];}
      if(isset($djspar['ws2']))
        {$ws2 = $djspar['ws2'];}
      if(isset($djspar['wsr']))
        {$wsr = $djspar['wsr'];}
      if(isset($djspar['disabled']))
        {$disabled = $djspar['disabled'];}
      //sdid 3596
      if(isset($djspar['seloprval']))
        {$seloprval = $djspar['seloprval'];}
      //~sdid 3596
      }
    if($operid>0)
      {
      $operidstr = " and ah.f_id in (select ahd.f_acchistid from ".DBPref."acchist_docs ahd where f_doctype=3 and f_docid=$operid) ";
      $operidstr1 = " and ah.f_id in (select p.f_acchistlink from ".DBPref."pays p where p.f_objtype=35 and p.f_objid=$operid) ";
      }
    if($isval==0)
      {$isvalstr = " and ah.f_val=643 ";}
    elseif($isval>0)
      {$isvalstr = " and ah.f_val<>643 ";}
    if($val>0)
      {$valstr = " and ah.f_val=$val ";}
    if($ahtype>0)
      {
      $ahtypestr = " and ah.f_ahtype=$ahtype ";
      }
    if($income==0)
      {$incomestr = " and ah.f_type=$income ";}
    elseif($income==1)
      {$incomestr = " and ah.f_type=$income ";}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    if($disabled==1)
      {$disableds="disabled";}
    $sclas = "selval";
    if($ws2==2)
      {$sclas = "getSelAccHistVal2".$did;}
    $response = "";
    if($wsr==0)
      {$response.= "<select class='$sclas' $smstyle id='getSelAccHistVal".$did."' $disableds>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //подключаемся к базе
    $dbh = dbconnect();
    //получаем список для выборки
    //sdid 3596
    //$sql = "SELECT ah.f_id,concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',ah.f_sum,'/',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',c.f_cname,'/',ah.f_id) fname FROM ".DBPref."acchist ah,".DBPref."clients c where c.f_id=ah.f_orgid ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ".$ord;
    $cntrows = 0;
    if($seloprval==19)
      {
      $sql = "SELECT ah.f_id,
                     concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',REPLACE(ah.f_grnd,'\"',''),'/',ROUND(ah.f_sum,2),' ',
                            (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',ah.f_id) fname 
                FROM ".DBPref."acchist ah
               where ah.f_id>0 ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ";
      $sql .= " UNION ";

      $sql .= "SELECT ah.f_id,
                     concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',REPLACE(ah.f_grnd,'\"',''),'/',ROUND(ah.f_sum,2),' ',
                            (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',ah.f_id) fname 
                FROM ".DBPref."acchist ah
               where ah.f_id>0 ".$curNum." ".$st." $operidstr1 $incomestr $ahtypestr $valstr $isvalstr ";

      $sql = "select distinct ga.f_id, ga.fname from (".$sql;
      $sql .= ") ga ".$ord;


      $sqlcnt = "SELECT ah.f_id cnt
                  FROM ".DBPref."acchist ah
                 where ah.f_id>0 ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ";
      $sqlcnt .= " UNION ";
      $sqlcnt .= "SELECT ah.f_id cnt
                  FROM ".DBPref."acchist ah
                 where ah.f_id>0 ".$curNum." ".$st." $operidstr1 $incomestr $ahtypestr $valstr $isvalstr ";
      $sqlcnt = "select count( distinct ga.cnt) cnt from (".$sqlcnt;
      $sqlcnt .= ") ga "; 

      /*$sql = "SELECT concat('1',ah.f_id) f_id,
                     concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',REPLACE(ah.f_grnd,'\"',''),'/',ROUND(ah.f_sum,2),' ',
                            (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',ah.f_id) fname 
                FROM ".DBPref."acchist ah
               where ah.f_id>0 ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ";
      $sql .= " UNION ";

      $sql .= "SELECT concat('2',p.f_id) f_id,
                     concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',REPLACE(ah.f_grnd,'\"',''),'/',ROUND(ah.f_sum,2),' ',
                            (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',ah.f_id) fname 
                FROM ".DBPref."acchist ah, veda_pays p 
               where ah.f_id>0 and ah.f_id=p.f_acchistlink and p.f_objtype=35 and p.f_objid=$operid ".$curNum." ".$st." $operidstr1 $incomestr $ahtypestr $valstr $isvalstr ";

      $sql = "select distinct ga.f_id, ga.fname from (".$sql;
      $sql .= ") ga ".$ord;


      $sqlcnt = "SELECT concat('1',ah.f_id) cnt
                  FROM ".DBPref."acchist ah
                 where ah.f_id>0 ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ";
      $sqlcnt .= " UNION ";
      $sqlcnt .= "SELECT concat('2',ah.f_id) cnt
                  FROM ".DBPref."acchist ah, veda_pays p
                 where ah.f_id>0 and ah.f_id=p.f_acchistlink and p.f_objtype=35 and p.f_objid=$operid ".$curNum." ".$st." $operidstr1 $incomestr $ahtypestr $valstr $isvalstr ";
      $sqlcnt = "select count( distinct ga.cnt) cnt from (".$sqlcnt;
      $sqlcnt .= ") ga ";*/

      //error_log("\n\ngetSelAccHistVal_sql = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
      //error_log("\n\ngetSelAccHistVal_sqlcnt = $sqlcnt\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
      $rescnt = $dbh->query($sqlcnt);
      if($rowcnt = $rescnt->fetch(PDO::FETCH_ASSOC)) {$cntrows = $rowcnt['cnt'];}
      }
    else {$sql = "SELECT ah.f_id,concat(ah.f_ppnum,'/',date_format(ah.f_ppdt,'%d.%m.%Y'),'/',ah.f_sum,'/',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=ah.f_val),'/',c.f_cname,'/',ah.f_id) fname FROM ".DBPref."acchist ah,".DBPref."clients c where c.f_id=ah.f_orgid ".$curNum." ".$st." $operidstr $incomestr $ahtypestr $valstr $isvalstr ".$ord;}
    //~sdid 3596
    //echo $sql;
    //error_log("\n\ngetSelAccHistVal_sql = $sql\n\n", 3, "/var/www/html/veda/logs/sdid3596.log"); 
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      if($seloprval==19) {if($cntrows==1) {$csel = "selected";}} //sdid 3596
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['fname'])."</option>";
      }
    if($wsr==0)
      {$response.= "</select>";}
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelAccHistVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }
//~sdid2685

//создать <select> для выбора записи выписки
function getSelDifRateVal($curNum,$st,$ord,$did,$wnz)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($st)>0)
        {$st = " and f_status in (".$st.") ";}
        else
        {$st = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        $response = "<select class='selval' id='getSelDifRateVal".$did."'>";
        if($wnz==0)
        {$response = $response."<option value='0'>-</option>";}
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id,f_id fname FROM ".DBPref."difrate where f_id>=0 ".$curNum." ".$st." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['fname'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelDifRateVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора маршрута
function getSelRoutesVal($curNum,$st,$ord,$did,$wnz)
{
    try
    {
        if(strlen($curNum)>0)
        {$curNum = " and f_id in (".$curNum.") ";}
        else
        {$curNum = "";}
        if(strlen($st)>0)
        {$st = " and f_status in (".$st.") ";}
        else
        {$st = "";}
        if(strlen($ord)>0)
        {$ord = " ORDER BY ".$ord;}
        else
        {$ord = "";}
        if($wnz>0)
        {$wnz = "f_id>0";}
        else
        {$wnz = "f_id>=0";}
        $response = "<select class='selval' id='getSelRoutesVal".$did."'>";
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec('SET CHARACTER SET utf8');
        //получаем список для выборки
        $sql = "SELECT f_id FROM ".DBPref."routes where ".$wnz." ".$curNum." ".$st." ".$ord;
        //echo $sql;
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $csel="";
            $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_id'])."</option>";
        }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelRoutesVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора поставок
function getSelShipmentsVal($curNum,$st,$ord,$did,$wnz,$djspar="",$wnoval=0,$noselz=0,$ctxt="",$mstyle="")//sdid3618
  {
  try
    {
    //echo $djspar."<br>";
    if(strlen($curNum)>0)
      {$curNum = " and f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($st)>0)
      {$st = " and f_status in (".$st.") ";}
    else
      {$st = "";}
    $shid = "";
    //echo $djspar."<br>";
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['postid']))
        {if($djspar['postid']>0){$shid=" and f_id=".$djspar['postid']." ";}}
      }
    //echo $shid."<br>";
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    if($wnz>0)
      {$wnz = "f_id>0";}
    else
      {$wnz = "f_id>=0";}
    //echo $shid."<br>";
    //sdid3618
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    if($noselz==1){$response = "";}
    else{$response = "<select class='selval' $smstyle id='getSelShipmentsVal$did'>";}
    //~sdid3618
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";}
    //подключаемся к базе
    //sdid3618
    //$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    //$dbh->exec('SET CHARACTER SET utf8');
    $dbh = dbconnect();
    //получаем список для выборки
    if(strlen($ctxt)>0)
      {
      $sql = "SELECT ga.* 
              FROM 
                (SELECT r.f_id,r.f_id cnct
                 from ".DBPref."shipments r where $wnz $shid $curNum $st $ord
                ) ga
              WHERE ga.cnct LIKE '%".$ctxt."%' LIMIT 100";
      }
    else
      {
      $sql = "SELECT f_id f_id, f_id cnct FROM ".DBPref."shipments where ".$wnz." ".$shid." ".$curNum." ".$st." ".$ord;
      }
    //echo $shid."<br>";
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      //if((strlen($curNum)==0)&&(strlen($selcurnum)>0))
      //  {if($selcurnum==$row['f_id']){$csel="selected";}}
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['cnct'])."</option>";
      }
    if($noselz==0){$response.="</select>";}
    //~sdid3618
    return $response;
    }
  catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelShipmentsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора договора
//sdid3400
function getSelNomenkVal($curNum,$tp,$clid,$ord,$did,$orgid,$mstyle,$wnz,$selid,$mclasses="",$nbdc=0,$ldid="")
  {
  try
    {
    if(strlen($curNum)>0)
      {$curNum = " and n.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($tp)>0)
      {$tp = " and n.f_grp in (".$tp.") ";}
    else
      {$tp = "";}
    if(strlen($clid)>0)
      {$clid = " and n.f_grp>0 ";}
    else
      {$clid = "";}
    if(strlen($did)>0)
      {$did = " and n.f_grp=0 ";}
    else
      {$did = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $smclasses = "";
    if(strlen($mclasses)>0)
      {$smclasses = $mclasses;}
    $mnbdc = "";
    if($nbdc>0)
      {$mnbdc = " and n.f_nbd1c=$nbdc ";}
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    $response = "<select class='selval $smclasses' ".$smstyle." id='getSelNomenkVal".$ldid."'>";
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //получаем список для выборки
    //$sql = "SELECT f_id,f_name from ".DBPref."nomenk where f_id>=0 ".$curNum." ".$tp." ".$clid." ".$did." ".$orgid." ".$ord;
    $sql = "SELECT n.f_id,concat(n.f_name,' (',(select f_name from ".DBPref."spr where f_type=131 and f_num=n.f_edizm),')') f_name from ".DBPref."nomenk n where n.f_id>=0 $mnbdc ".$curNum." ".$tp." ".$clid." ".$did." ".$orgid." ".$ord;
    //echo $sql;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $sels = "";
      if($selid==$row['f_id'])
        {$sels = "selected";}
      $response = $response."<option ".$sels." value='".$row['f_id']."'>".$row['f_name']."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelNomenkVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }
//~sdid3400

//создать <select> для выбора договора
function getSelDogVal($curNum,$tp,$clid,$ord,$did,$orgid,$mstyle,$wnz,$selid,$djspar,$wnoval=0,$rnoselz=0)//sdid2725
  {
  try
    {
    $ws2 = 0;
    $noselz = 0;
    $ctxt = "";
    if(strlen($djspar)>0)
      {
        $djspar = json_decode($djspar,true);
        if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
        if(isset($djspar['ws2']))
        {$ws2 = $djspar['ws2'];}
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];if(strlen($ctxt)==0){$ctxt="0";}}
      }
    if(($noselz==0)&&($rnoselz>0)){$noselz=$rnoselz;}
    if(strlen($curNum)>0)
      {$curNum = " and d.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($tp)>0)
      {$tp = " and d.f_dogtype in (0,".$tp.") ";}
    else
      {$tp = "";}
    $sclas = "selval";
    if($ws2>0)
      {$sclas = "getSelDogValSel2".$did;}
    //echo $sclas."<br>";
    if(strlen($clid)>0)
      {$clid = " and d.f_contrid in (".$clid.") ";}
    else
      {$clid = "";}
    if(strlen($orgid)>0)
      {$orgid = " and d.f_orgid in (".$orgid.") ";}
    else
      {$orgid = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    //подключаемся к базе
    $dbh = dbconnect();
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select class='".$sclas."' ".$smstyle." id='getSelDogVal".$did."'>";}
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //получаем список для выборки
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {$sql = "SELECT d.f_id,'-' dogname from ".DBPref."dogs d where d.f_id=0";}
      else
        {$sql = "select * from (SELECT d.f_id,concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c where o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord.") k where k.dogname like '%".$ctxt."%'";}
      }
    else
      {
      if($wnoval==1)
        {$sql = "SELECT d.f_id,concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
               where d.f_id>0 and o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;}
      else
        {$sql = "SELECT d.f_id,concat(d.f_dogname,'/',o.f_abbr,'/',c.f_cname) dogname 
               from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
               where o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$ord;}
      }
    //echo $sql."<br>".$selid."<br>";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
            $sels = "";
            if($selid==$row['f_id'])
            {$sels = "selected";}
            $response = $response."<option ".$sels." value='".$row['f_id']."'>".$row['dogname']."</option>";
        }
        if($noselz==0)
        {$response = $response."</select>";}
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelDogVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора договора
//sdid2084
function getSelDogValExt($curNum,$tp,$clid,$ord,$did,$orgid,$mstyle,$wnz,$selid,$djspar,$curclass="",$limit=0,$onchange="")//sdid3400
//~sdid2084
  {
  try
    {
    $dtformat = "%d.%m.%Y";
    $ws2      = 0;
    $ws3      = 0;
    $noselz   = 0;
    $ctxt     = "";
    $subtype  = "";
    $wdis = ""; // sdid 1958
//    echo $djspar;
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['subtype']))
        {$subtype = $djspar['subtype'];}
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['ws2']))
        {$ws2 = $djspar['ws2'];}
      if(isset($djspar['ws3']))
        {$ws3 = $djspar['ws3'];}
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];if(strlen($ctxt)==0){$ctxt="0";}}
      // sdid 1958
      if(isset($djspar['wdis']))
        {$wdis = $djspar['wdis'];}
      // ~ sdid 1958
      }
    if(strlen($subtype)>0)
      {$subtype =" and d.f_subtype in (".$subtype.") ";}
    //echo $subtype;
    if(strlen($curNum)>0)
      {$curNum = " and d.f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($tp)>0)
      {$tp = " and d.f_dogtype in (0,".$tp.") ";}
    else
      {$tp = "";}
    $sclas = "selval";
    if($ws2>0)
      {$sclas = "getSelDogValSel2".$did;}
    elseif(strlen($curclass)>0)
      {$sclas = $curclass;}
    //echo $sclas."<br>";
    if(strlen($clid)>0)
      {$clid = " and d.f_contrid in (".$clid.") ";}
    else
      {$clid = "";}
    if(strlen($orgid)>0)
      {$orgid = " and d.f_orgid in (".$orgid.") ";}
    else
      {$orgid = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    //sdid2084
    $slimit = "";
    if($limit>0)
      {$slimit = " limit ".$limit;}
    //~sdid2084
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    //sdid3400
    $sonchange = "";
    if(strlen($onchange)>0)
      {$sonchange=" onchange='".$onchange."'";}
    //~sdid3400
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    if($noselz==1)
      {$response = "";}
    else
      //{$response = "<select class='".$sclas."' ".$smstyle." id='getSelDogVal".$did."'>";} // sdid 1958
      {$response = "<select $wdis $sonchange class='".$sclas."' ".$smstyle." id='getSelDogVal".$did."'>";} // sdid 1958 sdid3400
    if($wnz==0)
      {$response = $response."<option value='0'>-</option>";}
    //получаем список для выборки
    //sdid2084
    if(strlen($ctxt)>0)
      {
      if(strcmp($ctxt,"0")==0)
        {$sql = "SELECT d.f_id,'-' dogname from ".DBPref."dogs d where d.f_id=0";}
      else
        {$sql = "select * from (SELECT d.f_id,ifnull(concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')'),'- (ИД - 0)') dogname from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c where o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$subtype." ".$ord.") k where k.dogname like '%".$ctxt."%' ".$slimit;}
      }
    else
      {
      if(($ws3>0)&&($selid>0))
        {$sql = "SELECT d.f_id,ifnull(concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')'),'- (ИД 0)') dogname 
                 from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
                 where o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$subtype." ".$ord." and d.f_id=".$selid." ".$slimit;}
      else
        {$sql = "SELECT d.f_id,ifnull(concat(d.f_dogname,' от ',DATE_FORMAT(d.f_dogdate,'".$dtformat."'),'/',o.f_abbr,'/',c.f_cname,' (ИД ',d.f_id,')'),'- (ИД 0)') dogname 
                 from ".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c 
                 where o.f_id=d.f_orgid and c.f_id=d.f_contrid ".$curNum." ".$tp." ".$clid." ".$orgid." ".$subtype." ".$ord." ".$slimit;}
      }
    //~sdid2084
    //if($_SESSION['loginid']==2)
    //  {echo $sql."<br>".$selid."<br>".$ws3."<br>";}
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $sels = "";
      if($selid==$row['f_id'])
        {$sels = "selected";}
      $response = $response."<option ".$sels." value='".$row['f_id']."'>".$row['dogname']."</option>";
      }
    if($noselz==0)
      {$response = $response."</select>";}
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelDogVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора клиента расширенный
function getSelClntValExt($curNum,$isorg,$isclnt,$did,$selcurnum,$wnz,$ws2,$mstyle,$djspar,$wnoval=0)
  {
  //echo "123<br>";
  try
    {
    $noselz = 0;
    $ftyped = 0;
    $fdocid = 0;
    $fttclb = 0;
    $ctxt   = "";
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['ftyped']))
        {$ftyped = $djspar['ftyped'];}
      if(isset($djspar['fdocid']))
        {$fdocid = $djspar['fdocid'];}
      if(isset($djspar['fttclb']))
        {$fttclb = $djspar['fttclb'];}
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];}
      }
    $sftd = "";
    if(($ftyped>0)&&($fttclb>0))
      {
      $tpcl = "f_contrid";
      if($fttclb==2){$tpcl = "f_orgid";}
      if($ftyped==4)
        {$sftd = " and f_id in (select ".$tpcl." from ".DBPref."dogs where f_id=".$fdocid.") ";}
      }
    //echo $sftd."<br>";
    if(strlen($curNum)>0)
      {$curNum = " and f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($isorg)>0)
      {$isorg = " and f_id in (select f_objectid from ".DBPref."categs where f_valstr=1 and f_objecttype=2 and f_ctgtype=1) ";}
    else
      {$isorg = "";}
    if(strlen($isclnt)>0)
      {$isclnt = " and f_id in (select f_objectid from ".DBPref."categs where f_valstr in (".$isclnt.") and f_objecttype=2 and f_ctgtype=1) ";}
    else
      {$isclnt = "";}
    $sclas = "selval";
    if($ws2>0)
      {$sclas = "getSelClntValSel2".$did;}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select class='".$sclas."' ".$smstyle." id='getSelClntVal".$did."'>";}
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";}
    if($wnz==0)
      {$response = $response."<option value='0'>-(ИД 0)</option>";}
    if(strlen($ctxt)>0)
      {$ctxt=" and concat(f_cname,' (ИД ',f_id,')') like '%".$ctxt."%' ";}
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    //$sql = "SELECT f_id,concat(f_cname,'(',f_id,')') f_cname FROM ".DBPref."clients where f_id>=0 ".$curNum." ".$isorg." ".$isclnt." ".$sftd." ".$ctxt." ORDER BY f_id";
    $sql = "SELECT f_id,f_cname FROM ".DBPref."clients where f_id>=0 ".$curNum." ".$isorg." ".$isclnt." ".$sftd." ".$ctxt." ORDER BY f_id";
    //echo $sql."<br>";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      if(strlen($selcurnum)>0)
        {if($selcurnum==$row['f_id']){$csel="selected";}}
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("+","&plus;",str_replace("\"","&quot;",$row['f_cname']))." (ИД ".$row['f_id'].")</option>";
      //$response = $response."<option ".$csel." value='".$row['f_id']."'>".$row['f_cname']."</option>";
      }
      if($noselz==0)
      {$response = $response."</select>";}
      return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelClntVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
    return "";
}

//создать <select> для выбора клиента
//sdid 1552
//function getSelClntVal($curNum,$isorg,$isclnt,$did,$selcurnum,$wnz,$ws2,$mstyle,$djspar,$wnoval=0,$curclass="",$onchange="")
function getSelClntVal($curNum,$isorg,$isclnt,$did,$selcurnum,$wnz,$ws2,$mstyle,$djspar,$wnoval=0,$curclass="",$onchange="",$forsearch=0)
//~sdid 1552
  {
  //echo "123<br>";
  try
    {
    $noselz = 0;
    $ftyped = 0;
    $fdocid = 0;
    $fttclb = 0;
    $ctxt   = "";
    $wdis   = "";
    $wrfinn = 0;
    $zval   = "-";
    $forsearch = 0;
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['ftyped']))
        {$ftyped = $djspar['ftyped'];}
      if(isset($djspar['fdocid']))
        {$fdocid = $djspar['fdocid'];}
      if(isset($djspar['fttclb']))
        {$fttclb = $djspar['fttclb'];}
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];}
      if(isset($djspar['wdis']))
        {$wdis = $djspar['wdis'];}
      //sdid2250
      if(isset($djspar['wrfinn']))
        {$wrfinn = $djspar['wrfinn'];}
      if(isset($djspar['zval']))
        {$zval = $djspar['zval'];}
      //~sdid2250
      }
//sdid 1552
    $sonchange = "";
    if(strlen($onchange)>0)
      {$sonchange=" onchange='".$onchange."'";}
//~sdid 1552
    $sftd = "";
    if(($ftyped>0)&&($fttclb>0))
      {
      $tpcl = "f_contrid";
      if($fttclb==2){$tpcl = "f_orgid";}
      if($ftyped==4)
        {$sftd = " and f_id in (select ".$tpcl." from ".DBPref."dogs where f_id=".$fdocid.") ";}
      }
    //echo $sftd."<br>";
    //sdid2250
    if($wrfinn>0){$wrfinn=" and length(f_inn) in (10,12) ";}
    else{$wrfinn="";}  
    //~sdid2250
    if(strlen($curNum)>0)
      {$curNum = " and f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($isorg)>0)
      {$isorg = " and f_id in (select f_objectid from ".DBPref."categs where f_valstr=1 and f_objecttype=2 and f_ctgtype=1) ";}
    else
      {$isorg = "";}
    if(strlen($isclnt)>0)
      {$isclnt = " and f_id in (select f_objectid from ".DBPref."categs where f_valstr in (".$isclnt.") and f_objecttype=2 and f_ctgtype=1) ";}
    else
      {$isclnt = "";}
    $sclas = "selval";
    if($ws2>0)
      {$sclas = "getSelClntValSel2".$did;}
    elseif(strlen($curclass)>0)
      {$sclas = $curclass;}
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    if($noselz==1)
      {$response = "";}
    else
      //sdid 1552
      //{$response = "<select $wdis class='".$sclas."' ".$smstyle." id='getSelClntVal".$did."'>";}
      {$response = "<select $sonchange $wdis class='".$sclas."' ".$smstyle." id='getSelClntVal".$did."'>";}
      //~sdid 1552
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";}
    elseif($wnz==0)
      {
      if($forsearch==1)
        {$response = $response."<option value>".$zval."</option>";}
      else
        {$response = $response."<option value='0'>".$zval."</option>";}
      }
    if(strlen($ctxt)>0)
      {$ctxt=" and f_cname like '%".$ctxt."%' ";}
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    //sdid2250
    $sql = "SELECT f_id,f_cname FROM ".DBPref."clients where f_id>=0 ".$curNum." ".$isorg." ".$isclnt." ".$sftd." ".$ctxt." ".$wrfinn." ORDER BY f_id";
    //~sdid2250
    //echo $sql."<br>";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      if(strlen($selcurnum)>0)
        {if($selcurnum==$row['f_id']){$csel="selected";}}
      if($forsearch==1)
        {$response = $response."<option ".$sels." value='".str_replace("\"","&quot;",$row['f_cname'])."'>".str_replace("\"","&quot;",$row['f_cname'])."</option>";}
      else
        {$response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_cname'])."</option>";}
      }
    if($noselz==0)
      {$response = $response."</select>";}
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelClntVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  return "";
  }

//создать <select> для выбора контакта
//function getSelContactsVal($curNum,$st,$ord,$did,$wnz,$djspar="") sdid 2448
function getSelContactsVal($curNum,$st,$ord,$did,$wnz,$djspar="",$selcurnum=0) // sdid 2448
  {
  try
    {
    $ctxt   = "";
    if(strlen($djspar)>0)
      {
      $djspar = json_decode($djspar,true);
      if(isset($djspar['ctxt']))
        {$ctxt = $djspar['ctxt'];}
      }
    if(strlen($ctxt)>0)
      {$ctxt=" and f_name like '%".$ctxt."%' ";}
    if(strlen($curNum)>0)
      {$curNum = " and f_id in (".$curNum.") ";}
    else
      {$curNum = "";}
    if(strlen($st)>0)
      {$st = " and f_status in (".$st.") ";}
    else
      {$st = "";}
    if(strlen($ord)>0)
      {$ord = " ORDER BY ".$ord;}
    else
      {$ord = "";}
    $response = "<select class='selval' id='getSelContactsVal".$did."'>";
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    //получаем список для выборки
    $sql = "SELECT f_id,f_name FROM ".DBPref."contacts where f_id>=0 ".$curNum." ".$st." ".$ctxt." ".$ord;
    //echo $sql."<br>";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $csel="";
      // sdid 2448
      //if((strlen($curNum)==0)&&(strlen($selcurnum)>0))
      //  {if($selcurnum==$row['f_id']){$csel="selected";}}
      if ($selcurnum > 0)
      {
          if ($selcurnum == $row['f_id'])
          {
              $csel = ' selected ';
          }
      }
      // ~ sdid 2448
      $response = $response."<option ".$csel." value='".$row['f_id']."'>".str_replace("\"","&quot;",$row['f_name'])."</option>";
      }
    $response = $response."</select>";
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelContactsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//создать <select> для выбора пользователя
function getSelUsrsVal($curNum,$grp,$ord,$did,$mstyle,$wdis,$wnoval=0,$forsearch=0,$curclass="",$idmail=0,$idname="value")//sdid3128
  {
  try
    {
    //echo $curNum."<br>";
    $otdel  = "";
    $objl = null;
    $selid=0;
    if(strlen($curNum)>0)
      {
      //if($_SESSION['loginid']==2)
      //  {echo $curNum."<br>";}
            //echo $curNum."<br>";
            if(strcmp(substr($curNum,0,1),"{")==0)
              {
                $objl   = json_decode($curNum, true);
                //var_dump($objl);
                if(isset($objl['curNum'])){$curNum = $objl['curNum'];}
                //echo $objl['curNum'];
                if(isset($objl['selid'])){$selid   = $objl['selid'];}
                if(isset($objl['otdel'])){if(strlen($otdel)>0){$otdel   = " and f_struct_code in (0,".$objl['otdel'].") ";}}
              $curNum = "";//sdid3128
              }
            else
              {$curNum = " and f_id in (".$curNum.") ";}
        }
        else
        {$curNum = "";}
        //echo $otdel."<br>";
        if($grp>0)
          {$grp = " and f_id in (select f_usrid from ".DBPref."usr_grp where f_grpid=".$grp.") ";}
        else
          {$grp = "";}
        if(strlen($ord)>0)
          {$ord = " ORDER BY ".$ord;}
        else
          {$ord = "";}
        $smstyle = "";
        if(strlen($mstyle)>0)
        {$smstyle="style='".$mstyle."'";}
        $wdisstr = "";
        if(isset($wdis))
          {
          if($wdis==1)
            {$wdisstr = " disabled=\"disabled\" ";}
          }
        $sclas = "selval";
        if(strlen($curclass)>0)
          {$sclas = $curclass;}
        $response = "<select ".$wdisstr." class='".$sclas."' ".$smstyle." id='getSelUsrsVal".$did."'>";
        if($wnoval==1)
          {$response = $response."<option value=''>Введите значение</option>";}
        //подключаемся к базе
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        //указываем, мы хотим использовать utf8
        $dbh->exec("SET CHARACTER SET utf8");
        //получаем список для выборки
        if($wnoval==1)
          {$sql = "SELECT f_id,f_login,concat(f_name1,' ',f_name2) name FROM ".DBPref."users where f_id>0 ".$otdel." ".$curNum." ".$grp." ".$ord;}//sdid3128
        else
          {$sql = "SELECT f_id,f_login,concat(f_name1,' ',f_name2) name FROM ".DBPref."users where f_id>=0 ".$otdel." ".$curNum." ".$grp." ".$ord;}//sdid3128
        //echo $sql;
        $res = $dbh->query($sql);
        //if($_SESSION['loginid']==2)
        //  {echo $sql."<br>".$selid."<br>";}
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $sels = "";
          //if($_SESSION['loginid']==2)
          //  {echo $row['f_id']."<br>";}
          if(is_numeric($selid))
            {
            if($selid>0)
              {
              //if($_SESSION['loginid']==2)
              //  {echo $selid."<br>";}
              if($row['f_id']==$selid)
                {
                $sels = "selected";
                }
              }
            }
          elseif(strlen($selid)>0)
            {
            $selar = explode(",",$selid);
            if(count($selar)>0)
              {
              $lsi=0;
              while($lsi<count($selar))
                {
                if($row['f_id']==$selar[$lsi])
                  {$sels = "selected";}
                $lsi++;
                }
              }
            }
          if($forsearch==1)
            {$response = $response."<option $idname='".$row['name']."' ".$sels.">".$row['name']."</option>";} //sdid3128
          elseif($idmail==1)                                                                                  //sdid3128
            {$response = $response."<option $idname='".$row['f_login']."' ".$sels.">".$row['name']."</option>";}//sdid3128
          else
            {$response = $response."<option $idname='".$row['f_id']."' ".$sels.">".$row['name']."</option>";} //sdid3128
          }
        $response = $response."</select>";
        return $response;
    }
    catch (PDOException $e)
    {
        echo "<select class='selval' id='getSelUsrsVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
}

//создать <select> для выбора справочной информации
function getSelSprVal($isext,$curType,$curNum,$isuse,$ordname,$did,$mstyle,$selid,$ws2,$wdis,$djspar,$forsearch=0,$wnoval=0,$curclass="",$oncnhgfuntxt="")//sdid3342
  {
  //echo 1;
  //if($_SESSION['loginid']==2){echo "$ordname<br>";}
  try
    {
    //echo $djspar."<br>";
    //echo $selid."<br>";
    //echo $ws2."<br>";
    $fcurtype  = $curType;
    $s86easy   = 0;
    $noselz    = 0;
    $namedop   = "";
    $namedop1  = "";
    $namedop2  = "";
    $wnz       = 1;
    $gus       = "";
    $gususl    = "";
    $zval      = "-";
    $uslint    = 0;
    $suslint   = "";
    $seloprval = 0;//вид прочей операции
    $specinvid = 0;//ссылка на ИД операции
    //sdid - 389
    $type_oper_id_filter = "";
    //~sdid - 389
    $dopprstr  = 0;
    $dopprstrs = "";
    if(strlen($djspar)>0)
      {
      //echo $djspar."<br>";
      $djspar = json_decode($djspar,true);
      if(isset($djspar['s86easy']))
        {$s86easy = $djspar['s86easy'];}
      //var_dump($djspar);
      //sdid - 389
      if(isset($djspar['type_operation_id'])){
        $type_oper_id_filter = " AND ((FIND_IN_SET('".$djspar['type_operation_id']."', f_dopprstr) OR FIND_IN_SET('0', f_dopprstr))) ";
        }
      //~sdid - 389
      if(isset($djspar['noselz']))
        {$noselz = $djspar['noselz'];}
      if(isset($djspar['uslint']))
        {$uslint = $djspar['uslint'];}
      if($uslint>0){$suslint = " and f_uslint=".$uslint." ";}
      if(isset($djspar['namedop']))
        {
        if((isset($djspar['namedop1']))&&($curType==40))
          {$namedop = " and f_namedop in (select f_num from ".DBPref."spr where f_type=39 and f_namedop in (".$djspar['namedop'].")) ";}
        else
          {$namedop = " and f_namedop in (".$djspar['namedop'].") ";}
        }
      if(isset($djspar['namedop1']))
        {
        if($djspar['namedop1']==1)//Станция
          {$namedop1 = " and f_num<10000 ";}
        elseif($djspar['namedop1']==2)//Порт
          {$namedop1 = " and f_num>=10000 ";}
        }
      //if(isset($djspar['dopprint']))
      //  {$namedop2 = " f_dopprint in (".$djspar['dopprint'].") ";}
      if(isset($djspar['wnz']))
        {$wnz = $djspar['wnz'];}
      //echo $wnz;
      if(isset($djspar['gus']))
        {$gus    = " f_uslstr ";
        $gususl = " and f_dopprint=1 ";$zval="";}
      if(isset($djspar['seloprval']))
        {$seloprval = $djspar['seloprval'];}
      if(isset($djspar['specinvid']))
        {$specinvid = $djspar['specinvid'];}
      if(isset($djspar['dopprstr']))
        {$gus      = " f_dopprstr ";
        $dopprstr  = $djspar['dopprstr'];}
      }
    $ctypeal="";
    if($curType>=0)
      {
      $ctype=$curType;
      if($ctype==86){$ctypeal="s86.";}
      $curType = " and ".$ctypeal."f_type=".$curType." ";
      }
    else
      {$curType = "";}
    if($isext>=0)
      {$isext = " and ".$ctypeal."f_isext=".$isext." ";}
    else
      {$isext = "";}
    //echo strlen($curNum)."|".$curType."|".$seloprval."|".$specinvid."<br>";
    //if($_SESSION['loginid']==2){echo strlen($curNum)."|".$curType."|".$seloprval."|".$specinvid."|".$ordname."<br>";}
    if(strlen($curNum)>0)
      {
      $pos = strpos($curNum,">");
      if($pos === false)
        {
        $pos = strpos($curNum,"<");
        if($pos === false){$curNum = " and ".$ctypeal."f_num in (".$curNum.") ";}
        else {$curNum = " and ".$ctypeal."f_num ".$curNum." ";}
        }
      else {$curNum = " and ".$ctypeal."f_num ".$curNum." ";}
      }
    //sdid2722
    //if(($fcurtype==4)&&(strlen($curNum)==0)&&(($seloprval==7)||($seloprval==8)||($seloprval==11)||($seloprval==16))&&($specinvid>0))//sdid2685
    //if(($fcurtype==4)&&(strlen($curNum)==0)&&(($seloprval==7)||($seloprval==8)||($seloprval==11)||($seloprval==16)||($seloprval==17))&&($specinvid>0))//sdid2685 //sdid 3047
    if(($fcurtype==4)&&(strlen($curNum)==0)&&(($seloprval==7)||($seloprval==8)||($seloprval==9)||($seloprval==11)||($seloprval==16)||($seloprval==17))&&($specinvid>0))//sdid2685 //sdid 3047
    //~sdid2722
      {$curNum = " and ".$ctypeal."f_num=(select f_val from veda_spec_invoices where f_id=$specinvid) ";}
    if($dopprstr>0)
      {$dopprstrs = " and (f_dopprstr is not null and length(f_dopprstr)>0) ";}
    if(strlen($ordname)>0)
      {$ordname = " ORDER BY f_".$ordname;}
    if($ctype==4)//валюту сортируем по коду
      {$ordname = " ORDER BY f_id";}
    // sdid 2707
    //
    //1)Таможенные платежи
    //2)Товар
    //3)Логистика
    //
    if($ctype==124)
      {
      $ordname = " ORDER BY CASE 
       WHEN f_num = 2 THEN 1 
       WHEN f_num = 1 THEN 2 
       WHEN f_num = 8 THEN 3 
       END ASC ";
      }
    // ~ sdid 2707
    $smstyle = "";
    if(strlen($mstyle)>0)
      {$smstyle="style='".$mstyle."'";}
    $sclas = "selval";
    if(isset($ws2))
      {
      if($ws2>0)
        {$sclas = "getSelSprValSel2".$did;}
      elseif(strlen($curclass)>0)
        {$sclas = $curclass;}
      }
    $wdisstr = "";
    if(isset($wdis))
      {
      if($wdis==1)
        {$wdisstr = " disabled=\"disabled\" ";}
      }
    //echo $sclas;
    //echo $curNum."<br>";
    //подключаемся к базе
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
    //указываем, мы хотим использовать utf8
    $dbh->exec('SET CHARACTER SET utf8');
    if($noselz==1)
      {$response = "";}
    else
      {$response = "<select ".$wdisstr." class='".$sclas."' ".$smstyle." id='getSelSprVal".$did."' $oncnhgfuntxt>";}//sdid3342
    //echo $wnz."<br>";
    //echo $wnoval;
    if($wnoval==1)
      {$response = $response."<option value=''>Введите значение</option>";}
    else
      {
      if($wnz==0)
        {
        if($forsearch==1)
          {$response = $response."<option value>".$zval."</option>";}
        else
          {$response = $response."<option value='0'>".$zval."</option>";}
        }
      elseif($wnz==-1)                                                  //sdid3010
        {$response = $response."<option value='-1'>".$zval."</option>";}//sdid3010
      }
    //получаем список для выборки
    //echo $namedop2."<br>";
    //if($_SESSION['loginid']==2){echo "$ctype<br>";}
    if($ctype==86)
        //if($ctype==1186)
      {
      if($s86easy==0)
        {$sql = "SELECT ".$ctypeal."f_num f_num,concat(s83.f_name,'/',s84.f_name,'/',s85.f_name,'/',".$ctypeal."f_name) f_name FROM ".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."spr s83 ".
          "where ".$ctypeal."f_type=86 and s85.f_type=85 and s84.f_type=84 and s83.f_type=83 and s86.f_dopprint=1 and ".$ctypeal."f_uslint=s85.f_num and ".
          "s85.f_uslint=s83.f_num and s85.f_dopprint=s84.f_num and ".$ctypeal."f_isuse=".$isuse." ".$curType." ".$curNum." ".$isext." ".$ordname;}
      elseif($s86easy==1)
        {$sql = "SELECT ".$ctypeal."f_num f_num,".$ctypeal."f_name f_name FROM ".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."spr s83 ".
          "where ".$ctypeal."f_type=86 and s85.f_type=85 and s84.f_type=84 and s83.f_type=83 and s86.f_dopprint=1 and ".$ctypeal."f_uslint=s85.f_num and ".
          "s85.f_uslint=s83.f_num and s85.f_dopprint=s84.f_num and ".$ctypeal."f_isuse=".$isuse." ".$curType." ".$curNum." ".$isext." ".$ordname;}
      elseif($s86easy==2)
        {$sql = "SELECT ".$ctypeal."f_num f_num,concat(s83.f_name,'/',s84.f_name,'/',s85.f_name,'/',".$ctypeal."f_name) f_name FROM ".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."spr s83 ".
          "where ".$ctypeal."f_type=86 and s85.f_type=85 and s84.f_type=84 and s83.f_type=83 and ".$ctypeal."f_uslint=s85.f_num and ".
          "s85.f_uslint=s83.f_num and s85.f_dopprint=s84.f_num and ".$ctypeal."f_isuse=".$isuse." ".$curType." ".$curNum." ".$isext." ".$ordname;}
      //echo $sql."<br>";
      //if($_SESSION['loginid']==2){echo "$sql<br>";}
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $sels = "";
        if(isset($selid))
          {
          if($selid>0)
            {
            if($row['f_num']==$selid)
              {$sels = "selected";}
            }
          }
        if($forsearch==1)
          {$response = $response."<option ".$sels." value='".$row['f_name']."'>".$row['f_name']."</option>";}
        else
          {$response = $response."<option ".$sels." value='".$row['f_num']."'>".$row['f_name']."</option>";}
      	}
      }
    else
      {
      if($ctype==143)
      //sdid 3623
        //{$sql = "SELECT f_type,f_num,concat((select f_abbr from ".DBPref."clients where f_id=f_uslint),'/',f_name) f_name 
        //         FROM ".DBPref."spr where f_id>=0 and f_isuse=".$isuse." ".$suslint." ".$curType." ".$curNum." ".$isext." ".$gususl." ".$ordname." ".$namedop." ".$namedop1;}
        {$sql = "SELECT f_uslint,f_type,f_num,concat((select f_abbr from ".DBPref."clients where f_id=f_uslint),'/',f_name) f_name 
                 FROM ".DBPref."spr where f_id>=0 and f_isuse=".$isuse." ".$suslint." ".$curType." ".$curNum." ".$isext." ".$gususl." ".$ordname." ".$namedop." ".$namedop1;}
      //~sdid 3623
      else
        {
        if(($ctype==63)&&(strlen($dopprstrs)>0))      //sdid3236
          {$gus="concat(f_name,' (',f_namedop,')') ";}//sdid3236
        $sql = "SELECT f_uslint,f_namedop,f_type,f_num,".$gus."f_name FROM ".DBPref."spr where f_id>=0 and f_isuse=".$isuse." ".$suslint." ".$curType." ". //sdid3342 //sdid3400
        //sdid - 389
        $type_oper_id_filter." ".
        //~sdid - 389
        $curNum." ".$isext." ".$gususl." ".$dopprstrs." ".$ordname." ".$namedop." ".$namedop1;
        }
        //if($_SESSION['loginid'])
        //  {
        //  if(($_SESSION['loginid']==2)&&($fcurtype==2))
        //    {echo $sql."<br>";}
        //  }
        //echo $selid."<br>";
        //echo $sql."<br>";
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $sels = "";
        if(isset($selid))
          {
          if($selid>0)
            {
            if($row['f_num']==$selid)
              {$sels = "selected";}
            }
          }
        if($forsearch==1)
          {$response = $response."<option ".$sels." value='".$row['f_name']."'>".$row['f_name']."</option>";}
        else
          {
          //sdid3342
          $dopvalue="";if($fcurtype==4){$dopvalue="svalue=\"".$row['f_namedop']."\"";}
          $response = $response."<option uslint='".$row['f_uslint']."' ".$sels." $dopvalue value='".$row['f_num']."'>".$row['f_name']."</option>";
          //~sdid3342
          }
        }
      }
    if($noselz==0)
      {$response = $response."</select>";}
    //echo $response;
    return $response;
    }
  catch (PDOException $e)
    {
    echo "<select class='selval' id='getSelSprVal".$did."'><option value='0'>Database error: ".$e->getMessage()."</option></select>";
    }
  }

//Функция получает от ройстат данные о лидах за последние 10 дней и пишет их базу
function RSleadsImport($api_url, $path, $project_id, $api_key, $tint)
{
    try
    {
        //$dtfrom = new DateTime('-1 days');
        $url = $api_url;
        $url .= "".$path."";
        $url .= "?project=".$project_id."";
        //$url .= "&key=".$api_key."&period=".$dtfrom->format('Y-m-d')."-".date('Y-m-d');
        //$url .= "&key=".$api_key."&period=".date('Y-m-d')."-".date('Y-m-d');
        //echo "1 ".$url."<br>";
        $wt = 0;
        if(isset($tint))
        {
            if($tint==2)
            {
                $wt = 1;
                $dtfrom = new DateTime('-2 days');
                $url .= "&key=".$api_key."&period=".$dtfrom->format('Y-m-d')."-".date('Y-m-d');
                //echo "2 ".$url."<br>";
            }
        }
        if($wt==0)
        {
            $url .= "&key=".$api_key."&period=".date('Y-m-d')."-".date('Y-m-d');
            //echo $url."<br>";
        }
        //if($tint>0)
        //  {$url .= "&key=".$api_key."&period=".$dtfrom->format('Y-m-d')."-".date('Y-m-d');}
        //else
        //  {$url .= "&key=".$api_key."&period=".$dtfrom->date('Y-m-d',strtotime('-7 hours -1 minutes'))."-".date('Y-m-d',strtotime('-7 hours -1 minutes'));}
        //echo $url."<br>";
        //Отправляем POST запрос в Roistat
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'Content-type: application/x-www-form-urlencoded'
                //,
                //'content' => '{"filters":{"and": [["date",">","2020-05-15T00:01:00+0000"],["date","<","2020-05-15T23:00:00+0000"]]},"sort": ["date","desc"],"limit": 5,"offset": 0}'
            ))));
        $result = json_decode($result, true);
        //print_r($result);
        $retstr="";
        $col=0;
        foreach($result['ProxyLeads'] as $result)
        {
            //echo $result["id"]."_".$result["title"]."<br>";
            $rstype                = 0;
            $curtitle              = $result["title"];
            if(strcmp(substr($curtitle,0,strlen("Звонок от")),"Звонок от")==0)
            {$curtitle = "Звонок от";}
            $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
            $sql = "select f_num from ".DBPref."spr where f_type=56 and f_namedop like '".$curtitle."'";
            $res = $dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                $rstype              = $row['f_num'];
            }
            $txts = explode("\n", $result["text"]);
            if($rstype==0)
            {mSendMail(0,0,2,"Загрузка ЛИДов ROISTAT","Не оперделен тип ЛИДа: ".$curtitle);}
            //echo $result["id"]."_".$result["title"]."_".$result["text"]."<br>";
            foreach ($txts as $value)
            {
                $text_pagez         = "";
                $text_promocod      = "";
                $text_ist           = "";
                $text_bphone        = "";
                $text_callrecordref = "";
                $text_chatref       = "";
                $text_msg           = "";
                $text_company       = "";
                $text_where         = "";
                $text_ob            = "";
                $text_massa         = "";
                //$value=str_replace("\n","",$value);
                //echo $result["id"]."_".$result["title"]."_".$result["text"]."_".$value."<br>";
                //echo $result["id"]."_".$value."_".mb_strlen($value,"utf-8")."_".mb_strlen("Компания:","utf-8")."_".strlen("Компания:")."_".mb_substr($value,0,mb_strlen("Компания:","utf-8"),"utf-8")."<br>";
                //echo $result["id"]."<br>";
                //echo mb_check_encoding($value,"utf-8")."<br>";
                $value=trim(mb_convert_encoding($value,"utf-8"));
                //echo mb_substr($value,0,9)."<br>";
                //echo strcmp(mb_substr($value,0,mb_strlen("Компания:")),"Компания:")."<br>";
                //if(strcmp(substr($value,0,strlen("Компания:")),"Компания:")==0)
                //  {echo "123<br>";$text_company = trim(mb_substr($value,mb_strlen("Компания:")));}
                if(strcmp(substr($value,0,strlen("Страница захвата:")),"Страница захвата:")==0)
                {$text_pagez = trim(substr($value,strlen("Страница захвата:")));}
                elseif(strcmp(substr($value,0,strlen("Промокод:")),"Промокод:")==0)
                {$text_promocod = trim(substr($value,strlen("Промокод:")));}
                elseif(strcmp(substr($value,0,strlen("Источник:")),"Источник:")==0)
                {$text_ist = trim(substr($value,strlen("Источник:")));}
                elseif(strcmp(substr($value,0,strlen("Набранный номер:")),"Набранный номер:")==0)
                {$text_bphone = trim(substr($value,strlen("Набранный номер:")));}
                elseif(strcmp(substr($value,0,strlen("Запись звонка:")),"Запись звонка:")==0)
                {$text_callrecordref = trim(substr($value,strlen("Запись звонка:")));}
                elseif(strcmp(substr($value,0,strlen("Ссылка на чат в архивах:")),"Ссылка на чат в архивах:")==0)
                {$text_chatref = trim(substr($value,strlen("Ссылка на чат в архивах:")));}
                elseif(strcmp(substr($value,0,strlen("Сообщение:")),"Сообщение:")==0)
                {$text_msg = trim(substr($value,strlen("Сообщение:")));}
                //elseif(strcmp(mb_substr($value,0,mb_strlen("Компания:")),"Компания:")==0)
                elseif(strcmp(substr($value,0,strlen("Компания:")),"Компания:")==0)
                {$text_company = trim(mb_substr($value,mb_strlen("Компания:")));}
                elseif(strcmp(substr($value,0,strlen("Куда:")),"Куда:")==0)
                {$text_where = trim(substr($value,strlen("Куда:")));}
                elseif(strcmp(substr($value,0,strlen("Объем, м3:")),"Объем, м3:")==0)
                {$text_ob = trim(substr($value,strlen("Объем, м3:")));}
                elseif(strcmp(substr($value,0,strlen("Вес, кг:")),"Вес, кг:")==0)
                {$text_massa = trim(substr($value,strlen("Вес, кг:")));}
                //echo $text_company."<br>";
            }
            $creation_date         = "'".$result["creation_date"]."'";
            $of_COMPANY_TITLE      = "";
            //$of_COMPANY_TITLE      = $result[""];
            $of_orderPage          = "";
            $of_manager_email      = "";
            $of_ASSIGNED_BY_ID     = "";
            $of_SOURCE_ID          = "";
            $of_SOURCE_DESCRIPTION = "";
            //$status                = $result[""];
            if(strlen($result["phone"])==10)
              {$result["phone"]="7".substr($result["phone"],0,10);}
            if((strlen($result["phone"])==11)&&(strcmp(substr($result["phone"],0,1),"8")==0))
              {$result["phone"]="7".substr($result["phone"],1,10);}
            $contid = 0;$stat=0;$conttp=0;$duration = 0;
            if($rstype==1)//если звонок, то поищем данные в звонках
              {
              $pfrom=0;$pto=0;
              $pfrom = strpos($text_callrecordref, "/call/");
              $pto   = strpos($text_callrecordref, "/file/");
              //echo $text_callrecordref."|$pfrom|$pto<br>";
              if(($pfrom>0)&&($pto>0)&&($pto>($pfrom+6)))
                {
                $pfrom = $pfrom+6;
                $pto   = $pto-$pfrom;
                $fcid  = "";
                //echo $text_callrecordref."<br>";
                $fcid = substr($text_callrecordref,$pfrom,$pto);
                //echo $fcid."<br>";
                if(strlen($fcid)>0)
                  {
                  $sql = "select f_id,f_sysstatus,f_contactid,f_clnttype,f_duration from ".DBPref."RS_calls where f_rsid=".$fcid;
                  //echo $sql."<br>";
                  $res = $dbh->query($sql);
                  if($row = $res->fetch(PDO::FETCH_ASSOC))
                    {
                    $contid   = $row['f_contactid'];
                    $stat     = $row['f_sysstatus'];
                    $conttp   = $row['f_clnttype'];
                    $duration = $row['f_duration'];
                    //echo $row['f_id']."|".$contid,"|".$stat."<br>";
                    }
                  }
                }
              }
            $sql = "select f_id from ".DBPref."RS_leads where f_rsid=".$result["id"];
            $res = $dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
              {
              $sql = "update ".DBPref."RS_leads  set f_rsid=".$result["id"].",f_rstype=".$rstype.",f_title='".$result["title"]."',f_text='".$result["text"]."',f_text_pagez='".$text_pagez."',f_text_promocod='".$text_promocod."',".
                  "                          f_text_ist='".$text_ist."',f_text_bphone='".$text_bphone."',f_text_callrecordref='".$text_callrecordref."',f_text_chatref='".$text_chatref."',f_text_msg='".$text_msg."',".
                  "                          f_text_company='".$text_company."',f_text_where='".$text_where."',f_text_ob='".$text_ob."',f_text_massa='".$text_massa."',f_name='".$result["name"]."',f_phone='".$result["phone"]."',".
                  "                          f_email='".$result["email"]."',f_roistat='".$result["roistat"]."',f_creation_date=".$creation_date.",f_order_id='".$result["order_id"]."',f_of_COMPANY_TITLE='".$of_COMPANY_TITLE."',".
                  "                          f_of_orderPage='".$of_orderPage."',f_of_manager_email='".$of_manager_email."',f_of_ASSIGNED_BY_ID='".$of_ASSIGNED_BY_ID."',f_of_SOURCE_ID='".$of_SOURCE_ID."',".
                  "                          f_of_SOURCE_DESCRIPTION='".$of_SOURCE_DESCRIPTION."',f_status=".$stat.",f_contactid=".$contid." where f_id=".$row["f_id"];
              }
            else
              {
              $sql = "insert into ".DBPref."RS_leads (       f_rsid    , f_rstype  ,          f_title     ,          f_text     ,  f_text_pagez   ,  f_text_promocod   ,  f_text_ist   , f_text_bphone    ,  f_text_callrecordref   ,  f_text_chatref,     f_text_msg,     f_text_company,     f_text_where,     f_text_ob,     f_text_massa,             f_name  ,             f_phone,               f_email,               f_roistat,      f_creation_date,            f_order_id,f_status ,f_contactid,f_clnttype) values ".
                  "                          (".$result["id"].",".$rstype.",'".$result["title"]."','".$result["text"]."','".$text_pagez."','".$text_promocod."','".$text_ist."','".$text_bphone."','".$text_callrecordref."','".$text_chatref."','".$text_msg."','".$text_company."','".$text_where."','".$text_ob."','".$text_massa."','".$result["name"]."','".$result["phone"]."','".$result["email"]."','".$result["roistat"]."',".$creation_date.",'".$result["order_id"]."',".$stat.",".$contid.",".$conttp.")";
              $dbh->exec($sql);
              }
            //echo $sql."<br>";
            //$dbh->exec($sql);
        }
    }
    catch (TypeError $e)
    {mSendMail(0,0,2,"Загрузка ЛИДов ROISTAT","Error: ".$e->getMessage());}
}

//Функция получает от ройстат данные о звонках за текущий день и пишет их базу
function RScallsImport($api_url, $path, $project_id, $api_key, $tint)
{
    try
    {
        $url = $api_url;
        $url .= "".$path."";
        $url .= "?project=".$project_id."";
        $url .= "&key=".$api_key."";
        if($tint==0)
        {$dtfl = '{"filters":{"and": [["date",">","'.date('Y-m-d',strtotime('-7 hours -55 minutes')).'T'.date('H:i:s',strtotime('-7 hours -55 minutes')).'+0000"],["date","<","'.date('Y-m-d').'T23:59:59+0000"]]},"sort": ["date","desc"],"limit": 100,"offset": 0}';}
        else
        {$dtfl = '{"filters":{"and": [["date",">","'.date('Y-m-d',strtotime('-7 hours -1 minutes')).'T00:00:01+0000"],["date","<","'.date('Y-m-d',strtotime('-7 hours -1 minutes')).'T23:59:59+0000"]]},"sort": ["date","desc"],"limit": 1000,"offset": 0}';}
        //echo $dtfl."<br>";
        //Отправляем POST запрос в Roistat
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                //'content' => '{"filters":{"and": [["date",">","'.date('Y-m-d').'T00:00:01+0000"],["date","<","'.date('Y-m-d').'T23:59:59+0000"]]},"sort": ["date","desc"],"limit": 10,"offset": 0}'
                //'content' => '{"filters":{"and": [["date",">","'.date('Y-m-d',strtotime('-5 minutes')).'T'.date('H:i:s',strtotime('-10 minutes')).'+0000"]]},"sort": ["date","desc"],"limit": 10,"offset": 0}'
                //'content' => '{"filters":{"and": [["date",">","2020-09-01T00:00:01+0000"],["date","<","2020-09-28T23:59:59+0000"]]},"sort": ["date","desc"],"limit": 5,"offset": 0}'
                'content' => $dtfl
            ))));
        $result = json_decode($result, true);
        //print_r($result);
        //echo "<br>";
        //echo "order_id=".$result["order_id"]."<br>";
        foreach($result['data'] as $result)
        {
            if(!isset($result["order_id"]))
            {$result["order_id"]=0;}
            //if($result["order_id"]="")
            //  {echo $result["order_id"]."<br>";}
            $dt  = substr($result["date"],0,10)." ".substr($result["date"],11,8);
            $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
            $sql = "select f_id,f_duration from ".DBPref."RS_calls where f_rsid=".$result["id"];
            //echo $sql."<br>";
            $res = $dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
              {
              $sysst = "";
              if(($row['f_duration']==0)&&($result["duration"]>0)&&($result["duration"]<=20)){$sysst = ",f_sysstatus=2";}
              $sql = "update ".DBPref."RS_calls  set f_rsid=".$result["id"].",f_callee='".$result["callee"]."',f_caller='".$result["caller"]."'".$sysst.",".
                  "                          f_duration=".$result["duration"].",f_waiting_time=".$result["waiting_time"].",f_answer_duration=".$result["answer_duration"].",".
                  "                          f_script_name='".$result["script_name"]."',f_status='".$result["status"]."',f_date='".$dt."',".
                  "                          f_order_id=".$result["order_id"]." ".
                  //"                          ,f_system_name='".$result[""]."' ".
                  "where f_id=".$row["f_id"];
              }
            else
            {
                $cntid = 0;
                $cnttp = 0;
                //$sql = "select f_clntid from ".DBPref."addres where f_type=2 and f_addr='".$result["caller"]."' and f_clnttype=1";
                $sql = "select f_clntid,f_clnttype from ".DBPref."addres where f_type=2 and f_addr like '%".substr($result["caller"],1)."%'";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                    $cntid = $row['f_clntid'];
                    $cnttp = $row['f_clnttype'];
                }
                $sysst = 0;
                //if($result["duration"]<=20){$sysst = 2;}
                $sql = "insert into ".DBPref."RS_calls (f_sysstatus,      f_rsid    ,          f_callee,               f_caller,              f_duration,             f_waiting_time,             f_answer_duration,              f_script_name,               f_status,     f_date,            f_order_id    ,f_contactid,f_clnttype) values ".
                       "                                (".$sysst.",".$result["id"].",'".$result["callee"]."','".$result["caller"]."',".$result["duration"].",".$result["waiting_time"].",".$result["answer_duration"].",'".$result["script_name"]."','".$result["status"]."','".$dt."',".$result["order_id"].",".$cntid." ,".$cnttp.")";
            }
            //echo $sql."<br>";
            $dbh->exec($sql);
        }
    }
    catch (TypeError $e)
    {mSendMail(0,0,2,"Загрузка звонков ROISTAT","Error: ".$e->getMessage());}
}

//function getValSumInRUB($valc,$vals,$prc,$dt)
//{
//$retval=0;
//$client = new SoapClient("http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL",array('soap_version' => SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE,'trace' => true,'features' => SOAP_USE_XSI_ARRAY_TYPE));
//$ParametrXDTO= Array("On_date" => $dt);
//$result = $client->GetCursOnDate($ParametrXDTO);
//$data = new SimpleXMLElement($result->GetCursOnDateResult->any);
//foreach ($data->ValuteData->ValuteCursOnDate as $curs)
//  {
//  //echo var_dump($curs);
//  if($curs->Vcode==$valc)
//    {
//    //echo floatval($curs->Vcurs)."<br>".floatval($curs->Vnom)."<br>";
//    $retval=$vals*floatval($curs->Vcurs)/floatval($curs->Vnom);
//    //echo $retval."<br>";
//    $retval=round(($retval+$retval*$prc/100),2);
//    }
//  }
//return $retval;
//}

//проверить доступность выгрузки данных в 1С
function c1c_checkBuhDt($dt)
{
    //echo "dt1: ".$dt."<br>";
    //sdid 3596 ----!!! временно закрыл это чтобы получать достоверные сведения при разработке!
    //if($_SESSION['loginid']==2)
    //  {return true;}
    //~sdid 3596
    $retval = false;
    if(isset($dt))
    {
        //echo "dt2: ".$dt."<br>";
        try
        {
            $dbh = dbconnect();
            $sql = "select count(*) cnt from (select max(f_dtbuhcls) mdt from ".DBPref."settings where f_settype=1) k where k.mdt>='".substr($dt,0,10)."'";
            //echo $sql."<br>";
            //error_log("sql= \n$sql\n\n",3,"/var/www/html/veda/logs/sdid3596.log");
            $res = $dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
            {if($row['cnt']==0){$retval = true;}}
        }
        catch (Exception $e)
        {return $retval;}
    }
    return $retval;
}

function c1c_SoapClient($cwsdl)
{
    $client = new SoapClient($cwsdl,
        array(
            //'login' => 'vasys', //логин пользователя к базе 1С
            //'password' => '<REDACTED>', //пароль пользователя к базе 1С
            //'connection_timeout' => 380,
            'soap_version' => SOAP_1_2, //версия SOAP
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'features' => SOAP_USE_XSI_ARRAY_TYPE
        )
    );
    return $client;
}

function c1c_createSoapClient()
{
    echo c1cwsdl."<br>";
    //$client = new SoapClient($c1cwsdl,
    //    array(
    //      //'login' => "site", //логин пользователя к базе 1С
    //      //'password' => '<REDACTED>', //пароль пользователя к базе 1С
    //      'soap_version' => SOAP_1_2, //версия SOAP
    //      'cache_wsdl' => WSDL_CACHE_NONE,
    //      'trace' => true,
    //      'features' => SOAP_USE_XSI_ARRAY_TYPE
    //      )
    //    );
    //return $client;
}

//function c1c_getInvoiceStatus($num,$dt,$orgkod)
//{
//$retval = "-";
//$client = c1c_createSoapClient();
//$ParametrXDTO= Array(
//    "num" => $num,
//    "dt" => $dt,
//    "orgkod" => $orgkod);
//$result   = $client->getInvoiceStatus($ParametrXDTO);
//$jsResult = $result->return;
//$data     = json_decode($jsResult, true);
//echo var_dump($data);
//$col      = count($data);
//if($col>0)
//  {
//  if($data[0]['retval']=="true")
//    {$retval=$data[0]['msg'];}
//  }
//return $retval;
//}

//добавить/обновить номенклатуру
function c1c_addupdNomenk($kod1c, $name, $group1c, $edizm, $jstr, $nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
    try
    {
        //echo $cwsdl;
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        //echo $cwsdl;
        $client = c1c_SoapClient($cwsdl);
        $ParametrXDTO = Array(
            "kod1c"   => $kod1c,
            "name"    => $name,
            "group1c" => $group1c,
            "edizm"   => $edizm,
            "jstr"    => $jstr);
        //echo $cwsdl;
        $result = $client->addupdNomenk($ParametrXDTO);
        //var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        //var_dump($jsResult);
        $data   = json_decode($jsResult,true);
        $retval = $data;
        return $retval;
    }
    catch (Error $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//загрузить номенклатуру
function c1c_getNomenk($grpcode,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
    try
    {
        //echo $cwsdl."<br>";
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        $client = c1c_SoapClient($cwsdl);
        $ParametrXDTO= Array(
            "grpcode" => $grpcode);
        $result = $client->getNomenk($ParametrXDTO);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        //var_dump($jsResult);
        $data   = json_decode($jsResult,true);
        $retval = $data;
        //var_dump($retval[0]);
        return $retval;
    }
    catch (Error $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//sdid3047
//удалить документ - корректировка долга
function c1c_deleteDutyCorrect($docnum, $docdt, $nbd1c)
  {
  $check = check1CExchangeBan();
  if($check[0]){return json_decode($check[1],true);}
  if(c1c_checkBuhDt($docdt))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    try
      {
      $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
      $client = c1c_SoapClient($cwsdl);
      $ParametrXDTO= Array(
          "dognum" => $docnum,
          "dogdt"  => $docdt);
      $result = $client->deleteDutyCorrect($ParametrXDTO);
      //Обработаем возвращаемый результат
      $jsResult = str_replace("\n","",$result->return);
      $data   = json_decode($jsResult,true);
      return $data;
      }
    catch (Error $e)
      {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
  else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$docdt." удаление запрещено\"}",true);} // sdid 2125
  }
//~sdid3047

//sdid 3168
function c1c_deleteDifRate($docnum, $docdt, $nbd1c)
  {
  $check = check1CExchangeBan();
  if($check[0]){return json_decode($check[1],true);}
  if(c1c_checkBuhDt($docdt))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    try
      {
      $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
      $client = c1c_SoapClient($cwsdl);
      $ParametrXDTO= Array(
          "docnum" => $docnum,
          "docdt"  => $docdt);
      $result = $client->deleteDifRate($ParametrXDTO);
      //Обработаем возвращаемый результат
      $jsResult = str_replace("\n","",$result->return);
      $data   = json_decode($jsResult,true);
      return $data;
      }
    catch (Error $e)
      {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} 
    }
  else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$docdt." удаление запрещено\"}",true);} 
  
  }
//~sdid 3168

//создать/обновить документ - корректировка долга
//function c1c_createDutyCorrect($docnum, $docdt, $jstr, $varr, $nbd1c) //sdid 3234
function c1c_createDutyCorrect($docnum, $docdt, $jstr, $varr, $nbd1c, $olddt=null) //sdid 3234
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  //echo $docdt."<br>";
  if(c1c_checkBuhDt($docdt))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    try
      {
      //echo $cwsdl."<br>";
      $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
      $client = c1c_SoapClient($cwsdl);
      //echo $docdt."<br>".$jstr."<br>".$varr."<br>";
      if($olddt == null) {$olddt = $docdt;} //sdid 3234
      //sdid 3234
      /*$ParametrXDTO= Array(
          "dognum" => $docnum,
          "dogdt"  => $docdt,
          "jstr"   => $jstr,
          "varr"   => $varr);*/
      $ParametrXDTO= Array(
          "dognum" => $docnum,
          "dogdt"  => $docdt,
          "jstr"   => $jstr,
          "varr"   => $varr,
          "olddt"  => $olddt);
      //~sdid 3234
      //var_dump($ParametrXDTO);
      $result = $client->createDutyCorrect($ParametrXDTO);
      //var_dump($result);
      //Обработаем возвращаемый результат
      $jsResult = str_replace("\n","",$result->return);
      //var_dump($jsResult);
      $data   = json_decode($jsResult,true);
      //var_dump($data);
      //$retval = $data[0];
      //var_dump($retval);
      return $data;
      }
    catch (Error $e)
      //      {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
      {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
  else
    //    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$dogdt." выгрузка запрещена\"}",true);} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$docdt." выгрузка запрещена\"}",true);} // sdid 2125
  }

//перенести спецфикацию в закрытые в 1С
function c1c_clsSpec($kodcb, $nbd1c, $dopstr)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1], true);}
  // ~ sdid 2125
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
  {$cwsdl = c1cwsdl2;}
  try
    {
        //echo $cwsdl."<br>";
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        $ParametrXDTO= Array(
            "kodcb" => $kodcb);
        $result = $client->clsSpec($ParametrXDTO);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        //var_dump($jsResult);
        $data   = json_decode($jsResult,true);
        $retval = $data[0];
        //var_dump($retval[0]);
        return $retval;
    }
    catch (Error $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//sdid3596
//добавить/обновить корректировку акта Поступление в 1С
function c1c_addupdAktCorrect($kodc,$dtc,$orgcodc,$vidoper,$inaktnum,$inaktdt,$ckod,$dkod,$dval,$dsum,$OP,$dstr)
  {
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
  try
    {
    //error_log("\n\nc1c_addupdAktCorrect\n\n",0, "/var/www/html/veda/logs/sdid3596.log");
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $client = new SoapClient($cwsdl,
                             array(
                                   //'login' => 'vasys', //логин пользователя к базе 1С
                                   //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                                   //'connection_timeout' => 380,
                                   'soap_version' => SOAP_1_2, //версия SOAP
                                   'cache_wsdl' => WSDL_CACHE_NONE,
                                   'trace' => true,
                                   'features' => SOAP_USE_XSI_ARRAY_TYPE));
    $ParametrXDTO= Array(
        "kodc"     => $kodc,
        "dtc"      => $dtc,
        "orgkodc"  => $orgcodc,
        "vidoper"  => $vidoper,
        "inaktnum" => $inaktnum,
        "inaktdt"  => $inaktdt,
        "ckod"     => $ckod,
        "dkod"     => $dkod,
        "dval"     => $dval,
        "dsum"     => $dsum,
        "op"       => $OP,
        "dstr"     => $dstr);
    //error_log("\n\nParametrXDTO = ".var_export($ParametrXDTO,true)."\n\n",0, "/var/www/html/veda/logs/sdid3596.log");
    $result = $client->addupdAktCorrect($ParametrXDTO);
    //error_log("\n\nresult = ".var_export($result,true)."\n\n",0, "/var/www/html/veda/logs/sdid3596.log");
    //Обработаем возвращаемый результат
    $jsResult = str_replace("\n","",$result->return);
    $data = json_decode($jsResult,true);
    $retval=$data;
    return $retval;
    }
  catch (Error $e)
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);}
  }
//~sdid3596

//добавить/обновить Реализация (акты, накладные, УПД) в 1С
function c1c_addupdAktR($okod, $ckod, $dkod, $numc, $dtc, $comm, $dsum, $dval, $vnds, $vozmc, $dparm, $nbd1c, $dopstr,$op)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  if(c1c_checkBuhDt($dtc))
    {
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        try
        {
            $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
            //echo $okod."<br>";
            //echo $ckod."<br>";
            //echo $dkod."<br>";
            //echo $numc."<br>";
            //echo $dtc."<br>";
            //echo $comm."<br>";
            //echo $dsum."<br>";
            //echo $dval."<br>";
            //echo $vnds."<br>";
            //echo $vozmc."<br>";
            //echo $dparm."<br>";
            //echo $dopstr."<br>";
            //echo $op."<br>";
            $client = new SoapClient($cwsdl,
                array(
                    //'login' => 'vasys', //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    //'connection_timeout' => 380,
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
            //echo "comm: $comm<br>";
            $ParametrXDTO= Array(
                "okod"   => $okod,
                "ckod"   => $ckod,
                "dkod"   => $dkod,
                "numc"   => $numc,
                "dtc"    => $dtc,
                "comm"   => $comm,
                "dsum"   => $dsum,
                "dval"   => $dval,
                "vnds"   => $vnds,
                "vozmc"  => $vozmc,
                "dparm"  => $dparm,
                "dopstr" => $dopstr,
                "op"     => $op);
            //Var_dump($ParametrXDTO);echo "<br>";
            $result = $client->addupdAktR($ParametrXDTO);
            //if($_SESSION['loginid']==2){Var_dump($result);echo "<br>";}
            //Var_dump($result);
            //Обработаем возвращаемый результат
            $jsResult = str_replace("\n","",$result->return);
            $data = json_decode($jsResult,true);
            $retval=$data;
            return $retval;
        }
        catch (Error $e)
        //        {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
        {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
    else
    {
        $rv = json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$dtc." выгрузка запрещена\"}",true);
        return $rv;}
}

//создать счет в 1С
function c1c_createInvoice($orgid,$obaccid,$contrid,$dogid,$invsum,$invval,$invdt,$invsrok,$wnds,$nomenk,$grnd,$num,$nbd1c,$kodc,$dopstr,$invst,$dground,$op="")
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  if(c1c_checkBuhDt($invdt))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
        try
          {
          $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
          //echo c1cwsdl."<br>";
          $client = new SoapClient($cwsdl,
                array(
                    //'login' => "site", //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
          $nomenk = str_replace("&","&amp;",$nomenk);
            //echo c1cwsdl."<br>";
            //echo $orgid."<br>";
            //echo $obaccid."<br>";
            //echo $contrid."<br>";
            //echo $dogid."<br>";
            //echo $invsum."<br>";
            //echo $invval."<br>";
            //echo $invdt."<br>";
            //echo $invsrok."<br>";
            //echo $wnds."<br>";
            //echo $nomenk."<br>";
            //echo $grnd."<br>";
            //echo $num."<br>";
            //echo $kodc."<br>";
            //echo $dopstr."<br>";
          if(strcmp($invsrok,"0000-00-00")==0){$invsrok="";}
          $ParametrXDTO= Array(
                "orgid" => $orgid,
                "obaccid" => $obaccid,
                "contrid" => $contrid,
                "dogid"   => $dogid,
                "invsum"  => $invsum,
                "invval"  => $invval,
                "invdt"   => $invdt,
                "invsrok" => $invsrok,
                "wnds"    => $wnds,
                "nomenk"  => $nomenk,
                "grnd"    => $grnd,
                "invnum"  => $num,
                "kodc"    => $kodc,
                "dopstr"  => $dopstr,
                "invst"   => $invst,
                "dground" => $dground,
                "op"      => $op
                );
          //Выполняем операцию
          $result = $client->createInvoice($ParametrXDTO);
            //if($_SESSION['loginid']==2)
            //  {
            //  //var_dump(str_replace("\n","",$result->return));
            //  //$result->return = str_replace("\n","",$result->return);
            //  }
            //var_dump($result);
            //Обработаем возвращаемый результат
          //sdid 1427
          $result->return = str_replace("\n","",$result->return);
          //sdid 1427
          $jsResult = $result->return;
            //var_dump($jsResult);
          $data = json_decode($jsResult, true);
          //  if($_SESSION['loginid']==2)
          //    {var_dump($data);}
          //sdid 1315
          if(is_array($data))
            {
            $col  = count($data);
            if($col>0)
              {
              //if($data[0]['retval']=="true")
              //  {$retval=$data[0]['msg'];}
              $retval=$data[0];
              }
            }
          //!sdid 1315
          return $retval;
        }
        catch (TypeError $e)
        //        {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
        {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
    else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$invdt." выгрузка запрещена\"}",true);}
}

//sdid2025
function c1c_createBnkAcc($clntkod,$bic,$acc,$valс,$nbd1c,$isorg,$vid1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  //echo $valс."<br>";
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
  try
    {
    $retval = "-";
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    //echo c1cwsdl."<br>";
    $client = new SoapClient($cwsdl,
        array(
            //'login' => "site", //логин пользователя к базе 1С
            //'password' => '<REDACTED>', //пароль пользователя к базе 1С
            'soap_version' => SOAP_1_2, //версия SOAP
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
    $ParametrXDTO= Array(
             "clntkod" => $clntkod
            ,"bic"     => $bic
            ,"acc"     => $acc
            ,"valc"    => $valс
            ,"isorg"   => $isorg
            ,"vid"     => $vid1c
            );
    //Выполняем операцию
    $result = $client->createBnkAcc($ParametrXDTO);
    //Обработаем возвращаемый результат
    $jsResult = $result->return;
    $data = json_decode($jsResult, true);
    $col = count($data);
    if($col>0)
      {
      $retval=$data[0];
      }
    return $retval;
    }
  catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
  }
//~sdid2025

function c1c_createClient($cname,$addname,$inn,$grp,$nbd1c,$kpp,$ogrn,$uradr,$vid,$mcontr,$clcntrreg,$nalognumber="")//sdid2669
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  try
    {
        $retval = "-";
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        //echo c1cwsdl."<br>";
        //$client = new SoapClient(c1cwsdl,
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        $client = new SoapClient($cwsdl,
            array(
                //'login' => "site", //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        $cname   = str_replace("&","&amp;",$cname);
        $addname = str_replace("&","&amp;",$addname);
        //if(strlen($inn)==0)
        //  {$inn="0000000000";}
        //echo "cname:".$cname."<br>addname:".$addname."<br>cgroup:".$grp."<br>inn:".$inn."<br>";
        //$retval = $cname."<br>".$addname."<br>".$grp."<br>".$inn."<br>";
        $ParametrXDTO= Array(
            "cname"        => $cname,
            "addname"      => $addname,
            "cgroup"       => $grp,
            "inn"          => $inn,
            "ogrn"         => $ogrn,
            "kpp"          => $kpp,
            "uradr"        => $uradr,
            "vid"          => $vid,
            "mcontr"       => $mcontr,
            "clcntrreg"    => $clcntrreg
            ,"nalognumber" => $nalognumber//sdid2669
        );
        //Выполняем операцию
        $result = $client->createClient($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        //echo $jsResult;
        $data = json_decode($jsResult, true);
        $col = count($data);
        if($col>0)
        {
            //if($data[0]['retval']=="true")
            $retval=$data[0];
        }
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

function c1c_createDog($clntkod,$orgkod,$dognum,$dogtype,$dogname,$dogdt,$nbd1c,$kodc,$dogval)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  try
    {
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        //$retval = "-";
        //$client = new SoapClient(c1cwsdl,
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        $client = new SoapClient($cwsdl,
            array(
                //'login' => "site", //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $clntkod."<br>";
        //echo $orgkod."<br>";
        //echo $dognum."<br>";
        //echo $dogtype."<br>";
        //echo $dogname."<br>";
        //echo $dogdt."<br>";
        //echo $kodc."<br>";
        //echo $dogval."<br>";
        $ParametrXDTO= Array(
            "clntkod" => $clntkod,
            "orgkod"  => $orgkod,
            "dognum"  => $dognum,
            "dogtype" => $dogtype,
            "dogname" => $dogname,
            "dogdt"   => $dogdt,
            "kodc"    => $kodc,
            "dogval"  => $dogval);
        //Выполняем операцию
        $result = $client->createDog($ParametrXDTO);
        //var_dump($result);
        //echo "<br>";
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        //$jsResult = "[{\"retval\":\"true\",\"msg\":\"БП-003861\",\"msgg\":\"БП-004588\",\"msgo\":\"БП-004589\",\"msgc\":\"БП-004590\"}";
        //var_dump($jsResult);
        //echo "<br>";
        $data = json_decode($jsResult, true);
        //var_dump($data);
        //echo "<br>";
        $col = count($data);
        if($col>0)
        {
            //if($data[0]['retval']=="true")
            $retval=$data[0];
        }
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

// sdid 1776
/**
 * Экспортировать в указанную базу данных (bd1c) контрагента по его f_id в veda_clients (contrid)
 * @param $dbh PDO
 * @param $bd1c int
 * @param $contrid int
 * @return string
 * Возвращает массив в формате JSON.
 * $return[0] - true/false в зависимости от результата выгрузки
 * $return[1] - сообщение о результатах выгрузки
 * $return[2] - в случае успешной выгрузки, здесь передается код контрагента в базе 1С, например "БП-002270"
 */
function export_client_to_1c($dbh, $bd1c, $contrid)
  {
  $nbd1c_contr = $bd1c;
  $sql_contr = "select * from " . DBPref . "clients where f_id=" . $contrid;
  //echo $sql_contr."<br>";
  $conn = $dbh->query($sql_contr);
  if($row_contr = $conn->fetch(PDO::FETCH_ASSOC))
    {
    $typ1c_contr = "";
    $sql_typ1c = "select s69.f_name f_name from " . DBPref . "spr s69," . DBPref . "spr s14 where s69.f_type=69 and s14.f_type=14 and s69.f_num=s14.f_uslint and s14.f_num in (select f_valstr from " . DBPref . "categs where f_ctgtype=1 and f_objectid=" . $row_contr["f_id"] . ") ";
    $conn_typ1c = $dbh->query($sql_typ1c);
    if($row_typ1c = $conn_typ1c->fetch(PDO::FETCH_ASSOC))
      {$typ1c_contr = $row_typ1c["f_name"];}
    $mcontr = "";
    if($row_contr["f_mcontr"] > 0)
      {$mcontr = getClntInf(2, 2, $row_contr["f_mcontr"], 0, -1 * $nbd1c_contr, 2);}
    $clcntrreg = "";
    $sql_clntreg = "select f_name f_name from " . DBPref . "spr where f_type=38 and f_num=" . $row_contr["f_country"] . " ";
    $conn_clntreg = $dbh->query($sql_clntreg);
    if($row_clntreg = $conn_clntreg->fetch(PDO::FETCH_ASSOC))
      {$clcntrreg = $row_clntreg["f_name"];}
    $cnamelen = 0;
    if(isset($row_contr["f_cname"]))
      {
      if(is_string($row_contr["f_cname"]))
        {$cnamelen = strlen($row_contr["f_cname"]);}
      }
    $addnamelen = 0;
    if(isset($row_contr["f_addname"]))
      {
      if(is_string($row_contr["f_addname"]))
        {$addnamelen = strlen($row_contr["f_addname"]);}
      }
    $innlen = 0;
    if(isset($row_contr["f_inn"]))
      {
      if(is_string($row_contr["f_inn"]))
        {$innlen = strlen($row_contr["f_inn"]);}
      }
    if($innlen > 1 || ($cnamelen > 1 && $addnamelen > 1))
      {
      $retval = c1c_createClient(
                $row_contr["f_cname"],
                $row_contr["f_addname"],
                $row_contr["f_inn"],
                $typ1c_contr,
                $nbd1c_contr,
                $row_contr["f_kpp"],
                $row_contr["f_ogrn"],
                $row_contr["f_address"],
                $row_contr["f_vid"],
                $mcontr,
                $clcntrreg
                ,$row_contr["f_nalognumber"]//sdid2669
            );
      if(strcmp($retval["retval"], "true") === 0)
        {
        $kai = [
               "curtbl" => 192,
               "f_contrid" => $row_contr['f_id'],
               "f_typecode" => $nbd1c_contr,
               "f_code" => $retval["msg"]
               ];
        $ar = json_decode(addRowTbl($kai) , true);
        if(!$ar[0])
          {
          $retval = "[false,\"Ошибка сохранения кода 1С контрагента в базе<br>Экспорт в 1С контрагента был успешен<br>\"]";
          return $retval;
          }
        else
          {
          $contrid = $retval["msg"];
          $client_is_exported = "Контрагент экспортирован в 1С (".$contrid.")<br>";
          $retval = "[true,\"".$client_is_exported."\",\"".$contrid."\"]";
          return $retval;
          }
        }
      else
        {
        $retval = "[false,\"" . $retval["msg"] . "\"]";
        return $retval;
        }
      }
    else
      {
      $errmsg = "Контрагент не экспортирован:<br> ";
      if($cnamelen <= 1)
        {$errmsg = $errmsg . "Наименование контрагента - Не задано<br>";}
      if($addnamelen <= 1)
        {$errmsg = $errmsg . "Полное наименование контрагента - Не задано<br>";}
      if($innlen <= 1)
        {$errmsg = $errmsg . "ИНН - Не задан<br>";}
      $retval = "[false,\"" . $errmsg . "\"]";
      return $retval;
      }
    }
  else
    {
    $retval = "[false,\"Контрагент не найден в БД<br>\"]";
    return $retval;
    }
  }
// ~ sdid 1776

//создать/получить договор
function createDog($dogid)
  {
  try
    {
    $client_is_exported = ""; // sdid 1776
    $retval = "[false,\"Ошибка не определена\"]";
    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase.';charset=utf8', UserName, Password);
    $dbh->exec("SET CHARACTER SET utf8");
    $dbh->exec("SET NAMES 'utf8'");
    $contrid = "";
    $orgid   = "";
    $sql = "select d.f_valdog,d.f_id,d.f_dogname,d.f_dogdate,d.f_dogtype,d.f_contrid,d.f_orgid,ifnull(d.f_kod1c,'-') kod1c, ".
        "  (select f_typecode from ".DBPref."client_codes where f_typecode in (1,2) and f_contrid=d.f_orgid) bd1c ".
        "from ".DBPref."dogs d where d.f_id=".$dogid;
    //echo $sql."|";
    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      // sdid 1776
      $bd1c = intval($row['bd1c']);
      $bd1c_name = "";
      if($bd1c > 0)
        {
        $sql_bdname = "SELECT f_name FROM ".DBPref."spr WHERE f_type=110 AND f_num='".$bd1c."'";
        $conn_bdname = $dbh->query($sql_bdname);
        if($res_bdname = $conn_bdname->fetch(PDO::FETCH_ASSOC))
          {$bd1c_name = $res_bdname['f_name'];}
        }
      // ~ sdid 1776
      $orgid   = getClntInf(2,2,$row['f_orgid'],1,$row['f_orgid'],2);
      //echo $orgid."<br>";
      $contrid = getClntInf(2,2,$row['f_contrid'],0,$row['f_orgid'],2);
      //echo $contrid."<br>";
      $typ1c = "";
      if($row['f_dogtype']==1)
        {$typ1c = "100";}
      else
        {
        $sql1 = "select s70.f_num f_num from ".DBPref."spr s70,".DBPref."spr s15 where s70.f_type=70 and s15.f_type=15 and s70.f_num=s15.f_uslint and s15.f_num=".$row['f_dogtype'];
        $res1 = $dbh->query($sql1);
        $i1=0;
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {$typ1c = $row1['f_num'];}
        }
      //$retval = "[true,\"".$contrid."|".$orgid."|".$row['f_dogname']."|".$typ1c."|"."Договор № ".$row['f_dogname']." от ".date("d.m.Y",strtotime($row['f_dogdate']))."|".$row['f_dogdate']."|".$row['bd1c']."|".$row['kod1c']."\",{\"msg\":\"".$retval['msg']."\",\"msgg\":\"".$msggv."\"}]";
      if(strlen($row['f_dogname'])>0)
        {$dognm  = "Договор № ".$row['f_dogname']." от ".date("d.m.Y",strtotime($row['f_dogdate']));}
      else
        {$dognm  = "без договора";}
      $dogdt  = $row['f_dogdate'];
      if(strcmp($dogdt,"0000-00-00")==0)
        {$dogdt=null;}
//sdid 1224
      // Проверяем передаваемые параметры $contrid,$orgid,$row['kod1c'] на корректность заполнения
      $contrlen = 0;if(is_string($contrid)){$contrlen=strlen($contrid);}
      // sdid 1776
      if($contrlen === 0)
        {
        $export_result = json_decode(export_client_to_1c($dbh, $bd1c, $row['f_contrid']),true);
        if($export_result[0] === true)
          {
          $client_is_exported = $export_result[1];
          $contrid = $export_result[2];
          $contrlen = strlen($contrid);
          }
        else
          {
          $retval = "[false,\"Ошибка экспортирования в базу 1С - ".$bd1c_name."<br>".$export_result[1]."Экспортирование договора в 1С запрещено<br>\"]";
          return $retval;
          }
        }
      // ~ sdid 1776
      $orglen   = 0;if(is_string($orgid)){$orglen=strlen($orgid);}
      $kod1clen = 0;if(isset($row['kod1c'])){if(is_string($row['kod1c'])){$kod1clen=strlen($row['kod1c']);}}

      if(($contrlen>1 && $orglen>1) || ($kod1clen>1))
        {
//~sdid 1224
      $retval = c1c_createDog($contrid,$orgid,$row['f_dogname'],$typ1c,$dognm,$dogdt,$row['bd1c'],$row['kod1c'],$row['f_valdog']);
      //var_dump($retval);
      //echo "<br>";
      if($retval['retval']=="true")
        {
        $msgg = "";
        $msggv = "";
        $msgov = "";
        $msgcv = "";
        if(isset($retval['msgg']))
          {
          if(strlen($retval['msgg'])>0)
            {$msgg = ",f_kod1cg='".$retval['msgg']."' ";$msggv = $retval['msgg'];}
          if(isset($retval['msgo']))
            {if(strlen($retval['msgo'])>0)
              {$msgg = $msgg.",f_kod1cgo='".$retval['msgo']."' ";$msgov = $retval['msgo'];}}
          if(isset($retval['msgc']))
            {if(strlen($retval['msgc'])>0)
              {$msgg = $msgg.",f_kod1cgc='".$retval['msgc']."' ";$msgcv = $retval['msgc'];}}
          }
        $sql = "update ".DBPref."dogs set f_kod1c='".$retval['msg']."'".$msgg.",f_lastver1c=now(),f_dttmcr=now(),f_userid=".$_SESSION['loginid']." where f_id=".$row['f_id'];
        $dbh->exec($sql);
        //$retval = "[true,\"Договор экспортирован в 1С |".$retval['msg']."|".$msgg."\"]";
        //echo $retval['retval']."<br>";
        //echo $retval['msgg']."<br>";
        //echo $retval['msgo']."<br>";
        //echo $retval['msgc']."<br>";
        // sdid 1776 $client_is_exported
        //$retval = "[true,\"Договор экспортирован в 1С \",{\"msg\":\"".$retval['msg']."\",\"msgg\":\"".$msggv."\",\"msgo\":\"".$msgov."\",\"msgc\":\"".$msgcv."\"}]";
        $retval = "[true,\"Выгрузка в базу 1С - ".$bd1c_name."<br>".$client_is_exported."Договор экспортирован в 1С (".$retval['msg'].") \",{\"msg\":\"".$retval['msg']."\",\"msgg\":\"".$msggv."\",\"msgo\":\"".$msgov."\",\"msgc\":\"".$msgcv."\"}]";
        // ~ sdid 1776
        }
      else
        {$retval = "[false,\"".$retval['msg']."\"]";}
//sdid 1224
        }
      else
        {
          $errmsg = "Договор не экспортирован:<br> ";
          if($contrlen<=1){$errmsg = $errmsg."Код клиента 1С у контрагента - Не задан<br>";}
          if($orglen<=1)  {$errmsg = $errmsg."Код клиента 1С у организации - Не задан<br>";}
          if($kod1clen<=1){$errmsg = $errmsg."Код 1С в договоре - Не задан<br>";}
          $retval = "[false,\"".$errmsg."\"]";
        } 
//~sdid 1224
      }
    else
      {$retval = "[false,\"Договор не найден в БД\"]";}
    return $retval;
    }
  catch(Exception $e)
    {return "[false,\"Database error: ".$e->getMessage()."\"]";}
  }

//импорт банковских выписко из 1С
//function c1c_getAccHist($dtb,$dte,$orgid)
function c1c_getAccHist($dtb,$dte,$orgid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  //echo $dtb."<br>";
  //echo $dte."<br>";
  //echo $orgid."<br>";
  try
    {
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        //$retval = "-";
        //echo c1cwsdl;
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "dtb" => $dtb,
            "dte"  => $dte,
            "orgid"  => $orgid);
        //Выполняем операцию
        $result = $client->getAccHist($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        //echo $jsResult."<br>";
        //$data = json_decode($jsResult, true);
        //echo $data[0]."<br>";
        //print_r(json_decode(json_encode($jsResult)));
        try{
            $data = json_decode($jsResult,true);
            //var_dump($data);
            $col = count($data);
            //echo $col."<br>";
            if($col>0)
            {
                //echo "ddd=".$data[0]."<br>";
                //if($data[0]['retval']=="true")
                $retval=$data[0];
            }
        }
        //        catch(Exception $e){return "{\"retval\":\"false\",\"msg\":\"Ошибка разбора json: ".$e->getMessage()."\"}";} sdid 2125
        catch(Exception $e){return json_decode("{\"retval\":\"false\",\"msg\":\"Ошибка разбора json: ".$e->getMessage()."\"}",true);} // sdid 2125
        //echo $retval;
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//импорт счетов от поставщиков архива ЭДО из 1С
//sdid1687
function c1c_getSchetsEDArh($dtb,$dte,$orgid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  try
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    //$retval = "-";
    //echo c1cwsdl;
    //ini_set('default_socket_timeout', 380);
$client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "dtb" => $dtb,
            "dte"  => $dte,
            "orgid"  => $orgid);
        //Выполняем операцию
        $result = $client->getEDOArh($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        //echo $jsResult."<br>";
        //$data = json_decode($jsResult, true);
        //echo $data[0]."<br>";
        //print_r(json_decode(json_encode($jsResult)));
        $data = json_decode($jsResult,true);
        print_r($data);
        //$col = count($data);
        //echo $col."<br>";
        //if($col>0)
        //  {
        //echo "ddd=".$data[0]."<br>";
        //if($data[0]['retval']=="true")
        //  $retval=$data[0];
        //  }
        //echo $retval;
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//импорт актов поступлений от поставщиков из 1С
function c1c_getAkts($dtb,$dte,$orgid,$nbd1c,$type)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
  try
    {
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $client = new SoapClient($cwsdl,
                     array(
                         //'login' => 'vasys', //логин пользователя к базе 1С
                         //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                         //'connection_timeout' => 380,
                         'soap_version' => SOAP_1_2, //версия SOAP
                         'cache_wsdl' => WSDL_CACHE_NONE,
                         'trace' => true,
                         'features' => SOAP_USE_XSI_ARRAY_TYPE
                     )
                 );
    $ParametrXDTO= Array(
         "dtb"   => $dtb
        ,"dte"   => $dte
        ,"orgid" => $orgid
        ,"type"  => $type
        );
    //echo $cwsdl."\n";
    //var_dump($ParametrXDTO);
    //echo "\n";
    //Выполняем операцию
    $result = $client->getAkts($ParametrXDTO);
    //Обработаем возвращаемый результат
    $jsResult = str_replace("\n","",$result->return);
    $data = json_decode($jsResult,true);
    //var_dump($data);
    $col = count($data);
    if($col>0)
      {
      $retval=$data[0];
      }
    return $retval;
    }
  catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
  }

//импорт актов поступлений от поставщиков из 1С, пришедших по ЭДО
function c1c_getAktsEDO($dtb,$dte,$orgid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  try
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $client = new SoapClient($cwsdl,
        array(
            //'login' => 'vasys', //логин пользователя к базе 1С
            //'password' => '<REDACTED>', //пароль пользователя к базе 1С
            //'connection_timeout' => 380,
            'soap_version' => SOAP_1_2, //версия SOAP
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'features' => SOAP_USE_XSI_ARRAY_TYPE
        )
    );
    $ParametrXDTO= Array(
        "dtb" => $dtb,
        "dte"  => $dte,
        "orgid"  => $orgid);
    //Выполняем операцию
    $result = $client->getAktsEDO($ParametrXDTO);
    //var_dump($result);
    //Обработаем возвращаемый результат
    $jsResult = str_replace("\t","",str_replace("\n","",$result->return));
    $data = json_decode($jsResult,true);
    $col = count($data);
    if($col>0)
      {
      $retval=$data[0];
      }
    return $retval;
    }
    catch (TypeError $e)
      //      {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
      {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
  }

//импорт архива ЭДО из 1С
function c1c_getEDOArh($dtb,$dte,$orgid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
  //echo $cwsdl."\n";
  try
    {
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "dtb" => $dtb,
            "dte"  => $dte,
            "orgid"  => $orgid);
        //Выполняем операцию
        $result = $client->getEDOArh($ParametrXDTO);
        //if($nbd1c==2)
        //  {Var_dump($result);}
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        $data = json_decode($jsResult,true);
        //print_r($data);
        $col = count($data);
        //echo $col."<br>";
        if($col>0)
        {
            $retval=$data[0];
        }
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//импорт изменений документов архива ЭДО из 1С
function c1c_getEDOArhChanges($dtb,$dte,$orgid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  try
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";

    $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "dtb" => $dtb,
            "dte"  => $dte,
            "orgid"  => $orgid);
        //Выполняем операцию
        $result = $client->getEDOArhChanges($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        $data = json_decode($jsResult,true);
        //print_r($data);
        $col = count($data);
        //echo $col."<br>";
        if($col>0)
        {
            $retval=$data[0];
        }
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

//импорт файла из записи архива ЭДО из 1С
function c1c_getEDOfl($edid,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return $check[1];}
  // ~ sdid 2125
  $cwsdl = c1cwsdl;
  if($nbd1c==2)
    {$cwsdl = c1cwsdl2;}
  try
    {
    //$retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    //echo $edid."<br>";
    $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "edid" => $edid);
        //Выполняем операцию
        $result = $client->getEDOfl($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        //$jsResult = str_replace("\n","",$result->return);
        //echo $result->return."<br>";
        //$data = json_decode($jsResult,true);
        //print_r($data);
        //$col = count($data);
        //echo $col."<br>";
        //if($col>0)
        //  {
        //  $retval=$data[0];
        //  }
        return $result->return;
    }
    catch (TypeError $e)
    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";}
}
//~sdid1687

function c1c_createSpec($clntkod,$orgkod,$specnum,$specname,$specdt,$pdogcode,$dognum,$dirname,$nbd1c,$subtype,$kodc,$kodcb)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  //echo $clntkod."<br>";
  //echo $orgkod."<br>";
  //echo $specnum."<br>";
  //echo $specname."<br>";
  //echo $specdt."<br>";
  //echo $pdogcode."<br>";
  //echo $dognum."<br>";
  //echo $dirname."<br>";
  if(c1c_checkBuhDt($specdt))
    {
    try
      {
            $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
            //$client = new SoapClient(c1cwsdl,
            $cwsdl = c1cwsdl;
            if($nbd1c==2)
            {$cwsdl = c1cwsdl2;}
            $client = new SoapClient($cwsdl,
                array(
                    //'login' => "site", //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
            $ParametrXDTO= Array(
                "clntkod"  => $clntkod,
                "orgkod"   => $orgkod,
                "specnum"  => $specnum,
                "specname" => $specname,
                "specdt"   => $specdt,
                "pdogcode" => $pdogcode,
                "dognum"   => $dognum,
                "dirname"  => $dirname,
                "kodc"     => $kodc,
                "subtype"  => $subtype,
                "kodcb"    => $kodcb);
            //Выполняем операцию
            $result = $client->createSpec($ParametrXDTO);
            //Обработаем возвращаемый результат
            $jsResult = $result->return;
            $data = json_decode($jsResult, true);
            $col = count($data);
            if($col>0)
            {
                //if($data[0]['retval']=="true")
                $retval=$data[0];
            }
            return $retval;
        }
        catch (TypeError $e)
        //        {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
        {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
    else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$specdt." выгрузка запрещена\"}",true);}
}

//sdid3596
//создаать\обновить в 1С операцию, введенную вручную
function c1c_addupdManualBuhOper($kodc,$dtc,$orgkodc,$descr,$tchjs,$dsum,$nbd1c)
  {
  $check = check1CExchangeBan();

  if ($check[0]) {return json_decode($check[1],true);}
  if(c1c_checkBuhDt($dtc))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    try
      {
      $client = new SoapClient($cwsdl,
          array(
              //'login' => 'vasys', //логин пользователя к базе 1С
              //'password' => '<REDACTED>', //пароль пользователя к базе 1С
              //'connection_timeout' => 380,
              'soap_version' => SOAP_1_2, //версия SOAP
              'cache_wsdl' => WSDL_CACHE_NONE,
              'trace' => true,
              'features' => SOAP_USE_XSI_ARRAY_TYPE
          )
      );
      $ParametrXDTO = Array(
          "kodc"    => $kodc,
          "dtc"     => $dtc,
          "orgkodc" => $orgkodc,
          "descr"   => $descr,
          "tchjs"   => $tchjs,
          "dsum"    => $dsum);
      //error_log("Тест Перенос банковских расходов \nPOST1C = \n".var_export($ParametrXDTO,true)."\n\n",3,"/var/www/html/veda/logs/sdid3596.log");
      $result = $client->addupdManualBuhOper($ParametrXDTO);
      //echo "res: ".$result." <br>";
      //Var_dump($result);
      //Обработаем возвращаемый результат
      $jsResult = str_replace("\n","",$result->return);
      $data = json_decode($jsResult,true);
      $retval=$data;
      return $retval;
      }
    catch (Error $e)
      {$errreq = json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);
       return $errreq;}
    }
  else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату $dtc выгрузка запрещена\"}",true);}
  }
//~sdid3596

//добавить обновить курсовую разницу в 1С
function c1c_addupdDifRate($okodc,$drdt,$tn,$dsum,$tchjs,$drkodc,$nbd1c,$dopparm=0)//sdid3289
  {
  /*
  $dopparm - 0 - по умолчанию
             1 - делаем перенос курсовых разницы при переносе покупки валют
  */
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  if(c1c_checkBuhDt($drdt))
    {
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
        try
        {
            $client = new SoapClient($cwsdl,
                array(
                    //'login' => 'vasys', //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    //'connection_timeout' => 380,
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
            $ParametrXDTO = Array(
                "okodc"   => $okodc,
                "drdt"    => $drdt,
                "tn"      => $tn,
                "dsum"    => $dsum,
                "tchjs"   => $tchjs,
                "drkodc"  => $drkodc,
                "dopparm" => $dopparm);//sdid3289
            $result = $client->addupdDifRate($ParametrXDTO);
            //Var_dump($result);
            //Обработаем возвращаемый результат
            $jsResult = str_replace("\n","",$result->return);
            $data = json_decode($jsResult,true);
            $retval=$data;
            return $retval;
        }
        catch (Error $e)
        //        {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
        //{return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
        {$errreq=json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true); /*error_log("\n\nerrreq (lib.php str.10062): ".var_export($errreq,true)."\n\n",0);*/ return $errreq;} // sdid 2125 3168
    }
    else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$drdt." выгрузка запрещена\"}",true);}
}

//добавить/обновить Поступление в 1С
//sdid1583
function c1c_addupdAkt($act, $okod1c, $ckod1c, $dkod1c, $comm, $num, $dt, $num1c, $dt1c, $dsum, $dval, $vnds, $dstr, $vozmc, $topr, $nbd1c, $op,$c1guid,$tdoc,$fromtovarvdolg=0,$kurscbpps=1)
//~sdid1583
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  $cdo = false;
  if($act==1){$cdo=c1c_checkBuhDt($dt);}
  else{$cdo=c1c_checkBuhDt($dt1c);}
    if($cdo)
    {
        $cwsdl = c1cwsdl;
        if($nbd1c==2)
        {$cwsdl = c1cwsdl2;}
        try
        {
            $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
            $client = new SoapClient($cwsdl,
                array(
                    //'login' => 'vasys', //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    //'connection_timeout' => 380,
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
            $ParametrXDTO= Array(
                "act"    => $act,
                "okod"   => $okod1c,
                "ckod"   => $ckod1c,
                "dkod"   => $dkod1c,
                "comm"   => $comm,
                "num"    => $num,
                "dt"     => $dt,
                "numc"   => $num1c,
                "dtc"    => $dt1c,
                "dsum"   => $dsum,
                "dval"   => $dval,
                "vnds"   => $vnds,
                "dstr"   => $dstr,
                "vozmc"  => $vozmc,
                "topr"   => $topr,
                "op"     => $op,
                "c1guid" => $c1guid,
                "type"   => $tdoc
                //sdid1583
                ,"doppr"   => $fromtovarvdolg
                ,"valcurs" => $kurscbpps
                //~sdid1583
                );
            //if($_SESSION['loginid']==2)
            //  {echo "inapr:<br>";var_dump($ParametrXDTO);echo "<br>";}
            //echo "act=$act<br>okod=$okod1c<br>ckod=$ckod1c<br>dkod=$dkod1c<br>comm=$comm<br>num=$num<br>dt=$dt<br>numc=$num1c<br>dtc=$dt1c<br>dsum=$dsum<br>dval=$dval<br>vnds=$vnds<br>dstr=$dstr<br>vozmc=$vozmc<br>topr=$topr<br>op=$op<br>c1guid=$c1guid<br>type=$tdoc<br>doppr=$fromtovarvdolg<br>valcurs=$kurscbpps<br>";
            $result = $client->addupdAkt($ParametrXDTO);
            //if($_SESSION['loginid']==2)
            //  {echo "outres:<br>";Var_dump($result);}
            //Var_dump($result);
            //Обработаем возвращаемый результат
            $jsResult = str_replace("\n","",$result->return);
            $data = json_decode($jsResult,true);
            $retval=$data;
            return $retval;
        }
        catch (Error $e)
        {if($_SESSION['loginid']==2){echo $e->getMessage();}
         //         return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
         return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
    else
    {
        $rv = json_decode("{\"retval\":\"false\",\"msg\":\"За данную дату выгрузка запрещена\"}",true);
        return $rv;
    }
}

//помечаем на удаление Поступление в 1С
function c1c_delAkt($dt1c, $num1c, $okod1c, $ckod1c, $c1guid, $type, $nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  if(c1c_checkBuhDt($dt1c))
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    try
      {
      $client = new SoapClient($cwsdl,
                array(
                    //'login' => 'vasys', //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    //'connection_timeout' => 380,
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
      $ParametrXDTO= Array(
          "dtc"    => $dt1c,
          "numc"   => $num1c,
          "okod"   => $okod1c,
          "ckod"   => $ckod1c,
          "c1guid" => $c1guid,
          "type"   => $type
          );
      $result = $client->delAkt($ParametrXDTO);
      //Var_dump($result);
      //Обработаем возвращаемый результат
      $jsResult = str_replace("\n","",$result->return);
      $data = json_decode($jsResult,true);
      $retval=$data;
      return $retval;
      }
    catch (Error $e)
      //      {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
      {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
  else
    //    {return "{\"retval\":\"false\",\"msg\":\"За дату ".$dt1c." удаление запрещено\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$dt1c." удаление запрещено\"}",true);} // sdid 2125
  }

//установить признак проверки логистами на ЭДО в 1С
//sdid1687
function c1c_setEDOLogist($edid,$islog,$nbd1c)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  //echo $edid."<br>";
  $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
  try
    {
    $cwsdl = c1cwsdl;
    if($nbd1c==2)
      {$cwsdl = c1cwsdl2;}
    $client = new SoapClient($cwsdl,
            array(
                //'login' => 'vasys', //логин пользователя к базе 1С
                //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                //'connection_timeout' => 380,
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        //echo $retval;
        $ParametrXDTO= Array(
            "edid" => $edid,
            "islog" => $islog);
        //Выполняем операцию
        //echo $edid."<br>";
        //echo $islog."<br>";
        $result = $client->setEDOLogist($ParametrXDTO);
        //Var_dump($result);
        //Обработаем возвращаемый результат
        $jsResult = str_replace("\n","",$result->return);
        $data = json_decode($jsResult,true);
        $col = count($data);
        //echo $col."<br>";
        //echo $data[0]['retval']."<br>";
        if($col>0)
        {
            $retval=$data[0];
        }
        return $retval;
    }
    catch (TypeError $e)
    //    {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
}

function setEDOLogist($edid,$islog,$fid,$nbd1c)
  {
  $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С (setEDOLogist)\"}";
  //echo $edid."<br>";
  $rrtv = c1c_setEDOLogist($edid,$islog,$nbd1c);
  //echo $rrtv['retval']."<br>";
  //$rrtv = json_decode($rrtv,true);
  //echo $rrtv['retval']."<br>";
  //echo $rrtv['msg']."<br>";
  if($rrtv['retval']=="true")
    {
    $retval = "{\"retval\":\"true\",\"msg\":\"ЭДО обновлен\",\"nv\":\"0\"}";
    $ki = Array('curtbl'=>181,'curidx'=>$fid,'f_checklogist'=>$islog);
    $ar = editRowTbl($ki);
    //$ar = json_decode($ar,true);
    //echo $ar."<br>";
    if($ar[0]=="true")
      {$retval = "{\"retval\":\"true\",\"msg\":\"ЭДО обновлен\",\"nv\":\"1\"}";}
    }
  return $retval;
  }
//~sdid1687

//обновить запись банковской выписки в 1С
function c1c_updAccHist($dsum,$dval,$dtype,$dirtype,$orgidc,$orgaccidc,$kodc,$dtc,$tops,$ntops,$contridc,$cursoper,$izvozm,$tchjs,$ahtype,$nbd1c,$op,$valnom)
  {
  // sdid 2125
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  // ~ sdid 2125
  if(c1c_checkBuhDt($dtc))
    {
        //echo $dtc."<br>";
        try
        {
            $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
            $cwsdl = c1cwsdl;
            if($nbd1c==2)
            {$cwsdl = c1cwsdl2;}
            $client = new SoapClient($cwsdl,
                array(
                    //'login' => 'vasys', //логин пользователя к базе 1С
                    //'password' => '<REDACTED>', //пароль пользователя к базе 1С
                    //'connection_timeout' => 380,
                    'soap_version' => SOAP_1_2, //версия SOAP
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'trace' => true,
                    'features' => SOAP_USE_XSI_ARRAY_TYPE
                )
            );
            $ParametrXDTO= Array(
                "dsum"      => $dsum,
                "dval"      => $dval,
                "dtype"     => $dtype,
                "dirtype"   => $dirtype,
                "orgidc"    => $orgidc,
                "orgaccidc" => $orgaccidc,
                "kodc"      => $kodc,
                "dtc"       => $dtc,
                "tops"      => $tops,
                "ntops"     => $ntops,
                "contridc"  => $contridc,
                "cursoper"  => $cursoper,
                "izvozm"    => $izvozm,
                "tchjs"     => $tchjs,
                "ahtype"    => $ahtype,
                "op"        => $op,
                "valnom"    => $valnom
            );
            //Var_dump($ParametrXDTO);
            //echo $dsum."|".$dval."|".$dtype."|".$dirtype."|".$orgidc."|".$orgaccidc."|".$kodc."|".$dtc."|".$tops."|".$ntops."|".$contridc."|".
            //     $cursoper."|".$izvozm."|".$tchjs."|".$ahtype."|".$nbd1c."<br>";
            $result = $client->updAccHist($ParametrXDTO);
            //Var_dump($result);
            //Обработаем возвращаемый результат
            $jsResult = str_replace("\n","",$result->return);
            $data     = json_decode($jsResult,true);
            $retval   = $data;
            return $retval;
        }
        catch (Exception $e)
        //        {return "{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}";} sdid 2125
        {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
    }
    else
    {return json_decode("{\"retval\":\"false\",\"msg\":\"За дату ".$dtc." выгрузка запрещена\"}",true);}
}

//sdid2253
//получить список актов сверки из 1С
function c1c_getcoacsu($dtb, $dte, $org, $clnt, $dog="", $nbd1c=1)
  {
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  try
    {
    //echo "$dtb|$dte|$org|$clnt|$dog|$nbd1c<br>";
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $cwsdl = c1cwsdl;
    if($nbd1c==2){$cwsdl = c1cwsdl2;}
    $client = new SoapClient($cwsdl,
        array(
            //'login' => 'vasys', //логин пользователя к базе 1С
            //'password' => '<REDACTED>', //пароль пользователя к базе 1С
            //'connection_timeout' => 380,
            'soap_version' => SOAP_1_2, //версия SOAP
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
    $ParametrXDTO= Array(
        "dtb"    => $dtb,
        "dte"    => $dte,
        "org"    => $org,
        "clnt"   => $clnt,
        "dog"    => $dog
        );
    //Var_dump($ParametrXDTO);
    //echo $dsum."|".$dval."|".$dtype."|".$dirtype."|".$orgidc."|".$orgaccidc."|".$kodc."|".$dtc."|".$tops."|".$ntops."|".$contridc."|".
    //     $cursoper."|".$izvozm."|".$tchjs."|".$ahtype."|".$nbd1c."<br>";
    $result = $client->getcoacsu($ParametrXDTO);
    //Var_dump($result);
    //Обработаем возвращаемый результат
    $jsResult = str_replace("\n","",$result->return);
    //echo $jsResult."<br>";
    $data     = json_decode($jsResult,true);
    $retval   = $data;
    return $retval;
    }
  catch (Exception $e)
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
  }

//получить информацию о акте сверки из 1С
function c1c_getcoacsuinfo1C($c1guid)
  {
  $check = check1CExchangeBan();
  if ($check[0]) {return json_decode($check[1],true);}
  try
    {
    //echo "$dtb|$dte|$org|$clnt|$dog|$nbd1c<br>";
    $retval = "{\"retval\":\"false\",\"msg\":\"Ошибка при выполненении запроса к 1С\"}";
    $cwsdl = c1cwsdl;
    if($nbd1c==2){$cwsdl = c1cwsdl2;}
    $client = new SoapClient($cwsdl,
        array(
            //'login' => 'vasys', //логин пользователя к базе 1С
            //'password' => '<REDACTED>', //пароль пользователя к базе 1С
            //'connection_timeout' => 380,
            'soap_version' => SOAP_1_2, //версия SOAP
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
            'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
    $ParametrXDTO= Array(
        "c1guid"    => $c1guid
        );
    //Var_dump($ParametrXDTO);
    //echo $dsum."|".$dval."|".$dtype."|".$dirtype."|".$orgidc."|".$orgaccidc."|".$kodc."|".$dtc."|".$tops."|".$ntops."|".$contridc."|".
    //     $cursoper."|".$izvozm."|".$tchjs."|".$ahtype."|".$nbd1c."<br>";
    $result = $client->getcoacsuinfo($ParametrXDTO);
    //Var_dump($result);
    //Обработаем возвращаемый результат
    $jsResult = str_replace("\n","",$result->return);
    //echo $jsResult."<br>";
    //$data     = json_decode($jsResult,true);
    $retval   = $jsResult;
    return $retval;
    }
  catch (Exception $e)
    {return json_decode("{\"retval\":\"false\",\"msg\":\"".$e->getMessage()."\"}",true);} // sdid 2125
  }
//sdid2253

function ShowLoginPage()
{
    //echo site_name."<br>";
    //echo client_id."<br>";
    $gpar = array(
        'redirect_uri'  => site_name,
        'response_type' => 'code',
        'client_id'     => client_id,
        'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile'
    );
    print("
<!DOCTYPE html>
<html lang=\"ru\">
    <head>
	<link rel=\"shortcut icon\" href=\"favicon.ico\"/>
        <link rel=\"icon\" href=\"favicon.ico\" />
	<meta charset=\"".scharset."\" />
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".scharset."\">
        <title>".stitle."</title>
        <link rel=\"shortcut icon\" href=\"img/logo.png\"> 
        <link rel=\"stylesheet\" type=\"text/css\" href=\"css/style.css\" />
        <link rel=\"stylesheet\" href=\"plugins/fontawesome-free/css/all.min.css\"/>
<style type=\"text/css\">
.button__1qmKY {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  border: solid 1px #E1E4E8;
  border-radius: 6px;
  color: #24292E;
  padding: 7px 0;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
}
.button__1qmKY:before {
  display: inline;
  content: ' ';
  background: url(\"data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%3E%0A%20%20%20%20%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%0A%20%20%20%20%20%20%20%20%3Cpath%20d%3D%22M0%200h24v24H0z%22%2F%3E%0A%20%20%20%20%20%20%20%20%3Cg%20fill-rule%3D%22nonzero%22%3E%0A%20%20%20%20%20%20%20%20%20%20%20%20%3Cpath%20fill%3D%22%23FBBB00%22%20d%3D%22M8.938%2013.047l-.383%201.43-1.4.03A5.475%205.475%200%200%201%206.5%2011.9c0-.912.222-1.772.615-2.53l1.246.229.546%201.238a3.27%203.27%200%200%200%20.03%202.21z%22%2F%3E%0A%20%20%20%20%20%20%20%20%20%20%20%20%3Cpath%20fill%3D%22%23518EF8%22%20d%3D%22M17.404%2010.873a5.506%205.506%200%200%201-1.96%205.316h-.001l-1.57-.08-.221-1.387a3.278%203.278%200%200%200%201.41-1.674H12.12v-2.175H17.404z%22%2F%3E%0A%20%20%20%20%20%20%20%20%20%20%20%20%3Cpath%20fill%3D%22%2328B446%22%20d%3D%22M15.443%2016.189a5.5%205.5%200%200%201-8.288-1.683l1.783-1.459a3.27%203.27%200%200%200%204.714%201.675l1.79%201.467z%22%2F%3E%0A%20%20%20%20%20%20%20%20%20%20%20%20%3Cpath%20fill%3D%22%23F14336%22%20d%3D%22M15.51%207.666L13.73%209.125a3.271%203.271%200%200%200-4.822%201.713L7.115%209.37a5.499%205.499%200%200%201%208.396-1.705z%22%2F%3E%0A%20%20%20%20%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3C%2Fg%3E%0A%3C%2Fsvg%3E%0A\") no-repeat left center;
  background-size: 44px;
  width: 32px;
  height: 32px;
  margin-right: 10px;
}
.button__1qmKY:hover {
  border-color: #C6CBD1;
}
</style>
    </head>
    <body>
        <div class=\"container\">
                        <header>
                                <img src=\"img/logo.png\"/><br>
                        <header>		
			<section class=\"main\">
				<form class=\"form-1\" action=enter.php method=post >
				
					<p class=\"field\">
						<input type=\"text\" id=\"login\" name=\"login\" placeholder=\"E-mail\">
						<i class=\"fa fa-lg fa-user\"></i>
					</p>
						<p class=\"field\">
							<input type=\"password\" id==\"password\" name=\"password\" placeholder=\"Password\">
							<i class=\"fa fa-lg fa-lock\"></i>
					</p>
          <input type=\"hidden\" name=\"current_url\" value=\"\" /> <!-- sdid 2493 -->
					<p class=\"submit\">
						<button type=\"submit\" name=\"submit\" ACTION=\"enter.php\"><i class=\"fa fa-lg fa-arrow-right\"></i></button>
					</p>
				<!--<br>
                                <p class=\"field\">
                                <a href='https://accounts.google.com/o/oauth2/auth?" . urldecode(http_build_query($gpar)) . "'><div class=\"button__1qmKY\">Войти через Google</div></a>
                                </p>-->
				</form>
			</section>

        </div>
    </body>
    <!-- sdid 2493 -->
    <script type='text/javascript'>
        document.querySelector(\"input[name='current_url']\").value = window.location.href;
    </script>
    <!-- ~ sdid 2493 -->
</html>
        ");
}

function check_usrlogin($foradm,$onlyuser)
  {
  $accessonly2 = 0;//запрет доступа всем кроме 2 логина
  $gc = 0;
  if(isset($_GET['code']))
    {
    $result = false;
    $params = array(
        'client_id'     => client_id,
        'client_secret' => client_secret,
        'redirect_uri'  => site_name,
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code']
    );
    $url = 'https://accounts.google.com/o/oauth2/token';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($curl);
    curl_close($curl);
    $tokenInfo = json_decode($result, true);
    if(isset($tokenInfo['access_token']))
      {
      $params['access_token'] = $tokenInfo['access_token'];
      $userInfo = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo' . '?' . urldecode(http_build_query($params))), true);
      if (isset($userInfo['id'])) 
        {
        $userInfo = $userInfo;
        $result = true;
        }
      }
    if($result)
      {
      //echo "Социальный ID пользователя: " . $userInfo['id'] . '<br />';
      //echo "Имя пользователя: " . $userInfo['name'] . '<br />';
      //echo "Email: " . $userInfo['email'] . '<br />';
      //echo "Ссылка на профиль пользователя: " . $userInfo['link'] . '<br />';
      //echo "Пол пользователя: " . $userInfo['gender'] . '<br />';
      //echo '<img src="' . $userInfo['picture'] . '" />'; echo "<br />";
      $_SESSION['login'] = $userInfo['email'];
      $_SESSION['uname'] = $userInfo['name'];
      $gc = 1;
      }
    }
  if(!isset($_SESSION['login']))
    {
    return 0;
    }
  else
    {
    if($accessonly2==1)
      {
      if(strcmp($_SESSION['login'],"brexwid@gmail.com")!=0)
        {
        echo "<font color='red'>Вход временно запрещен</font>";
        return 0;
        }
      }
    try
      {
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      //$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $dbh->exec('SET CHARACTER SET utf8');
      $sql = "SELECT u.f_id,u.f_login,u.f_password,u.f_authtype,u.f_name1,u.f_name2,u.f_alpsort,u.f_persmenu,u.f_struct_code,
                u.f_dialogtemplate,
                ifnull((select f_name from ".DBPref."spr where f_type=164 and f_num=u.f_dialogcolors),'') dialogcolors,
                ifnull((select f_namedop from ".DBPref."spr where f_type=164 and f_num=u.f_dialogcolors),'') dialogcolorscode,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='baseclass' and f_uslint=u.f_dialogtemplate),'') basestyleclass,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='basebtn' and f_uslint=u.f_dialogtemplate),'') basestylebtn,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='basefooter' and f_uslint=u.f_dialogtemplate),'') basestylefooter,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='basecard' and f_uslint=u.f_dialogtemplate),'') basestylecard,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='baseheader' and f_uslint=u.f_dialogtemplate),'') basestyleheader,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='basetitle' and f_uslint=u.f_dialogtemplate),'') basestyletitle,
                ifnull((select f_namedop from ".DBPref."spr where f_type=165 and f_name='baseselect' and f_uslint=u.f_dialogtemplate),'') basestyleselect
              FROM ".DBPref."users u 
              where u.f_login='".$_SESSION['login']."' and u.f_isactived=1";
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      $row = $res->fetch();
      if($row)
        {
        if($row['f_authtype']==3)//google/локальная
          {
          if($gc==1)//если вошел через гугл
            {
            $_SESSION['login']       = $userInfo['email'];
            $_SESSION['uname']       = $userInfo['name'];
            $_SESSION['loginid']     = $row['f_id'];
            $_SESSION['alpsort']     = $row['f_alpsort'];
            $_SESSION['persmenu']    = $row['f_persmenu'];
            $_SESSION['struct_code'] = $row['f_struct_code'];
            $dbh->exec("insert into ".DBPref."usr_logins (f_usrid,f_toperid,f_dttm,f_operef) values (".$_SESSION['loginid'].",1,NOW(),0)");
            return 1;
            }
          elseif($row['f_password']==$_SESSION['pass'])
            {
            if($foradm==1 and $row['f_isadmin']==0)
              {return 0;}
            $_SESSION['login']       = $row['f_login'];
            $_SESSION['uname']       = $row['f_name1']." ".$row['f_name2'];
            $_SESSION['loginid']     = $row['f_id'];
            $_SESSION['alpsort']     = $row['f_alpsort'];
            $_SESSION['persmenu']    = $row['f_persmenu'];
            $_SESSION['struct_code'] = $row['f_struct_code'];
            $dbh->exec("insert into ".DBPref."usr_logins (f_usrid,f_toperid,f_dttm,f_operef) values (".$_SESSION['loginid'].",1,NOW(),0)");
            return 1;
            }
          else
            {return 0;}
          }
        elseif($row['f_authtype']==0)//пароль лежит в явном виде в СУБД
          {
          if($row['f_password']==$_SESSION['pass'])
            {
            if($foradm==1 and $row['f_isadmin']==0)
              {return 0;}
            $_SESSION['login']            = $row['f_login'];
            $_SESSION['uname']            = $row['f_name1']." ".$row['f_name2'];
            $_SESSION['loginid']          = $row['f_id'];
            $_SESSION['alpsort']          = $row['f_alpsort'];
            $_SESSION['persmenu']         = $row['f_persmenu'];
            $_SESSION['struct_code']      = $row['f_struct_code'];
            $_SESSION['dialogtemplate']   = $row['f_dialogtemplate'];//шаблон диалогов
            $_SESSION['dialogcolors']     = $row['dialogcolors'];    //цвет диалогов
            $_SESSION['dialogcolorscode'] = $row['dialogcolorscode'];    //цвет диалогов
            $_SESSION['basestyleclass']   = $row['basestyleclass'];  //основной класс диалога
            $_SESSION['basestylebtn']     = $row['basestylebtn'];
            $_SESSION['basestylebtnclr']  = $_SESSION['basestylebtn'];
            $_SESSION['basestylebtndef']  = $_SESSION['basestylebtn'];
            $_SESSION['basestyleheader']  = $row['basestyleheader'];
            $_SESSION['basestylefooter']  = $row['basestylefooter'];
            $_SESSION['basestyletitle']   = $row['basestyletitle'];
            $_SESSION['basestyleselect']  = $row['basestyleselect'];
            $_SESSION['basestylecard']    = $row['basestylecard'];
            $_SESSION['basestylecardclr'] = $_SESSION['basestylecard'];
            if($_SESSION['dialogtemplate']==1)
              {
              if(strlen($_SESSION['dialogcolors'])>0)
                {
                $_SESSION['basestylebtnclr']  = $_SESSION['basestylebtnclr']." ".$_SESSION['basestylebtnclr']."-".$_SESSION['dialogcolors'];
                $_SESSION['basestylebtndef']  = $_SESSION['basestylebtndef']." ".$_SESSION['basestylebtndef']."-default";
                $_SESSION['basestylecardclr'] = $_SESSION['basestylecardclr']." ".$_SESSION['basestylecardclr']."-".$_SESSION['dialogcolors'];
                }              
              }
            $dbh->exec("insert into ".DBPref."usr_logins (f_usrid,f_toperid,f_dttm,f_operef) values (".$_SESSION['loginid'].",1,NOW(),0)");
            //echo $_SESSION['struct_code']."<br>";
            return 1;
            }
          else
            {
            return 0;
            }
          }
        elseif($row['f_authtype']==1)//LDAP-аутентификации
          {
          $ldap_url="";
          $query  = "select f_value_str from t_settings where f_type=3 and f_subtype=0";
          //$result = mysql_db_query(DataBase,$query);
          $result = mysql_query($query,$link);
          if($result)
            {
            if(mysql_numrows($result)>0)
              {$row=mysql_fetch_array($result);
              $ldap_url = $row['f_value_str'];}
            $query  = "select f_value_str from t_settings where f_type=3 and f_subtype=1";
            //$result = mysql_db_query(DataBase,$query);
            $result = mysql_query($query,$link);
            if($result)
              {
              if(mysql_numrows($result)>0)
                {$row=mysql_fetch_array($result);
                $ldap_domain = $row['f_value_str'];}
              }
            $query  = "select f_value_str from t_settings where f_type=3 and f_subtype=2";
            $result = mysql_query($query,$link);
            if($result)
              {
              if(mysql_numrows($result)>0)
                {$row=mysql_fetch_array($result);
                $ldap_dn = $row['f_value_str'];}
              if((strlen($ldap_url)>0) && (strlen($ldap_domain)>0) && (strlen($ldap_dn)>0))
                {$ds = ldap_connect( $ldap_url );
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                $username = $_SESSION['login'];
                $password = $_SESSION['pass'];
                if(ldap_bind( $ds, "$username@$ldap_domain", $password ))
                  {return 1;}
                else
                  {return 0;}
                }
              else
                {return 0;}
              }
            }
          }
        elseif($row['f_authtype']==2)//GOOGLE-аутентификации
          {
          //echo "gsocid<br>";
          if (isset($_GET['code']))
            {
            //$result = false;
            //$params = array(
            //    'client_id'     => client_id,
            //    'client_secret' => client_secret,
            //    'redirect_uri'  => site_name,
            //    'grant_type'    => 'authorization_code',
            //    'code'          => $_GET['code']
            //);
            //$url = 'https://accounts.google.com/o/oauth2/token';
            //$curl = curl_init();
            //curl_setopt($curl, CURLOPT_URL, $url);
            //curl_setopt($curl, CURLOPT_POST, 1);
            //curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
            //curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //$result = curl_exec($curl);
            //curl_close($curl);
            //$tokenInfo = json_decode($result, true);
            //if(isset($tokenInfo['access_token']))
            //  {
            //    $params['access_token'] = $tokenInfo['access_token'];
            //    $userInfo = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo' . '?' . urldecode(http_build_query($params))), true);
            //    if (isset($userInfo['id'])) {
            //        $userInfo = $userInfo;
            //        $result = true;
            //    }
            //  }
            //
            //if($result)
                        if($gc==1)
                        {
                            //echo "Социальный ID пользователя: " . $userInfo['id'] . '<br />';
                            //echo "Имя пользователя: " . $userInfo['name'] . '<br />';
                            //echo "Email: " . $userInfo['email'] . '<br />';
                            //echo "Ссылка на профиль пользователя: " . $userInfo['link'] . '<br />';
                            //echo "Пол пользователя: " . $userInfo['gender'] . '<br />';
                            //echo '<img src="' . $userInfo['picture'] . '" />'; echo "<br />";
                            $_SESSION['login']   = $userInfo['email'];
                            $_SESSION['uname']   = $userInfo['name'];
                            $_SESSION['loginid'] = $row['f_id'];
                            $dbh->exec("insert into ".DBPref."usr_logins (f_usrid,f_toperid,f_dttm,f_operef) values (".$_SESSION['loginid'].",1,NOW(),0)");
                            return 1;
                        }
                        else
                            return 0;
                    }
                    else
                        return 0;
                }
            }
            else
            {
                return 0;
            }
        }
        catch (PDOException $e)
        {
            echo 'PDODatabase error(check_usrlogin): '.$e->getMessage();
            return 0;
        }
    }
}

function UsrMenu2l()//меню пользователя в 2 уровня
  {
  $retstr="";
  $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
  $dbh->exec('SET CHARACTER SET utf8');
  //$sql  = "SELECT m.f_id f_id,m.f_name f_name,m.f_href f_href,m.f_target f_target,m.f_menucod f_menucod,m.f_used _used,m.f_img f_img,m.f_size f_size,m.f_parentid f_parentid,m.f_level f_level,m.f_func f_func from ".DBPref."menu_all m where m.f_parentid=0 and m.f_level=0 and m.f_used=1 and m.f_id in (select f_menu_id from ".DBPref."menu_user where f_user_id=(select f_id from ".DBPref."users where f_login='".$_SESSION['login']."')) order by f_menucod";
  //$sql  = "SELECT m.f_id f_id,m.f_name f_name,m.f_img f_img from ".DBPref."menu_all m where m.f_parentid=0 and m.f_level=0 and m.f_used=1 and m.f_id in (select f_menu_id from ".DBPref."menu_user where f_user_id=(select f_id from ".DBPref."users where f_login='".$_SESSION['login']."')) order by m.f_menucod";
  $usrmenuord = "m.f_menucod";
  $persmenu   = "brexwid@gmail.com";
  $usertype   = $_SESSION['persmenu'];
  {if($_SESSION['persmenu']==1){$persmenu = $_SESSION['login'];}}
  //sdid - 172
  $text_color     = "#ffffff";
  $color          = "#c2c7d0";
  $is_with_border = "";
  $sql_query      = "
      SELECT m.* 
      FROM (SELECT f_valstr valstr, ctg.f_ctgtype, ctg.f_id, f_namedop 
            FROM ".DBPref."categs ctg, ".DBPref."users users, ".DBPref."spr spr
            WHERE ctg.f_ctgtype=18 AND ctg.f_objectid=users.f_id AND users.f_login='".$_SESSION['login']."' AND spr.f_type=142 AND 
              f_valstr=spr.f_num ORDER BY ctg.f_id DESC LIMIT 1) m
      UNION
      SELECT k.* 
      FROM (SELECT f_valstr valstr, ctg.f_ctgtype, ctg.f_id, '' f_namedop 
            FROM ".DBPref."categs ctg, ".DBPref."users users
            WHERE ctg.f_ctgtype=19 AND ctg.f_objectid=users.f_id AND users.f_login='".$_SESSION['login']."' ORDER BY ctg.f_id DESC LIMIT 1) k";
  $response  = $dbh->query($sql_query);
  while($row = $response->fetch(PDO::FETCH_ASSOC))
    {
    if($row['f_ctgtype'] == 18)
      {
      $color      = $row['f_namedop'];
      $text_color = $row['valstr'] == 0 ? "#ffffff" : $color;
      }
    elseif($row['f_ctgtype'] == 19)
      {
      $is_with_border = $row['valstr'];
      }
    }

  $border_parameters = $is_with_border ? "border: 1px solid $color;" : "";

  $retstr=$retstr."<style>
  [class*='sidebar-dark-'] .nav-sidebar > .nav-item.menu-open > .nav-link,
  [class*='sidebar-dark-'] .nav-sidebar > .nav-item:hover > .nav-link,
  [class*='sidebar-dark-'] .nav-sidebar > .nav-item > .nav-link:focus {
    background-color: rgba(255, 255, 255, 0.1);
    color: $text_color;
    $border_parameters
  }
  </style>";
  //~sdid - 172
    //$usertype = 0;
    //echo "usertype=".$usertype;
    if(($usertype==0)||($usertype==1))
      {
      $sql = "SELECT m.f_id f_id,m.f_name f_name,m.f_img f_img 
              from ".DBPref."menu_all m,".DBPref."menu_user mu,".DBPref."users u  
              where m.f_id=mu.f_menu_id and mu.f_user_id=u.f_id and mu.f_usertype=".$usertype." and m.f_parentid=0 and m.f_level=0 and m.f_used=1 and 
                u.f_login='".$persmenu."' 
              order by ".$usrmenuord;
      }
    elseif($usertype==2)
      {
      $sql = "SELECT m.f_id f_id,m.f_name f_name,m.f_img f_img 
              from ".DBPref."menu_all m,".DBPref."menu_user mu
              where m.f_id=mu.f_menu_id and mu.f_usertype=".$usertype." and m.f_parentid=0 and m.f_level=0 and m.f_used=1 and 
                mu.f_user_id=".$_SESSION['struct_code']."
              order by ".$usrmenuord;
      }
    // in (select f_menu_id from ".DBPref."menu_user where f_user_id=(select f_id from ".DBPref."users where f_login='".$persmenu."')) 
    //$retstr=$retstr."<br>".$sql;
    //echo $sql."<br>";
      //echo $_SESSION['loginid']."|";
      //echo $sql."<br>";
    //echo "UsrMenu2l";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $retstr=$retstr."
        <li class=\"nav-item has-treeview\">
          <a href=\"#\" class=\"nav-link\">
            <i class=\"nav-icon fas ".$row['f_img']."\"></i>
            <p>
              ".$row['f_name']."
              <i class=\"fas fa-angle-left right\"></i>
            </p>
          </a>
          <ul class=\"nav nav-treeview\">";
      //$sql = "select m.* from ".DBPref."menu_all m where m.f_parentid=".$row['f_id']." and m.f_used=1 and m.f_id in (select f_menu_id from ".DBPref."menu_user where f_user_id=(select f_id from ".DBPref."users where f_login='".$_SESSION['login']."')) order by m.f_menucod";
      if(isset($_SESSION['alpsort']))
        {if($_SESSION['alpsort']==1){$usrmenuord = "m.f_name";}}
      if(($usertype==0)||($usertype==1))
        {
        $sql = "select m.* from ".DBPref."menu_all m 
                where m.f_parentid=".$row['f_id']." and m.f_used=1 and 
                  m.f_id in (select f_menu_id from ".DBPref."menu_user where f_usertype=".$usertype." and f_user_id=(select f_id from ".DBPref."users where f_login='".$persmenu."')) 
                order by ".$usrmenuord;
        }
      elseif($usertype==2)
        {
        $sql = "select m.* from ".DBPref."menu_all m,".DBPref."menu_user mu 
                where m.f_parentid=".$row['f_id']." and m.f_used=1 and 
                  m.f_id=mu.f_menu_id and mu.f_usertype=".$usertype." and mu.f_user_id=".$_SESSION['struct_code']."  
                order by ".$usrmenuord;
        }
      //if($_SESSION['loginid']==2)
      //  {echo $sql."<br>";}
      //echo $_SESSION['loginid']."|";
      //echo $sql."<br>";
      //$retstr=$retstr."<br>".$sql;

        $res1 = $dbh->query($sql);
        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {
          $madd = 1;
          if($row1['f_checkrights']==1)
            {
            $madd=checkRightsTblForUsr($_SESSION['loginid'],$row1['f_id'],4);
            }
          if($madd==1)
            {
            //sdid VA-665
            //$tableId = $_GET['invtb'] ?? $_GET['pgid'];
	    if(isset($_GET['invtb'])) 
              {$tableId = $_GET['invtb'];}
            elseif(isset($_GET['pgid'])) 
              {$tableId = $_GET['pgid'];} 
            $activeClass = isset($tableId) && $tableId == $row1['f_id'] ? ' active' : '';
            $retstr=$retstr."
            <li class=\"nav-item\">
              <a href='javascript:void(0)' data-table-id='". $row1['f_id']."' onclick='".$row1['f_func']."' class='nav-link$activeClass'>
                <i class=\"fas fa-genderless nav-icon\"></i>
                <p>".$row1['f_name']."</p>
              </a>
            </li>";
            //~sdid VA-665
            }
          }

        $retstr=$retstr."
            </ul>
          </li>";
    }
    return $retstr;
}

//создать документ по файлу ЭДО
function parseEDOflandCreateDoc($doccnt,$edtypeid,$fdata,$edoid,$orgid,$contrid)
  {
  if($doccnt==0)//если не вставлялось никаких документов
    {
    $ks = 0;
    $kd = 0;
    if(strcmp($edtypeid,"Счет на оплату")==0)//будем парсить счет на оплату
      {
      //echo $edtypeid."<br>";
      $strxml = base64_decode($fdata);
      //echo $strxml."<br>";
      $parser = simplexml_load_string($strxml);
      //echo $parser->${'Файл'}->Attribute('Имя');
      //echo isset($parser[0]->Attribute('Имя'));
      //if(isset($parser->${'Файл'}->Attribute('Имя')))
      //print_r($parser->attributes());
      //echo "<br>".$parser->attributes()->{'ИдФайл'}."<br>";
      $isSCHET = 0;
      if(isset($parser['Имя']))
        {
        //echo "1<br>";
        if(strcmp(substr($parser['Имя'],0,8),"ON_SCHET")==0)
          {$isSCHET = 1;}
        }
      elseif(isset($parser->{'Документ'}))
        {
        //echo "2<br>";
        if(isset($parser->{'Документ'}->{'ХозОперация'}))
          {
          //echo "2.1<br>";
          if(strcmp($parser->{'Документ'}->{'ХозОперация'},"Счет на оплату")==0)
            {$isSCHET = 1;}
          }
        elseif(strcmp(substr($parser->attributes()->{'ИдФайл'},0,8),"ON_SCHET")==0)
          {$isSCHET = 1;}
        }
      elseif(isset($parser->attributes()->{'ИдФайл'}))
        {
        //echo "3<br>";
        //echo "{'ИдФайл'}:$parser->attributes()->{'ИдФайл'}<br>";
        if(strcmp(substr($parser->attributes()->{'ИдФайл'},0,8),"ON_SCHET")==0)//Разбираем счет
          {$isSCHET = 1;}
        }
      //echo "isSCHET:$isSCHET<br>";
      if($isSCHET==1)
        {
        $isparse   = 0;
        $cbaccid   = 0;
        $dogid     = 0;
        $dogtype   = 4;
        $schetcom  = "";
        $schetdt   = "0000-00-00";
        $schetnum  = "";
        $schetval  = 643;
        $dogdt     = "0000-00-00";
        $dognum    = "";
        $bnkbic    = "";
        $recbnkacc = "";
        $itogkol   = 0;
        $itogsum   = 0;
        $itogndss  = 0;
        $schetid   = 0;
        $mdet      = Array();
        $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
        if((isset($parser->{'Документ'}))&&(!isset($parser->{'Документ'}->{'ХозОперация'}))&&(!isset($parser->attributes()->{'ИдФайл'})))
          {
          //echo "1<br>";
          foreach($parser->{'Документ'}->attributes() as $a => $b)
            {
            if(strcmp($a,"Дата")==0){$schetdt = $b;$schetdt = substr($schetdt,6,4)."-".substr($schetdt,3,2)."-".substr($schetdt,0,2);}
            elseif(strcmp($a,"Номер")==0){$schetnum = $b;}
            elseif(strcmp($a,"Примечание")==0){$schetcom = $b;}
            }
          //echo "schetnum:".$schetnum."<br>";
          if(isset($parser->{'Документ'}->{'Валюта'}))
            {
            foreach($parser->{'Документ'}->{'Валюта'}->attributes() as $a => $b)
              {if(strcmp($a,"КодОКВ")==0){$schetval = $b;}}
            }
          //$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
          if(isset($parser->{'Документ'}->{'Налоги'}->{'Налог'}->{'Сумма'})){$itogndss = $parser->{'Документ'}->{'Налоги'}->{'Налог'}->{'Сумма'};}
          $i = 0;
          while($i<count($parser->{'Документ'}->{'Параметр'}))
            {
            if(strcmp($parser->{'Документ'}->{'Параметр'}[$i]->attributes()->{'Имя'},"ДоговорДата")==0)
              {
              $dogdt = $parser->{'Документ'}->{'Параметр'}[$i]->Attributes()->{'Значение'};
              $dogdt = substr($dogdt,6,4)."-".substr($dogdt,3,2)."-".substr($dogdt,0,2);
              }
            elseif(strcmp($parser->{'Документ'}->{'Параметр'}[$i]->Attributes()->{'Имя'},"ДоговорНомер")==0)
              {
              $dognum = $parser->{'Документ'}->{'Параметр'}[$i]->Attributes()->{'Значение'};
              }
            $i++;
            }
          //echo $dognum."_".$dogdt."<br>";
          if(isset($parser->{'Документ'}->{'Поставщик'}))
            {
            if(isset($parser->{'Документ'}->{'Поставщик'}->{'БанкРекв'}))
              {
              foreach($parser->{'Документ'}->{'Поставщик'}->{'БанкРекв'}->attributes() as $a => $b)
                {
                if(strcmp($a,"БИК")==0){$bnkbic = $b;}
                elseif(strcmp($a,"РСчет")==0){$recbnkacc = $b;}
                }
              }
            }
          //echo $bnkbic."_".$recbnkacc."<br>";
          if(isset($parser->{'Документ'}->{'ТаблДок'}))
            {
            if(isset($parser->{'Документ'}->{'ТаблДок'}->{'ИтогТабл'}))
              {
              foreach($parser->{'Документ'}->{'ТаблДок'}->{'ИтогТабл'}->attributes() as $a => $b)
                {
                if(strcmp($a,"Кол_во")==0){$itogkol = $b;}
                elseif(strcmp($a,"Сумма")==0){$itogsum = $b;}
                }
              if(isset($parser->{'Документ'}->{'ТаблДок'}->{'ИтогТабл'}->{'НДС'}))
                {
                foreach($parser->{'Документ'}->{'ТаблДок'}->{'ИтогТабл'}->{'НДС'}->attributes() as $a => $b)
                  {if(strcmp($a,"Сумма")==0){$itogndss = $b;}}
                }
              }
            //echo $itogkol."_".$itogsum."_".$itogndss."_".count($parser->{'Документ'}->{'ТаблДок'}->{'СтрТабл'})."<br>";
            $i = 0;
            while($i<count($parser->{'Документ'}->{'ТаблДок'}->{'СтрТабл'}))
              {
              $mdet[$i]          = Array();
              $mdet[$i]['name']  = "";
              $mdet[$i]['kol']    = 0;
              $mdet[$i]['sum']    = 0;
              $mdet[$i]['price']  = 0;
              $mdet[$i]['num']    = 0;
              $mdet[$i]['ndssum'] = 0;
              $mdet[$i]['nds']    = "";
              foreach($parser->{'Документ'}->{'ТаблДок'}->{'СтрТабл'}[$i]->attributes() as $a => $b)
                {
                if(strcmp($a,"Название")==0){$mdet[$i]['name'] = $b;}
                elseif(strcmp($a,"Кол_во")==0){$mdet[$i]['kol'] = $b;}
                elseif(strcmp($a,"Сумма")==0){$mdet[$i]['sum'] = $b;}
                elseif(strcmp($a,"Цена")==0){$mdet[$i]['price'] = $b;}
                elseif(strcmp($a,"ПорНомер")==0){$mdet[$i]['num'] = $b;}
                }
              if(isset($parser->{'Документ'}->{'ТаблДок'}->{'СтрТабл'}[$i]->{'НДС'}))
                {
                foreach($parser->{'Документ'}->{'ТаблДок'}->{'СтрТабл'}[$i]->{'НДС'}->attributes() as $a => $b)
                  {
                  if(strcmp($a,"Сумма")==0){$mdet[$i]['ndssum'] = $b;}
                  elseif(strcmp($a,"Ставка")==0){$mdet[$i]['nds'] = $b;}
                  }
                }
              $i++;
              }
            }
          $isparse=1;
          }
        elseif(isset($parser->{'Документ'}->{'ХозОперация'}))
          {
          //echo "2<br>";
          //echo "{'Документ'}->{'ХозОперация'}<br>";
          if(strcmp($parser->{'Документ'}->{'ХозОперация'},"Счет на оплату")==0)
            {
            //echo "{'Документ'}->{'ХозОперация'}<br>";
            if(isset($parser->{'Документ'}->{'Номер'})){$schetnum = $parser->{'Документ'}->{'Номер'};}
            if(isset($parser->{'Документ'}->{'Дата'})){$schetdt = $parser->{'Документ'}->{'Дата'};}
            if(isset($parser->{'Документ'}->{'Валюта'})){$schetval = $parser->{'Документ'}->{'Валюта'};}
            if(isset($parser->{'Документ'}->{'Сумма'})){$itogsum = $parser->{'Документ'}->{'Сумма'};}
            if(isset($parser->{'Документ'}->{'Налоги'}->{'Налог'}->{'Сумма'})){$itogndss = $parser->{'Документ'}->{'Налоги'}->{'Налог'}->{'Сумма'};}
            $i = 0;
            //echo "docnum:".$docnum."<br>";
            //echo "{'Документ'}->{'Контрагенты'}->{'Контрагент'}:".count($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'})."<br>";
            //echo "{'Документ'}->{'Товары'}->{'Товар'}:".count($parser->{'Документ'}->{'Товары'}->{'Товар'})."<br>";
            while($i<count($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}))
              {
              if(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'Роль'}))
                {
                if(strcmp($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'Роль'},"Продавец")==0)
                  {
                  if(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ОфициальноеНаименование'}))
                    {$doccontr = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ОфициальноеНаименование'};}
                  elseif(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ПолноеНаименование'}))
                    {$doccontr = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ПолноеНаименование'};}
                  if(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'РасчетныеСчета'}->{'РасчетныйСчет'}->{'Банк'}->{'БИК'}))
                    {$bnkbic = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'РасчетныеСчета'}->{'РасчетныйСчет'}->{'Банк'}->{'БИК'};}
                  if(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'РасчетныеСчета'}->{'РасчетныйСчет'}->{'НомерСчета'}))
                    {$recbnkacc = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'РасчетныеСчета'}->{'РасчетныйСчет'}->{'НомерСчета'};}
                  }
                elseif(strcmp($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'Роль'},"Покупатель")==0)
                  {
                  if(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ОфициальноеНаименование'}))
                    {$docorg = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ОфициальноеНаименование'};}
                  elseif(isset($parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ПолноеНаименование'}))
                    {$docorg = $parser->{'Документ'}->{'Контрагенты'}->{'Контрагент'}[$i]->{'ПолноеНаименование'};}
                  }
                }
              $i++;
              }
            $i = 0;
            $itogkol = 0;
            while($i<count($parser->{'Документ'}->{'Товары'}->{'Товар'}))
              {
              $mdet[$i]           = Array();
              $mdet[$i]['name']   = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'Наименование'};
              $mdet[$i]['kol']    = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'Количество'};
              $mdet[$i]['sum']    = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'Сумма'};
              $mdet[$i]['price']  = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'ЦенаЗаЕдиницу'};
              $mdet[$i]['num']    = ($i+1);
              $mdet[$i]['ndssum'] = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'Налоги'}[0]->{'Налог'}[0]->{'Сумма'};
              $mdet[$i]['nds']    = $parser->{'Документ'}->{'Товары'}->{'Товар'}[$i]->{'Налоги'}[0]->{'Налог'}[0]->{'Ставка'};
              $itogkol = $itogkol+$mdet[$i]['kol'];
              $i++;
              }
            //echo "mdet:".count($mdet)."<br>";
            $isparse=1;
            }
          }
        elseif(isset($parser->attributes()->{'ИдФайл'}))
          {
          //echo "3<br>";
          if(strcmp(substr($parser->attributes()->{'ИдФайл'},0,8),"ON_SCHET")==0)//Разбираем счет
            {
            //echo $parser->attributes()->{'ИдФайл'}."<br>";
            if(isset($parser->{'Документ'}))
              {
              if(isset($parser->{'Документ'}->{'СвСчет'}->attributes()->{'НомерСчет'}))
                {$schetnum = $parser->{'Документ'}->{'СвСчет'}->attributes()->{'НомерСчет'};}
              if(isset($parser->{'Документ'}->{'СвСчет'}->attributes()->{'ДатаСчет'}))
                {$schetdt = $parser->{'Документ'}->{'СвСчет'}->attributes()->{'ДатаСчет'};
                 $docdt = $parser->{'Документ'}->{'СвСчет'}->attributes()->{'ДатаСчет'};
                 $docdt = substr($docdt,6,4)."-".substr($docdt,3,2)."-".substr($docdt,0,2);}
              if(isset($parser->{'Документ'}->{'СвСчет'}->attributes()->{'КодОКВ'}))
                {$schetval = $parser->{'Документ'}->{'СвСчет'}->attributes()->{'КодОКВ'};}
              if(isset($parser->{'Документ'}->{'ТаблСчет'}->{'ВсегоОпл'}->attributes()->{'СтТовУчНалВсего'}))
                {$itogsum = $parser->{'Документ'}->{'ТаблСчет'}->{'ВсегоОпл'}->attributes()->{'СтТовУчНалВсего'};}
              if(isset($parser->{'Документ'}->{'ТаблСчет'}->{'ВсегоОпл'}->{'СумНалВсего'}->attributes()->{'СумНДС'}))
                {$itogndss = $parser->{'Документ'}->{'ТаблСчет'}->{'ВсегоОпл'}->{'СумНалВсего'}->attributes()->{'СумНДС'};}
              if(isset($parser->{'Документ'}->{'СвСчет'}->{'СвПрод'}->{'БанкРекв'}->attributes()->{'НомерСчета'}))
                {$recbnkacc = $parser->{'Документ'}->{'СвСчет'}->{'СвПрод'}->{'БанкРекв'}->attributes()->{'НомерСчета'};}
              if(isset($parser->{'Документ'}->{'СвСчет'}->{'СвПрод'}->{'БанкРекв'}->{'СвБанк'}->attributes()->{'БИК'}))
                {$bnkbic = $parser->{'Документ'}->{'СвСчет'}->{'СвПрод'}->{'БанкРекв'}->{'СвБанк'}->attributes()->{'БИК'};}
              if(isset($parser->{'Документ'}->{'ТаблСчет'}))
                {
                $i = 0;
                $itogkol = 0;
                while($i<count($parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}))
                  {
                  $mdet[$i]           = Array();
                  $mdet[$i]['name']   = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->attributes()->{'НаимТов'};
                  $mdet[$i]['kol']    = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->attributes()->{'КолТов'};
                  $mdet[$i]['sum']    = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->attributes()->{'СтТовУчНал'};
                  $mdet[$i]['price']  = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->attributes()->{'ЦенаТов'};
                  $mdet[$i]['num']    = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->attributes()->{'НомСтр'};
                  $mdet[$i]['ndssum'] = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->{'СумНал'}->attributes()->{'СумНДС'};
                  $mdet[$i]['nds']    = $parser->{'Документ'}->{'ТаблСчет'}->{'СведТов'}[$i]->{'НалСт'}->attributes()->{'НалСтВел'};
                  $itogkol = $itogkol+$mdet[$i]['kol'];
                  $i++;
                  }
                }
              }
            $isparse=1;
            }
          }
        }
      if($isparse==1)
        {
        //echo "orgid:$orgid<br>contrid:$contrid<br>";
        if(($orgid>0) && ($contrid>0))
          {
          if(strlen($dognum)>0)
            {$sql = "select f_id,f_dogtype from ".DBPref."dogs where f_contrid=".$contrid." and f_orgid=".$orgid." and f_dogname='".$dognum."' and f_dogdate='".$dogdt."'";}
          else
            {$sql = "select f_id,f_dogtype from ".DBPref."dogs where f_contrid=".$contrid." and f_orgid=".$orgid;}
          //echo $sql."<br>";
          $res = $dbh->query($sql);
          if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
            $dogid   = $row['f_id'];
            $dogtype = $row['f_dogtype'];
            //echo "dogid:$dogid<br>dogtype:$dogtype<br>";
            if((strlen($bnkbic)>0)&&(strlen($recbnkacc)>0))
              {
              $sql = "select f_id from ".DBPref."bank_accounts where f_bic='".$bnkbic."' and f_acc='".$recbnkacc."'";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {$cbaccid = $row['f_id'];}
              else
                {
                $kai = Array('curtbl'=>101,'f_clntid'=>$contrid,'f_bic'=>"'".$bnkbic."'",'f_acc'=>$recbnkacc);
                $ar = json_decode(addRowTbl($kai), true);
                if($ar[0]=="true")
                {$cbaccid = $ar[2];}
                }
              //echo "cbaccid:$cbaccid<br>";
              }
            }
          }
        //вставляем счет
        //sdid 2877
        /*
        $itognds = 0;
        if(($itogndss>0)&&($itogsum>0))
          {
          if(round(($itogsum/120)*20,2)==$itogndss)
            {$itognds=2;}
          }
        $kai = Array('curtbl'=>17,'f_orgid'=>$orgid,'f_type'=>2,'f_num'=>"'".$schetnum."'",'f_dt'=>$schetdt,'f_contrid'=>$contrid,
            'f_cbaccid'=>$cbaccid,
            'f_dogtype'=>$dogtype,
            'f_dogid'=>$dogid,
            'f_sum'=>$itogsum,'f_status'=>12,'f_ndssum'=>$itogndss,'f_val'=>$schetval,'f_nds'=>$itognds,
            'f_com'=>"'".$schetcom."'",'f_edoid'=>$edoid);
        $ar = json_decode(addRowTbl($kai), true);
        if($ar[0]=="true")
          {
          $schetid = $ar[2];
          if($schetid>0)
            {
            $ks++;
            $i = 0;
            //echo count($mdet)."<br>";
            while($i<count($mdet))
              {
              $nds = 0;
              $sql = "select f_num from ".DBPref."spr where f_type=10 and (upper(f_name)='".strtoupper($mdet[$i]['nds'])."' or f_name ='".$mdet[$i]['nds']."%')";
              //echo $sql."<br>";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {$nds = $row['f_num'];}
              $kkai = Array('curtbl'=>179 ,'f_schetid'=>$schetid,'f_num'=>$mdet[$i]['num'],'f_count'=>$mdet[$i]['kol'],'f_price'=>$mdet[$i]['price'],'f_sum'=>$mdet[$i]['sum'],
                  'f_nds'=>$nds,'f_ndssum'=>$mdet[$i]['ndssum'],'f_val'=>$schetval,'f_grnd'=>"'".$mdet[$i]['name']."'");
              $aar = json_decode(addRowTbl($kkai), true);
              if($aar[0]=="true")
                {$kd++;}
              $i++;
              }
            }
          }
        */
        // Ищем в базе счет. Если нашли, смотрим:
        // если f_edoid привязан, новый счет не создаем
        // если не привязан, привязываем, меняем статус на "Загружен по ЭДО", не вый счет не создаем.
        // Если счет не найден, создаем новый
        $cursql = "select f_id,f_edoid,f_status 
                   from ".DBPref."schets 
                   where f_orgid=".$orgid." and f_contrid=".$contrid." and f_num like '".$schetnum."' and f_dt='".$schetdt."'
                   and f_dogid=".$dogid." and f_val=".$schetval." and f_sum=".$itogsum." and (f_dogtype=$dogtype or ($dogtype=6 and f_dogtype=0))";
        $res1 = $dbh->query($cursql);
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {
          if($row1['f_edoid']==0)
            {
            //$ki1 = Array('curtbl'=>17,'curidx'=>$row1['f_id'],'f_edoid'=>$edoid,'f_status'=>12);
            $ki1 = Array('curtbl'=>17,'curidx'=>$row1['f_id'],'f_edoid'=>$edoid);
            editRowTbl($ki1);
            }
          }
        else
          {
          $itognds = 0;
          if(($itogndss>0)&&($itogsum>0))
            {
            if(round(($itogsum/120)*20,2)==$itogndss)
              {$itognds=2;}
            }
          $kai = Array('curtbl'=>17,'f_orgid'=>$orgid,'f_type'=>2,'f_num'=>"'".$schetnum."'",'f_dt'=>$schetdt,'f_contrid'=>$contrid,
              'f_cbaccid'=>$cbaccid,
              'f_dogtype'=>$dogtype,
              'f_dogid'=>$dogid,
              'f_sum'=>$itogsum,'f_status'=>12,'f_ndssum'=>$itogndss,'f_val'=>$schetval,'f_nds'=>$itognds,
              'f_com'=>"'".$schetcom."'",'f_edoid'=>$edoid);
          $ar = json_decode(addRowTbl($kai), true);
          if($ar[0]=="true")
            {
            $schetid = $ar[2];
            if($schetid>0)
              {
              $ks++;
              $i = 0;
              //echo count($mdet)."<br>";
              while($i<count($mdet))
                {
                $nds = 0;
                $sql = "select f_num from ".DBPref."spr where f_type=10 and (upper(f_name)='".strtoupper($mdet[$i]['nds'])."' or f_name ='".$mdet[$i]['nds']."%')";
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {$nds = $row['f_num'];}
                $kkai = Array('curtbl'=>179 ,'f_schetid'=>$schetid,'f_num'=>$mdet[$i]['num'],'f_count'=>$mdet[$i]['kol'],'f_price'=>$mdet[$i]['price'],'f_sum'=>$mdet[$i]['sum'],
                    'f_nds'=>$nds,'f_ndssum'=>$mdet[$i]['ndssum'],'f_val'=>$schetval,'f_grnd'=>"'".$mdet[$i]['name']."'");
                $aar = json_decode(addRowTbl($kkai), true);
                if($aar[0]=="true")
                  {$kd++;}
                $i++;
                }
              }
            }
          }
        //~sdid 2877
        }
      }
    }
  return $ks;
  }
//sdid 3172
// 2.	Для расходного типа операции: 
// 2.1.	Кроме:
// 2.1.1. FE -> поставщику (ИД 116)
// 2.1.2. Таможня (по наличию признака isdt)
// 2.1.3. Операций финансирования (ИД 25)
function checkToperParm($toper,$bdrarticle=0,$prevbdrarticle=(-1),$dbh=null)
  {
  $response = [false,0,0,0];
  if(!$dbh){$dbh = dbconnect();}
  /*$sql = "
         select 
         case when t.f_id=116      
                then 0
              when t.f_isfinance=1 
                then 0
              when t.f_type=50 
                then 0
              when t.f_type=53 
                then 0
              when t.f_isdt=1      
                then 0
              when (SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$bdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num)<>2
                then 0
              else 1
         end toper,
         ifnull((SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$bdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num),0) bdr,
         ifnull((SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$prevbdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num),-1) prevbdr
         from veda_typeopers t
         where t.f_id=$toper
         ";*/

  $sql = "
         select 
           t.f_noneeddopdog,
         case when (t.f_id=116 or t.f_isfinance=1 or t.f_type=50 or t.f_type=53 or t.f_isdt=1 or 
                   (SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$bdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num)<>2)
                then 0
              else 1
         end toper,
         ifnull((SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$bdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num),0) bdr,
         ifnull((SELECT s84.f_num
                      FROM veda_spr s86, veda_spr s85, veda_spr s84
                     WHERE s86.f_type=86
                       AND s85.f_type=85
                       AND s84.f_type=84
                       AND s86.f_num=$prevbdrarticle
                       AND s86.f_uslint=s85.f_num
                       AND s85.f_dopprint=s84.f_num),-1) prevbdr
         from veda_typeopers t
         where t.f_id=$toper
         ";

  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {$response = [true, intval($row['toper']), intval($row['bdr']), intval($row['prevbdr']), intval($row['f_noneeddopdog'])];}
  return $response;
  }

//sdid 3348
function checkInflateNDSParm($orgid=0,$poprid=0,$toper=0,$dbh=null)
  {
  $response = [false,"Ф-я checkInflateNDSParm: неверные входные параметры"];
  if(!$dbh){$dbh = dbconnect();}
  //error_log("\n\norgid=$orgid poprid=$poprid toper=$toper\n\n",0);
  if($orgid==16)
    {
    $sql = "select si.f_nds poprnds,
                   ifnull((select s84.f_num from veda_spr s84, veda_spr s85, veda_spr s86, veda_typeopers t 
                            where s84.f_type=84 and s85.f_type=85 and s86.f_type=86 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=t.f_bdrarticle and t.f_id=$toper),0) kindtopbdr,
                   ifnull((select s84.f_num from veda_spr s84, veda_spr s85, veda_spr s86
                            where s84.f_type=84 and s85.f_type=85 and s86.f_type=86 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=si.f_bdrarticle),0) kindpoprbdr
             from veda_spec_invoices si
            where si.f_id=$poprid
           ";
    $conn = $dbh->query($sql);
    if($row = $conn->fetch(PDO::FETCH_ASSOC))
      {
      if(($row['poprnds']==0 || $row['poprnds']==3) && ($row['kindtopbdr']==2 || $row['kindtopbdr']==0) && $row['kindpoprbdr']==1)
        {$response = [true, 1, intval($row['poprnds']), intval($row['kindtopbdr']), intval($row['kindpoprbdr'])];}
      else 
        {$response = [true, 0, intval($row['poprnds']), intval($row['kindtopbdr']), intval($row['kindpoprbdr'])];}
      }
    }
  //error_log("\n\ncheckInflateNDSParm_lib = ".var_export($response,true)."\n\n",0);
  return $response;
  }
//~sdid 3348

function getContrFromDogs($dogid)
  {
  $response = [false, false];
  $dbh = dbconnect();
  $sql = "SELECT d.f_contrid FROM veda_dogs d WHERE d.f_id=".$dogid;
  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {$response = [true, intval($row['f_contrid'])];}
  else
    {$response = [false, "Не найден контрагент по договору"];}
  return $response;
  }
//~sdid 3172
// sdid 1958
function checkIsProfitOrLoss($bdr_article,$dbh=null)
  {
  $response = [false, false];
  if(!$dbh){$dbh = dbconnect();}
  $sql = "SELECT s84.f_num
                  FROM veda_spr s86, veda_spr s85, veda_spr s84
                  WHERE s86.f_type=86
                    AND s85.f_type=85
                    AND s84.f_type=84
                    AND s86.f_num=".$bdr_article."
                    AND s86.f_uslint=s85.f_num
                    AND s85.f_dopprint=s84.f_num";
  //if($_SESSION['loginid'])
  //  {if($_SESSION['loginid']==2){echo $sql;}}
  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {$response = [true, intval($row['f_num'])];}
  return $response;
  }

function getContrFromSpecs($specs_id)
  {
  $response = [false, false];
  $dbh = dbconnect();
  $sql = "SELECT d.f_contrid
          FROM veda_dogs d, veda_specs s 
          WHERE d.f_id=s.f_dogid AND s.f_id=".$specs_id;
  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {$response = [true, intval($row['f_contrid'])];}
  else
    {$response = [false, "Не найден контрагент по спецификации"];}
  return $response;
  }
// ~ sdid 1958
// sdid 1874
function format_date($dt)
  {
  $result = false;
  $pattern = '/^([0-2]\d|3[0-1]).(0\d|1[0-2]).(19|20)\d{2}$/'; // dd.mm.yyyy
  if(is_string($dt))
    {
    if(preg_match($pattern, $dt) === 1)
      {
      $date_parts = explode(".", $dt);
      $result = $date_parts[2] . "-" . $date_parts[1] . "-" . $date_parts[0]; // yyyy-mm-dd
      }
    else
      {$result = $dt;}
    }
  return $result;
  }
// ~ sdid 1874

// sdid 2023
function getSQL_CorrectionNumFromOper($prefix)
  {
  return "SELECT IFNULL(MAX(c.f_num), 0) FROM ".DBPref."corrects c, ".DBPref."corrects_opers co WHERE c.f_id=co.f_correctid AND co.f_correctoperid=$prefix.f_id AND c.f_specid=$prefix.f_specid";
  }
// ~ sdid 2023

// sdid 2125
/*
 * Проверка запрета на обмен с 1С, установленного в настройках veda_settings
 *
 * В случае установленного запрета, возвращает:
 * [0] - true
 * [1] - JSON где retval:false, msg:сообщение строкой о запрете
 *
 * В случае отсутствия запрета возвращает:
 * [0] - false
 * [1] - Сообщение строкой об отсутствии запрета
 *
 */
function check1CExchangeBan()
  {
  $dbh = dbconnect();
  $sql = "SELECT COUNT(f_id) as c FROM ".DBPref."settings WHERE f_settype=3";
  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {
    if($row['c'] > 0)
      {return [true, "{\"retval\":\"false\",\"msg\":\"Обмен с 1С запрещен настройками\"}"];}
    else
      {return [false, "Обмен с 1С разрешен"];}
    }
  else
    {return [true, "{\"retval\":\"false\",\"msg\":\"Ошибка получения данных из базы\"}"];}
}
// ~ sdid 2125

// sdid 2347

function validate_date($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function get_throwable_msg($e)
{
    $msg = "Неизвестная ошибка обработки: lib/lib.php/get_throwable_msg";

    if ($e instanceof Throwable)
    {
        $msg = 'Ошибка выполнения: ' . $e->getMessage() . '; Файл: ' . $e->getFile() . "; Строка: " . $e->getLine();
    }
    else
    {
        return "В качестве интерфейса Throwable передан " . gettype($e) . ": lib/lib.php/get_throwable_msg";
    }

    return $msg;
}

/*
 * Получение контактных данных (veda_addres) по клиенту с учетом даты начала действия
 *
 * $client_id - int(11), id клиента в veda_clients
 * $data_type - int(11), тип данных из veda_spr.f_type = 58
 * $valid_date - YYYY-mm-dd, дата начала действия
 *
 *  Возвращает [bool, string]
 *
 */
function get_client_contact_data($client_id, $data_type, $valid_date)
{
    try {
        $response = [false, "Неизвестная ошибка (lib/lib.php/get_client_contact_data)"];

        if ($client_id > 0 && $data_type > 0 && validate_date($valid_date, 'Y-m-d'))
        {
            $dbh = dbconnect();

            $sql = "SELECT * FROM ".DBPref."addres WHERE f_clnttype=2 and f_clntid=$client_id AND f_type=$data_type AND f_validdate<='$valid_date' ORDER BY f_validdate DESC";
            //echo $sql;
            $conn = $dbh->query($sql);
            if ($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
                $response = [true, $row['f_addr']];
            }
            else
            {
                $response = [false, 'Данные не найдены'];
            }
        }
        else
        {
            $response = [false, "Неверные входные параметры"];
        }

        return $response;
    } catch (\Throwable $e) {
        return [false, get_throwable_msg($e)];
    }
}
// ~ sdid 2347
//sdid2358
function get_attrhist($target_menu, $target_object, $target_field, $valid_date)
  {
  try
    {
    $response = [false, "Неизвестная ошибка (lib/lib.php/get_client_contact_data)"];
    if($target_menu > 0 && $target_object > 0 && $target_field > 0 && validate_date($valid_date, 'Y-m-d'))
      {
      $dbh = dbconnect();
      $sql = "SELECT 
                CASE 
                  WHEN c.f_fldobjtype=101 THEN
                    (SELECT f_acc FROM ".DBPref."bank_accounts WHERE f_id=c.f_fldvalue)
                  ELSE c.f_fldvalue
                END fldvalue
              FROM ".DBPref."attrhist c 
              WHERE c.f_objtype=$target_menu 
                AND c.f_objid=$target_object 
                AND c.f_fldnum=$target_field 
                AND c.f_validdate<='$valid_date' 
              ORDER BY c.f_validdate DESC";
      $conn = $dbh->query($sql);
      if($row = $conn->fetch(PDO::FETCH_ASSOC))
        {
        $response = [true, $row['fldvalue']];
        }
      else
        {
        $response = [false, 'Данные не найдены'];
        }
      }
    else
      {
      $response = [false, "Неверные входные параметры"];
      }
    return $response;
    }
  catch(\Throwable $e)
    {return [false, get_throwable_msg($e)];}
  }
//~sdid2358

//sdid2504
function validatePositiveInteger($input)
  {
  $response = false;
  if(!is_null($input))
    {
    if(strlen($input) > 0)
      {
      if(filter_var($input, FILTER_VALIDATE_INT) !== false)
        {
        if($input > 0)
          {
          $response = true;
          }
        }
      }
    }
  return $response;
  }

//function getPairedOperationBlock($spec_id, $type_of_operation)
function getPairedOperationBlock($spec_id, $type_of_operation, $contr_id, $org_id, $bdr)
  {
  $response = "";
  //echo json_encode(func_get_args()) . "<br>";
  foreach(func_get_args() as $arg) {if (!validatePositiveInteger($arg)) {return $response;}}
  if($contr_id == $org_id){return $response;}
  $dbh = dbconnect();
  $sql = "
      SELECT
          IFNULL(( SELECT f_isourorg FROM ".DBPref."clients WHERE f_id = $contr_id ), 0 ) isourorg,
          (
          SELECT
              GROUP_CONCAT( s86.f_num SEPARATOR ',' ) 
          FROM
              ".DBPref."spr s86,
              ".DBPref."spr s85,
              ".DBPref."spr s84,
              ".DBPref."spr s83 
          WHERE
              s86.f_type = 86 
              AND s85.f_type = 85 
              AND s84.f_type = 84 
              AND s83.f_type = 83 
              AND s86.f_dopprint = 1 
              AND s86.f_uslint = s85.f_num 
              AND s85.f_uslint = s83.f_num 
              AND s85.f_dopprint = s84.f_num 
              AND s86.f_isuse = 1 
              AND s85.f_name LIKE '%-%' 
          ) expense_bdrs,
          IFNULL(( SELECT f_islogist FROM ".DBPref."typeopers WHERE f_id = $type_of_operation ), 0 ) islogist,
          ( SELECT COUNT( f_id ) FROM ".DBPref."typeopers_links WHERE f_idoper = $type_of_operation and f_contrid=$contr_id ) links_count,
          (
          SELECT
              COUNT( d.f_id ) 
          FROM
              ".DBPref."dogs d 
          WHERE
              #d.f_orgid = $contr_id ~#
              (d.f_orgid = $contr_id or d.f_orgid in ( SELECT f_contrid FROM ".DBPref."typeopers_links WHERE f_idoper = $type_of_operation ))
              AND d.f_contrid = $org_id 
              AND d.f_subtype IN ( SELECT f_typedog FROM veda_typeopers_links WHERE f_idoper = $type_of_operation ) 
          ) linked_dogs,
          IFNULL((
                  SELECT
                      GROUP_CONCAT(
                          CONCAT(
                              s.f_id,
                              ',',
                              ifnull(d.f_dogname,''),
                              '/',
                              ifnull(s.f_num,''),
                              '/',
                              ifnull(o.f_abbr,''),
                              '/',
                              ifnull(c.f_cname,''),
                              ' (ИД ',
                              s.f_id,
                              ')'
                          ) SEPARATOR ';' 
                  ) 
                  FROM
                      ".DBPref."dogs d,
                      ".DBPref."specs s,
                      ".DBPref."clients c,
                      ".DBPref."clients o
                  WHERE
                      #d.f_orgid = $contr_id   ~#
                      (d.f_orgid = $contr_id or d.f_orgid in ( SELECT f_contrid FROM ".DBPref."typeopers_links WHERE f_idoper = $type_of_operation ))
                      AND d.f_contrid = $org_id 
                      AND d.f_subtype IN ( SELECT f_typedog FROM ".DBPref."typeopers_links WHERE f_idoper = $type_of_operation ) 
                      AND s.f_dogid = d.f_id
                      AND o.f_id = d.f_orgid 
                      AND c.f_id = d.f_contrid
                      AND s.f_parentspecid = $spec_id
                      ),
              0 
          ) linked_specs
         ";
  //echo $sql . "<br>";
  $conn = $dbh->query($sql);
  if($row = $conn->fetch(PDO::FETCH_ASSOC))
    {
    $isourorg = $row['isourorg'];
    $expense_bdrs = explode(',', $row['expense_bdrs']); // расходные статьи бюджета
    $islogist = $row['islogist'];
    $links_count = $row['links_count'];
    $linked_specs = $row['linked_specs'];
    $linked_dogs = $row['linked_dogs'];

    //echo "isourorg = " . $isourorg . "<br>";
    //echo "expense_bdrs = " . json_encode($expense_bdrs) . "<br>";
    //echo "islogist = " . $islogist . "<br>";
    //echo "links_count = " . $links_count . "<br>";
    //echo "linked_specs = " . $linked_specs . "<br>";

    if(
       $isourorg == 1 // 3.1.1. В качестве контрагента выбрана - наша организация
       && in_array($bdr, $expense_bdrs) // 3.1.2. Выполняемая операция - расходная
       && $islogist == 1 // 3.1.3. Вид выполняемой операции имеет признак "Логист."
       && $links_count > 0 // 3.1.3. Для видов выполняемой операции присутствует ссылка на парный вид операции
       && $linked_dogs > 0 // 3.1.4. Между нашей организацией-исполнителем и нашей организацией-контрагентом существует договор с подвидом как в связке с парной операцией, где организация - организация-контрагент, а контрагент - организация-исполнитель
      )
      {
      $linked_specs_options = "";
      $selected = " selected ";
      $i = 0;
      if($linked_specs != 0)
        {
        foreach(explode(';', $linked_specs) as $linked_spec_data)
          {
          $linked_spec_array = explode(',', $linked_spec_data);
          $linked_spec_id = $linked_spec_array[0];
          $linked_spec_name = $linked_spec_array[1];
          $linked_specs_options .= "<option $selected value='$linked_spec_id'>$linked_spec_name</option>";
          if($i == 0)
            {
            $selected = "";
            }
          $i++;
          }
        }

      $options = "
      $linked_specs_options
      <option $selected value='1'>Новая заявка</option>
      <option value='0'>Не выполнять</option>
      ";
      $select = "<select class='form-control'  id='specinv-links__select'>$options</select>";

      $block = "<tr>
                  <td colspan=1>Выполнить парную операцию по:</td>
                  <td colspan=2>$select</td>
                 </tr>
                 ";

      $response = $block;
      }
    }
  //echo $response . "<br>";
  return $response;
  }
// ~ sdid 2504