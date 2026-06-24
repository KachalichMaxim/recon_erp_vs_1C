<?php
  //Бибилиотека обработки функций вставки/обновления/удаления строк в таблицах

  // sdid 2023
if(!function_exists("checkCorrects_php"))
  {include __DIR__."/../mclasses/Corrects/class.php";}
if(!function_exists("checkCorrectsOpers_php"))
  {include __DIR__."/../mclasses/CorrectsOpers/class.php";}
if(!function_exists("checkclasslib_php"))
  {include __DIR__."/classlib.php";}
// ~ sdid 2023
  require_once ("easydb.php");
  require_once ("easylib.php");
  require_once ("SendMailSmtpClass.php");
//sdid 1315
  if(!function_exists("checklib_php"))
    {include __DIR__."/lib.php";}
//sdid 1315
// sdid 2504
if(!function_exists("checkSpecinvLinksclass_php"))
  {include __DIR__."/../mclasses/SpecinvLinks/class.php";}
// ~ sdid 2504

// sdid 2707
if(!function_exists("checkFundingclass_php"))
  {include __DIR__."/../mclasses/Funding/class.php";}
// ~ sdid 2707
//sdid 3339
if(!function_exists("checkAgentRepsclass_php"))
  {include __DIR__."/../mclasses/AgentReps/class.php";}
//~sdid 3339
//sdid 246
  function checkrowsLib_php(){return 0;}// sdid 2023

// ФУНКЦИИ ПЕРЕНЕСЕНЫ ИЗ expAccHist1DocTo1C.php
  //выгрузить один документ выписки в 1С
  function expAccHist1DocTo1C($row,$i,$dbh)
    {
    $c1res   = 0;
    $answ    = "";
    $lorgdiv = 0;
    $dtype   = 0;
    $answ    = $answ."<tr><td>".$i."</td><td>".$row['f_orgid1C']."</td><td>".$row['dirtype']."</td><td>".$row['f_ppnum']."</td><td>".$row['f_ppdt']."</td><td>";
    //sdid3047 - вынес сбор инфы для экспорта выписки по поступлениям клиентов, если првиязывались операции не только по счетам, в отдельный блок
    $jstr      = "";
    $needexpah = 1;
    //проверяем наличие связки выписки со счетам, операциями
    $sql  = "select 
               (select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=3) q1
              ,(select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=1) q2
              ,(select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=3 
                  and ahd.f_docid not in (select f_operid from ".DBPref."schets where f_id in (select f_docid from ".DBPref."acchist_docs where f_acchistid=ah.f_id and f_doctype=1) ) 
                  and ahd.f_docid not in (select f_operid from ".DBPref."schets where f_maininv in (select f_docid from ".DBPref."acchist_docs where f_acchistid=ah.f_id and f_doctype=1) ) 
                  and ahd.f_docid not in (select lsdo.f_operid from ".DBPref."schets ls,".DBPref."schets_details lsd,".DBPref."schets_details_opers lsdo where lsdo.f_schets_detailsid=lsd.f_id and lsd.f_schetid=lsd.f_id and lsd.f_id in (select f_docid from ".DBPref."acchist_docs where f_acchistid=ah.f_id and f_doctype=1) ) 
               ) q4
             FROM ".DBPref."acchist ah where ah.f_ahtype=1 and ah.f_id=".$row['f_id'];
    //echo "sql0: $sql<br>";
    $res1 = $dbh->query($sql);
    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
      {
      if(($row1['q1']>0)and($row1['q2']>0)and($row1['q4']>0))
        {
        $sql = "select 
                  si.f_nds,si.f_isvozm,si.f_specid,si.f_parenttype
                  ,sum(ahd.f_clssum) clssum
                  ,count(*) clscnt
                  ,case 
                    when si.f_parenttype=2 then 
                      case 
                        when si.f_isvozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=si.f_specid) 
                        else (select f_kod1cb from ".DBPref."specs where f_id=si.f_specid) 
                      end 
                    else (select f_kod1c from ".DBPref."dogs where f_id=si.f_specid) 
                  end dogc 
                  ,case 
                     when (select sch.f_kod1c from veda_schets sch where sch.f_operid=si.f_id and sch.f_maininv=0 and sch.f_ismaininv=0 limit 1) is not null then
                       (select concat(sch.f_dt,'|',sch.f_kod1c) from veda_schets sch where sch.f_operid=si.f_id and sch.f_maininv=0 and sch.f_ismaininv=0 limit 1)
                     when (select sch.f_kod1c from veda_schets sch,veda_schets schm where schm.f_maininv=sch.f_id and schm.f_ismaininv=0 and schm.f_operid=si.f_id limit 1) is not null then
                       (select concat(sch.f_dt,'|',sch.f_kod1c) from veda_schets sch,veda_schets schm where schm.f_maininv=sch.f_id and schm.f_ismaininv=0 and schm.f_operid=si.f_id limit 1)
                     when (select sch.f_kod1c from veda_schets sch,veda_schets_details schd,veda_schets_details_opers schdo where schdo.f_operid=si.f_id and schdo.f_schets_detailsid=schd.f_id and schd.f_schetid=sch.f_id limit 1) is not null then
                       (select concat(sch.f_dt,'|',sch.f_kod1c) from veda_schets sch,veda_schets_details schd,veda_schets_details_opers schdo where schdo.f_operid=si.f_id and schdo.f_schets_detailsid=schd.f_id and schd.f_schetid=sch.f_id limit 1)
                     else ''
                   end schet
                from ".DBPref."acchist_docs ahd,".DBPref."spec_invoices si 
                where ahd.f_acchistid=".$row['f_id']." and ahd.f_doctype=3 and ahd.f_docid=si.f_id
                group by si.f_nds,dogc,schet";
        //echo "sql1_0 : $sql<br>";
        $res2    = $dbh->query($sql);
        while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
          {
          $curcursoper = $row['f_cursoper'];
          $izvozm      = 0;
          if($izvozm<2){$izvozm=$row2['f_isvozm'];}
          $cjstr="\"doctype\":\"3\",\"invkodc\":\"".trim(substr($row2['schet'],11))."\",\"invdt\":\"".substr($row2['schet'],0,10).
                 "\",\"kurs\":\"".$curcursoper."\",\"dogc\":\"".$row2['dogc']."\",\"dsum\":".$row2['clssum'].",\"isvozm\":\"".$izvozm.
                 "\",\"valnom\":\"".$row['valnominal']."\",\"dnds\":\"".$row2['f_nds']."\",\"vozmndssum\":\"0\"";
          if(strlen($cjstr)>0)
            {if(strlen($jstr)>0)
               {$jstr=$jstr.",";}
             $jstr=$jstr."{".$cjstr."}";}
          }
        $dtype     = -1;
        $needexpah = 0;
        }
      }
    //echo "1.dtype:$dtype needexpah:$needexpah<br>";
    //sdid3281
    if($needexpah==1)
      {
      //проверяем что выписка по счетам клиенту и есть связанные операции
      $sql  = "select 
                  (select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=1) q1
                 ,(select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=3) q2
               FROM ".DBPref."acchist ah where ah.f_ahtype=1 and ah.f_id=".$row['f_id'];
      //echo "sql2_0 :$sql<br>";
      $res1 = $dbh->query($sql);
      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
        {
        if(($row1['q1']>0)&&($row1['q2']>0))
          {
          $sql2 = "
                   select 
                     sch.f_kod1c invkodc,
                     case 
                       when sch.f_dogtype=2 then 
                         case 
                           when sch.f_vozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=sch.f_dogid) 
                           else (select f_kod1cb from ".DBPref."specs where f_id=sch.f_dogid) 
                         end 
                       else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
                     end dogc,
                     case 
                       when sch.f_dogtype=2 then 
                         (select f_kod1cp from ".DBPref."specs where f_id=sch.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
                     end vozmdogc,
                     case 
                       when sch.f_dogtype=2 then 
                         (select f_kod1cb from ".DBPref."specs where f_id=sch.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
                     end nvozmdogc,
                     ahd.f_clssum dsum,
                     case 
                       when sch.f_vozm=2 then 0
                       else ahd.f_clssum
                     end vozmsum,
                     case 
                       when sch.f_vozm=2 then ahd.f_clssum
                       else 0
                     end nvozmsum,
                     sch.f_vozm isvozm,si.f_nds dnds,
                     round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2) dndssum,
                     case 
                       when sch.f_vozm=2 then 0
                       else round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2)
                     end vozmndssum,
                     case 
                       when sch.f_vozm=2 then round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2)
                       else 0
                     end nvozmndssum,
                     sch.f_status invst,sch.f_dt invdt
                   from ".DBPref."acchist_docs ahd,".DBPref."schets sch,".DBPref."spec_invoices si 
                   where si.f_id=sch.f_operid and sch.f_operid>0 and sch.f_ismaininv=0 and sch.f_maininv=0 and sch.f_id=ahd.f_docid and 
                     ahd.f_doctype=1 and ahd.f_acchistid in (".$row['f_id'].")
                   union 
                   select 
                     sch.f_kod1c invkodc,
                     case 
                       when schd.f_dogtype=2 then 
                         case 
                           when schd.f_vozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=schd.f_dogid) 
                           else (select f_kod1cb from ".DBPref."specs where f_id=schd.f_dogid) 
                         end 
                       else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
                     end dogc,
                     case 
                       when schd.f_dogtype=2 then 
                         (select f_kod1cp from ".DBPref."specs where f_id=schd.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
                     end vozmdogc,
                     case 
                       when schd.f_dogtype=2 then 
                         (select f_kod1cb from ".DBPref."specs where f_id=schd.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
                     end nvozmdogc,
                     sum(ahdsi.f_clssum) dsum,
                     sum(case 
                           when schd.f_vozm=2 then 0
                           else ahdsi.f_clssum
                         end) vozmsum,
                     sum(case 
                     	   when schd.f_vozm=2 then ahdsi.f_clssum
                     	   else 0
                         end) nvozmsum,
                     schd.f_vozm isvozm,si.f_nds dnds,
                     sum(round(cast(ahdsi.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2)) dndssum,
                     sum(case 
                     	   when sch.f_vozm=2 then 0
                     	   else round(cast(ahdsi.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2)
                         end) vozmndssum,
                     sum(case 
                     	   when sch.f_vozm=2 then round(cast(ahdsi.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=si.f_nds)) AS DECIMAL(15,2)),2)
                     	   else 0
                         end) nvozmndssum,
                     sch.f_status invst,sch.f_dt invdt
                   from ".DBPref."acchist_docs ahdsi,".DBPref."acchist_docs ahd,".DBPref."schets sch,".DBPref."schets schd,".DBPref."spec_invoices si,".DBPref."spec_invoices ssi 
                   where 
                     si.f_id=schd.f_operid and 
                     schd.f_operid>0 and schd.f_ismaininv=0 and schd.f_maininv=sch.f_id and sch.f_maininv=0 and sch.f_ismaininv=1 and sch.f_id=ahd.f_docid and ahd.f_doctype=1 
                     and ahdsi.f_doctype=3 and ahdsi.f_docid=ssi.f_id and si.f_id=ssi.f_id
                     and ahd.f_acchistid in (".$row['f_id'].") and ahdsi.f_acchistid in (".$row['f_id'].")
                     group by sch.f_id,schd.f_vozm,si.f_nds
                  ";
          //echo "sql2_1 :$sql2<br>";
          $res2    = $dbh->query($sql2);
          while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
            {
            $curcursoper = $row['f_cursoper'];
            $izvozm      = 0;
            if($izvozm<2){$izvozm=$row2['f_isvozm'];}
            $cjstr="\"doctype\":\"1\",\"invkodc\":\"".$row2['invkodc'].
                   "\",\"dogc\":\"".$row2['dogc']."\",\"vozmdogc\":\"".$row2['vozmdogc']."\",\"nvozmdogc\":\"".$row2['nvozmdogc'].
                   "\",\"dsum\":".$row2['dsum'].",\"vozmsum\":".$row2['vozmsum'].",\"nvozmsum\":".$row2['nvozmsum'].
                   ",\"isvozm\":\"".$row2['isvozm']."\",\"dnds\":\"".$row2['dnds'].
                   "\",\"dndssum\":".$row2['dndssum'].",\"vozmndssum\":".$row2['vozmndssum'].",\"nvozmndssum\":".$row2['nvozmndssum'].
                   ",\"invst\":\"".$row2['invst']."\",\"kurs\":\"".$curcursoper."\",\"valnom\":\"".$row['valnominal']."\",\"invdt\":\"".substr($row2['invdt'],0,10)."\"";

            if(strlen($cjstr)>0)
              {if(strlen($jstr)>0)
                 {$jstr=$jstr.",";}
               $jstr=$jstr."{".$cjstr."}";}
            }
          $dtype     = -1;
          $needexpah = 0;
          }
        }
      }
    //echo "2.dtype:$dtype needexpah:$needexpah<br>";
    //if($needexpah==1)//убрали блок по последнему сообщению в заявке 3281
    //  {
    //  //проверяем что выписка по счетам клиенту и нет связанных операций
    //  $sql  = "select 
    //              (select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=1) q1
    //             ,(select count(*) from ".DBPref."acchist_docs ahd where ahd.f_acchistid=ah.f_id and ahd.f_doctype=3) q2
    //           FROM ".DBPref."acchist ah where ah.f_ahtype=1 and ah.f_id=".$row['f_id'];
    //  //echo "3.sql1:$sql<br>";
    //  $res1 = $dbh->query($sql);
    //  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
    //    {
    //    if(($row1['q1']>0)&&($row1['q2']==0))
    //      {
    //      $sql2 = "
    //               select 
    //                 sch.f_kod1c invkodc,
    //                 case 
    //                   when sch.f_dogtype=2 then 
    //                     case 
    //                       when sch.f_vozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=sch.f_dogid) 
    //                       else (select f_kod1cb from ".DBPref."specs where f_id=sch.f_dogid) 
    //                     end 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
    //                 end dogc,
    //                 case 
    //                   when sch.f_dogtype=2 then 
    //                     (select f_kod1cp from ".DBPref."specs where f_id=sch.f_dogid) 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
    //                 end vozmdogc,
    //                 case 
    //                   when sch.f_dogtype=2 then 
    //                     (select f_kod1cb from ".DBPref."specs where f_id=sch.f_dogid) 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=sch.f_dogid) 
    //                 end nvozmdogc,
    //                 ahd.f_clssum dsum,
    //                 case 
    //                   when sch.f_vozm=2 then 0
    //                   else ahd.f_clssum
    //                 end vozmsum,
    //                 case 
    //                   when sch.f_vozm=2 then ahd.f_clssum
    //                   else 0
    //                 end nvozmsum,
    //                 sch.f_vozm isvozm,sch.f_nds dnds,
    //                 round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2) dndssum,
    //                 case 
    //                   when sch.f_vozm=2 then 0
    //                   else round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2)
    //                 end vozmndssum,
    //                 case 
    //                   when sch.f_vozm=2 then round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2)
    //                   else 0
    //                 end nvozmndssum,
    //                 sch.f_status invst,sch.f_dt invdt
    //               from ".DBPref."acchist_docs ahd,".DBPref."schets sch
    //                 #,".DBPref."spec_invoices si 
    //               where 
    //                 #si.f_id=sch.f_operid and 
    //                 sch.f_operid>0 and sch.f_ismaininv=0 and sch.f_maininv=0 and sch.f_id=ahd.f_docid and 
    //                 ahd.f_doctype=1 and ahd.f_acchistid in (".$row['f_id'].")
    //               union 
    //               select 
    //                 sch.f_kod1c invkodc,
    //                 case 
    //                   when schd.f_dogtype=2 then 
    //                     case 
    //                       when schd.f_vozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=schd.f_dogid) 
    //                       else (select f_kod1cb from ".DBPref."specs where f_id=schd.f_dogid) 
    //                     end 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
    //                 end dogc,
    //                 case 
    //                   when schd.f_dogtype=2 then 
    //                     (select f_kod1cp from ".DBPref."specs where f_id=schd.f_dogid) 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
    //                 end vozmdogc,
    //                 case 
    //                   when schd.f_dogtype=2 then 
    //                     (select f_kod1cb from ".DBPref."specs where f_id=schd.f_dogid) 
    //                   else (select f_kod1c from ".DBPref."dogs where f_id=schd.f_dogid) 
    //                 end nvozmdogc,
    //                 sum(ahd.f_clssum) dsum,
    //                 sum(case 
    //                       when schd.f_vozm=2 then 0
    //                       else ahd.f_clssum
    //                     end) vozmsum,
    //                 sum(case 
    //                 	   when schd.f_vozm=2 then ahd.f_clssum
    //                 	   else 0
    //                     end) nvozmsum,
    //                 schd.f_vozm isvozm,sch.f_nds dnds,
    //                 sum(round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2)) dndssum,
    //                 sum(case 
    //                 	   when schd.f_vozm=2 then 0
    //                 	   else round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2)
    //                     end) vozmndssum,
    //                 sum(case 
    //                 	   when schd.f_vozm=2 then round(cast(ahd.f_clssum*(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)/(100+(select f_uslint from ".DBPref."spr where f_type=10 and f_num=sch.f_nds)) AS DECIMAL(15,2)),2)
    //                 	   else 0
    //                     end) nvozmndssum,
    //                 sch.f_status invst,sch.f_dt invdt
    //               from 
    //                 #".DBPref."acchist_docs ahdsi,
    //                 ".DBPref."acchist_docs ahd,".DBPref."schets sch,".DBPref."schets schd
    //                 #,".DBPref."spec_invoices si,".DBPref."spec_invoices ssi 
    //               where 
    //                 #si.f_id=schd.f_operid and 
    //                 schd.f_operid>0 and schd.f_ismaininv=0 and schd.f_maininv=sch.f_id and sch.f_maininv=0 and sch.f_ismaininv=1 and sch.f_id=ahd.f_docid and ahd.f_doctype=1 
    //                 #and ahdsi.f_doctype=3 and ahdsi.f_docid=ssi.f_id and si.f_id=ssi.f_id
    //                 and ahd.f_acchistid in (".$row['f_id'].") 
    //                 #and ahdsi.f_acchistid in (".$row['f_id'].")
    //                 group by sch.f_id,schd.f_vozm,schd.f_nds
    //              ";
    //      //echo "3.sql2:$sql2<br>";
    //      $res2    = $dbh->query($sql2);
    //      while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
    //        {
    //        $curcursoper = $row['f_cursoper'];
    //        $izvozm      = 0;
    //        if($izvozm<2){$izvozm=$row2['f_isvozm'];}
    //        $cjstr="\"doctype\":\"1\",\"invkodc\":\"".$row2['invkodc'].
    //               "\",\"dogc\":\"".$row2['dogc']."\",\"vozmdogc\":\"".$row2['vozmdogc']."\",\"nvozmdogc\":\"".$row2['nvozmdogc'].
    //               "\",\"dsum\":".$row2['dsum'].",\"vozmsum\":".$row2['vozmsum'].",\"nvozmsum\":".$row2['nvozmsum'].
    //               ",\"isvozm\":\"".$row2['isvozm']."\",\"dnds\":\"".$row2['dnds'].
    //               "\",\"dndssum\":".$row2['dndssum'].",\"vozmndssum\":".$row2['vozmndssum'].",\"nvozmndssum\":".$row2['nvozmndssum'].
    //               ",\"invst\":\"".$row2['invst']."\",\"kurs\":\"".$curcursoper."\",\"valnom\":\"".$row['valnominal']."\",\"invdt\":\"".substr($row2['invdt'],0,10)."\"";
    //
    //        if(strlen($cjstr)>0)
    //          {if(strlen($jstr)>0)
    //             {$jstr=$jstr.",";}
    //           $jstr=$jstr."{".$cjstr."}";}
    //        }
    //      $dtype     = -1;
    //      $needexpah = 0;
    //      }
    //    }
    //  }
    //echo "3.dtype:$dtype<br>needexpah:$needexpah<br>";
    //~sdid3281
    if($needexpah==1)
      {
      //~sdid3047
      //перебираем прикрепленные к выписке документы
      $sql1    = "select * from ".DBPref."acchist_docs where f_acchistid=".$row['f_id'];
      //if($_SESSION['loginid']==2)
      //  {echo "sql3_1: $sql1<br>";}
      $res1    = $dbh->query($sql1);
      //$jstr    = "";//sdid3047
      $izvozm  = 0;
      $ic      = 0;
      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
        {
        $dtype = $row1['f_doctype'];
        if($dtype==3)//операции
          {
          $sql2="select i.f_orgdivision 
                 from ".DBPref."spec_invoices i 
                 where i.f_id=".$row1['f_docid'];
          //if($_SESSION['loginid']==2)
          //  {echo "sql3_2: $sql2.<br>";}
          $res2 = $dbh->query($sql2);
          if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
            {
            if($row2['f_orgdivision']>0){$lorgdiv=$row2['f_orgdivision'];}
            }
          }
        //if($_SESSION['loginid']==2)
        //  {echo "3. dtype_1: $dtype<br>";}
        $cjstr = "";
        if($dtype==1)//ищем инфу о счетах если все связанные операции включены в счета //sdid3047
          {
          //sdid3281
          //sdid1896
          $sql2 = "select s.f_status invst,s.f_orgid,s.f_num,s.f_dt,s.f_kod1c,s.f_operid,s.f_vozm,s.f_ismaininv,s.f_maininv,o.f_kod1c okodc,c.f_kod1c ckodc,
                     #sdid3281
                     #s.f_sum,s.f_nds 
                     s.f_sum
                     ,case 
                       when s.f_ismaininv=0 and s.f_type=1 and s.f_operid>0 then 
                         ifnull((select f_nds from ".DBPref."spec_invoices where f_id=s.f_operid),s.f_nds)
                       #when s.f_ismaininv=1 and s.f_maininv=0 and s.f_type=1 and s.f_operid=0 then
                       else s.f_nds
                     end onds
                     #~sdid3281
                     ,case 
                       when s.f_dogtype=2 then 
                         case 
                           when s.f_vozm=1 then (select f_kod1cp from ".DBPref."specs where f_id=s.f_dogid) 
                           else (select f_kod1cb from ".DBPref."specs where f_id=s.f_dogid) 
                         end 
                       else (select f_kod1c from ".DBPref."dogs where f_id=s.f_dogid) 
                     end dogc 
                     #sdid3281
                     ,round(cast(ifnull(case 
                       when s.f_ismaininv=1 then (select sum(get_schet_ndssum(i.f_id)) from ".DBPref."schets i where i.f_ismaininv=0 and i.f_maininv=s.f_id) 
                       else get_schet_ndssum(s.f_id) 
                     end,0) AS DECIMAL(15,2)),2) ndssum
                     ,round(cast(ifnull(case 
                       when s.f_ismaininv=1 then (select sum(get_schet_vozmsum(i.f_id,".$row['f_id'].")) from ".DBPref."schets i where i.f_ismaininv=0 and i.f_maininv=s.f_id) 
                       else get_schet_vozmsum(s.f_id,".$row['f_id'].") 
                     end,0) AS DECIMAL(15,2)),2) vozmsum
                     ,round(cast(ifnull(case 
                       when s.f_ismaininv=1 then (select sum(get_schet_nvozmsum(i.f_id,".$row['f_id'].")) from ".DBPref."schets i where i.f_ismaininv=0 and i.f_maininv=s.f_id) 
                       else get_schet_nvozmsum(s.f_id,".$row['f_id'].") 
                     end,0) AS DECIMAL(15,2)),2) nvozmsum
                     ,round(cast(ifnull(case 
                       when s.f_ismaininv=1 then (select sum(get_schet_vozmndssum(i.f_id,".$row['f_id'].")) from ".DBPref."schets i where i.f_ismaininv=0 and i.f_maininv=s.f_id) 
                       else get_schet_vozmndssum(s.f_id,".$row['f_id'].") 
                     end,0) AS DECIMAL(15,2)),2) vozmndssum
                     ,round(cast(ifnull(case 
                       when s.f_ismaininv=1 then (select sum(get_schet_nvozmndssum(i.f_id,".$row['f_id'].")) from ".DBPref."schets i where i.f_ismaininv=0 and i.f_maininv=s.f_id) 
                       else get_schet_nvozmndssum(s.f_id,".$row['f_id'].") 
                     end,0) AS DECIMAL(15,2)),2) nvozmndssum
                     #~sdid3281
                     ,case 
                       when s.f_dogtype=2 then 
                         (select f_kod1cp from ".DBPref."specs where f_id=s.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=s.f_dogid) 
                     end vozmdogc 
                     ,case 
                       when s.f_dogtype=2 then 
                         (select f_kod1cb from ".DBPref."specs where f_id=s.f_dogid) 
                       else (select f_kod1c from ".DBPref."dogs where f_id=s.f_dogid) 
                     end nvozmdogc 
                   from ".DBPref."schets s,".DBPref."clients o,".DBPref."clients c 
                   where o.f_id=s.f_orgid and c.f_id=s.f_contrid and s.f_id=".$row1['f_docid'];
          //~sdid1896
          //echo $sql2."<br>";
          $res2 = $dbh->query($sql2);
          if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
            {
            if(strlen($row2['f_kod1c'])>0)
              {
              if($izvozm<2){$izvozm=$row2['f_vozm'];}
              $curcursoper = $row['f_cursoper'];
              //if($curcursoper!=1){$ccursoper=$ccursoper*valnominal;}
              //sdid1896
              $cjstr="\"doctype\":\"".$dtype."\",\"invkodc\":\"".$row2['f_kod1c'].
                     "\",\"dogc\":\"".$row2['dogc']."\",\"vozmdogc\":\"".$row2['vozmdogc']."\",\"nvozmdogc\":\"".$row2['nvozmdogc'].
                     "\",\"dsum\":".$row1['f_clssum'].",\"vozmsum\":".$row2['vozmsum'].",\"nvozmsum\":".$row2['nvozmsum'].
                     //",\"isvozm\":\"".$row2['f_vozm']."\",\"dnds\":\"".$row2['f_nds'].
                     ",\"isvozm\":\"".$row2['f_vozm']."\",\"dnds\":\"".$row2['onds'].//sdid3281
                     "\",\"dndssum\":".$row2['ndssum'].",\"vozmndssum\":".$row2['vozmndssum'].",\"nvozmndssum\":".$row2['nvozmndssum'].
                     ",\"invst\":\"".$row2['invst']."\",\"kurs\":\"".$curcursoper."\",\"valnom\":\"".$row['valnominal']."\",\"invdt\":\"".substr($row2['f_dt'],0,10)."\"";
              //~sdid1896
              //echo "cjstr: ".$cjstr."<br>";
              }
            else{$answ = $answ."Счет ".$row2['f_num']." от ".$row2['f_dt']." не выгружался в 1С<br>";$cjstr="";}
            }
          else{$answ = $answ."Не найдены данные о счетах";$cjstr="";}
          //~sdid3281
          }
        elseif($dtype==2)//закрывающие документы
          {$sql2="";}
        elseif($dtype==3)//операции
          {
          //if($_SESSION['loginid']==2)
          //  {echo "<br>ahtype = ".$row['f_ahtype']."<br>";}
          if($row['f_ahtype']==5)//Комиссия за вал перевод
            {
            $cjstr = "\"ahtype\":\"5\"";
            //if(strlen($cjstr)>0){if(strlen($jstr)>0){$jstr=$jstr.",";}$jstr=$jstr.$cjstr;}
            }
          //sdid1896
          //elseif($row['f_ahtype']==1)//Оплата по счету клиента
          //  {//собираем инфу по операциям спецификации
          //  $sql2="select 
          //           i.f_id ifid
          //           ,case when i.f_isvozm=1 then s.f_kod1cp else s.f_kod1cb end dogc 
          //           ,i.f_sum isum
          //           ,i.f_isvozm isvozm
          //           ,i.f_nds inds
          //           ,i.f_contrid,c.f_kod1c ckodc,c.f_id cfid,i.f_dogid,s.f_kod1cp dkodc
          //         from ".DBPref."spec_invoices i,".DBPref."specs s,".DBPref."clients c,".DBPref."dogs d 
          //         where c.f_id=d.f_contrid and d.f_id=s.f_dogid and s.f_id=i.f_specid and i.f_parenttype=2 and i.f_id=".$row1['f_docid'];
          //  //echo $sql2."<br>";
          //  $res2 = $dbh->query($sql2);
          //  //echo "cursoper:".$row['f_cursoper']."|kurs:".$row1['f_curs']."<br>";
          //  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
          //    {
          //    $curcursoper = $row['f_cursoper'];
          //    if(($row1['f_curs']!=$row['f_cursoper'])&&($row1['f_curs']!=1))
          //      {$curcursoper = $row1['f_curs'];}
          //    $invkodc = "";
          //    $invdt   = "";
          //    $invst   = 0;
          //    $sql3 = "select 
          //                ifnull(case when s.f_maininv>0 then ifnull(sa.f_kod1c,ifnull(sa.f_num,ifnull(s.f_kod1c,ifnull(s.f_num,'')))) 
          //                            else ifnull(s.f_kod1c,ifnull(s.f_num,'')) end,'') invkodc
          //               ,ifnull(case when s.f_maininv>0 then ifnull(sa.f_dt,ifnull(sa.f_dt,ifnull(s.f_dt,''))) else ifnull(s.f_dt,'') end,'') invdt
          //               ,ifnull(case when s.f_maininv>0 then sa.f_status else s.f_status end,0) invst
          //             from ".DBPref."schets s
          //             left join ".DBPref."schets sa on sa.f_id=s.f_maininv
          //             where s.f_type=1 and s.f_operid=".$row2['ifid'];
          //    //echo $sql3."<br>";
          //    $res3 = $dbh->query($sql3);
          //    if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
          //      {
          //      $invkodc = $row3['invkodc'];
          //      $invdt   = $row3['invdt'];
          //      $invst   = $row3['invst'];
          //      }
          //    //$cckodc = getClntInf(2,2,$row2['f_contrid'],0,$row2['f_orgid'],2);
          //    $dsum = $row1['f_clssum'];
          //    if($dsum==0){$dsum=$row2['isum'];}
          //    $ndssum = 0;
          //    if($row2['inds']==1){$ndssum = round($dsum*10/110,2);}
          //    if($row2['inds']==2){$ndssum = round($dsum*20/120,2);}
          //    //!ТабЧСч.ДоговорКонтрагента  - Справочники.ДоговорыКонтрагентов.НайтиПоКоду(usl.dogc);
          //    //!ТабЧСч.СчетНаОплату        - 
          //    //!ТабЧСч.СуммаПлатежа        - usl.dsum
          //    //!ТабЧСч.КурсВзаиморасчетов  - usl.kurs*usl.valnom
          //    //!ТабЧСч.СуммаВзаиморасчетов - Окр((Число(usl.dsum)/Число(usl.kurs)),2);
          //    //!ТабЧСч.СтавкаНДС           - usl.dnds
          //    //!ТабЧСч.СуммаНДС		 - usl.dsum*10/110;
          //    //!ТабЧСч.КурсВзаиморасчетов  - cursoper*valnom;
          //    $cjstr="\"doctype\":\"".$dtype.
          //        "\",\"dogc\":\"".$row2['dkodc'].
          //        "\",\"dsum\":\"".$dsum.
          //        "\",\"dnds\":\"".$row2['inds'].
          //        "\",\"dndssum\":\"".$ndssum.
          //        "\",\"isvozm\":\"".$row2['isvozm'].
          //        "\",\"invkodc\":\"".$invkodc.
          //        "\",\"invdt\":\"".$invdt.
          //        "\",\"invst\":\"".$invst.
          //        "\",\"kurs\":\"".$curcursoper.
          //        "\",\"valnom\":\"".$row['valnominal']."\"";
          //    //echo $cjstr."<br>";
          //    }
          //  }
          //~sdid1896
          elseif($row['f_ahtype']==6)//Комиссия за покупку валюты
            {
            $sql2="select i.f_isvozm,i.f_contrid,c.f_kod1c ckodc,c.f_id cfid,i.f_dogid,s.f_kod1cp dkodc,i.f_nds 
                   from ".DBPref."spec_invoices i,".DBPref."clients c,".DBPref."specs s,".DBPref."dogs d 
                   where c.f_id=d.f_contrid and d.f_id=s.f_dogid and s.f_id=i.f_specid and i.f_id=".$row1['f_docid'];
            //echo $sql2."<br>";
            $res2 = $dbh->query($sql2);
            if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
              {
              $cckodc = getClntInf(2,2,$row2['cfid'],0,$row['f_orgid'],2);
              $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row2['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",\"dnds\":\"".$row2['f_nds']."\"";
              }
            else{$answ = $answ."Не найдены данные Контрагнета";$cjstr="";}
            }
          elseif(($row['f_ahtype']==7)||($row['f_ahtype']==12))//Комиссия за УНК || Комиссия банка с НДС
            {
            //sdid3328
            $lcontrbic = $row['f_contrbic1C'];
            if((strlen($lcontrbic)==0)&&($row['f_contrid']>0))//если БИК получателя не указан, но есть контрагент
              {
              $sql2 = "select f_id from ".DBPref."categs where f_ctgtype=1 and f_objectid=".$row['f_contrid']." and f_valstr='11' and f_objecttype=2";
              $res2 = $dbh->query($sql2);
              if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
                {
                $sql2 = "select ct.f_valstr from ".DBPref."dogs d,".DBPref."categs ct 
                         where d.f_contrid=".$row['f_contrid']." and d.f_orgid=15 and d.f_dogtype=10 and d.f_valdog=643 and d.f_id=ct.f_objectid and ct.f_ctgtype=5
                         and ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and ctt.f_objectid=d.f_id),0)=0";
                $res2 = $dbh->query($sql2);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
                  {
                  if(strlen($row2['f_valstr'])==9)//нашли БИК
                    {$lcontrbic = $row2['f_valstr'];}
                  }
                }
              }
            $sql2="select f_contrid,f_kod1c 
                   from ".DBPref."dogs db,".DBPref."categs ct 
                   where db.f_dogtype=10 and ct.f_ctgtype=5 and db.f_id=ct.f_objectid and ct.f_valstr='$lcontrbic' and 
                     ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and 
                               ctt.f_objectid=db.f_id),0)=0 and
                     db.f_valdog=".$row['f_val']." and db.f_orgid=".$row['f_orgid'];
            //~sdid3328
            //if($_SESSION['loginid']==2)
            //  {echo $sql2."<br>";}
            $res2 = $dbh->query($sql2);
            if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
              {
              $cckodc = getClntInf(2,2,$row2['f_contrid'],0,$row['f_orgid'],2);
              $cjstr="\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row2['f_kod1c']."\",\"dsum\":\"".$row1['f_clssum']."\"";
              }
            else{$answ = $answ."Не найдены данные банка-контрагента";$cjstr="";}
            }
          elseif(
                 ($row['f_ahtype']==8)||                        //Проценты по депозиту
                 (($row['f_ahtype']==9)&&($row['f_val']!=643))||//Возврат поставщику в валюте
                 ($row['f_ahtype']==11)||                       //Комиссия банка 2
                 //($row['f_ahtype']==12)||                       //Комиссия банка с НДС
                 ($row['f_ahtype']==13)                         //Комиссия банка orig
                )
            {
            $cjstr="\"ckodc\":\"".$row['f_contrid1C']."\",\"dogc\":\"".$row['f_dogid1C']."\"";
            }
          elseif(($row['f_ahtype']==10)||                         //Оплата страховки
                 ($row['f_ahtype']==14)                           //Оплата контрагенту
                 ||(($row['f_ahtype']==17)&&($row['f_type']==1))  //Внутренний обобщенный платеж - Списание //sdid2549
                )
            {
            $cjstr="\"ckodc\":\"".$row['f_contrid1C']."\",\"dogc\":\"".$row['f_dogid1C']."\"";
            }
          //sdid2702
          elseif($row['f_ahtype']==15) //Продажа валюты
            {
            $sql3 = "select c.f_id cfid,c.f_kod1c ckodc,
                       (select getcbrate(f_val,f_ppdt) from ".DBPref."acchist where f_id=".$row['f_id'].") curscb,
                       (select f_sum from ".DBPref."acchist where f_id=".$row['f_id'].") sval,
                       (select f_cursoper from ".DBPref."acchist where f_id=".$row['f_id'].") curs,
                       case 
                         when ifnull((select count(*) from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                        d.f_orgid=".$row['f_orgid']." and 
                                        d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_id=".$row['f_id'].") and 
                                        ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                        d.f_id=ct.f_objectid),0)=1 then
                           ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                     d.f_orgid=".$row['f_orgid']." and 
                                     d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_id=".$row['f_id'].") and 
                                     ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                     d.f_id=ct.f_objectid),'')
                         else 
                           ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                     d.f_orgid=".$row['f_orgid']." and 
                                     d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_id=".$row['f_id'].") and 
                                     ct.f_ctgtype=5 and ct.f_valstr='".$row['f_contrbic1C']."' and 
                                     d.f_id=ct.f_objectid),'')
                       end dkodc
                     from ".DBPref."clients c where UPPER(c.f_cname) like (select UPPER(f_bankname) from ".DBPref."banks where f_bic='".$row['f_contrbic1C']."')";
            $res3 = $dbh->query($sql3);
            if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
              {
              $cckodc  = getClntInf(2,2,$row3['cfid'],0,$row['f_orgid'],2);
              $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row3['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",\"curscb\":\"".$row3['curscb']."\",\"sval\":\"".$row3['sval']."\",\"cursval\":\"".$row3['curs']."\"";
              }
            else{$answ = $answ."Не найдены данные Банка(1)";$cjstr="";}
            }
          elseif($row['f_ahtype']==16) //Рублевая часть продажи валюты
            {
            $sql3 = "select c.f_id cfid,c.f_kod1c ckodc,
                       (select getcbrate(f_val,f_ppdt) from ".DBPref."acchist where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) curscb,
                       (select f_sum from ".DBPref."acchist where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) sval,
                       (select f_cursoper from ".DBPref."acchist where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) curs,
                       (select f_clssum from ".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                        where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=".$row1['f_docid']." and 
                              lah.f_grnum='".$row['f_grnum']."' and lah.f_ppdt='".$row['f_ppdt']."' and lah.f_val<>643) svaldoc,
                       case 
                         when ifnull((select count(*) from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                        d.f_orgid=".$row['f_orgid']." and 
                                        d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) and 
                                        ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                        d.f_id=ct.f_objectid),0)=1 then
                           ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                     d.f_orgid=".$row['f_orgid']." and 
                                     d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) and 
                                     ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                     d.f_id=ct.f_objectid),'')
                         else 
                           ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                     d.f_orgid=".$row['f_orgid']." and 
                                     d.f_valdog=(select f_val from ".DBPref."acchist 
                                                    where f_grnum='".$row['f_grnum']."' and f_ppdt='".$row['f_ppdt']."' and f_val<>643) and 
                                     ct.f_ctgtype=5 and ct.f_valstr='".$row['f_contrbic1C']."' and 
                                     d.f_id=ct.f_objectid),'')
                       end dkodc
                     from ".DBPref."clients c where UPPER(c.f_cname) like (select UPPER(f_bankname) from ".DBPref."banks where f_bic='".$row['f_contrbic1C']."')";
            //if($_SESSION['loginid']==2)
            //  {echo $sql3."<br>";}
            $res3 = $dbh->query($sql3);
            if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
              {
              $cckodc  = getClntInf(2,2,$row3['cfid'],0,$row['f_orgid'],2);
              $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row3['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",".
                     "\"curscb\":\"".$row3['curscb']."\",\"sval\":\"".$row3['sval']."\",\"cursval\":\"".$row3['curs']."\",".
                     "\"cursvaldocs\":\"".$row1['f_curs']."\",\"svaldoc\":\"".$row3['svaldoc']."\"";
              }
            else{$answ = $answ."Не найдены данные Банка(2)";$cjstr="";}
            }
          //~sdid2702
          //sdid2549
          elseif(($row['f_ahtype']==17)&&($row['f_type']==0))//Внутренний обобщенный платеж - Зачисление
            {
            $sql2="select i.f_isvozm,i.f_contrid,c.f_kod1c ckodc,c.f_id cfid,i.f_dogid
                     ,case when i.f_isvozm=1 then s.f_kod1cp else s.f_kod1cb end dkodc
                     ,i.f_nds 
                   from ".DBPref."spec_invoices i,".DBPref."clients c,".DBPref."specs s,".DBPref."dogs d 
                   where d.f_id=s.f_dogid and s.f_id=i.f_specid and c.f_id=d.f_contrid and i.f_id=".$row1['f_docid'];
            //echo $sql2."<br>";
            $res2 = $dbh->query($sql2);
            if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
              {
              $cckodc = getClntInf(2,2,$row2['cfid'],0,$row['f_orgid'],2);
              $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row2['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",\"dnds\":\"".$row2['f_nds']."\",\"isvozm\":\"".$row2['f_isvozm']."\"";
              }
            else{$answ = $answ."Не найдены данные Контрагнета";$cjstr="";}
            }
          //~sdid2549
          else
            {
            $sql2="select i.f_isvozm,i.f_contrid,c.f_kod1c ckodc,i.f_dogid,d.f_kod1c dkodc,i.f_nds,d.f_orgid 
                   from ".DBPref."spec_invoices i,".DBPref."clients c,".DBPref."dogs d 
                   where c.f_id=i.f_contrid and d.f_id=i.f_dogid and i.f_id=".$row1['f_docid'];
            //if($_SESSION['loginid']==2)
            //  {echo $sql2."<br>";}
            $res2 = $dbh->query($sql2);
            if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
              {
              //echo $izvozm."<br>";
              //echo $row2['f_isvozm']."<br>";
              //echo $row['f_typeop1C']."<br>";
              //echo $row['f_val']."<br>";
              if($izvozm<2){$izvozm=$row2['f_isvozm'];}
              if((strcmp($row['f_typeop1C'],"Оплата поставщику")==0)&&($row['f_val']!=643))//валютный платеж
                {
                $cckodc = getClntInf(2,2,$row2['f_contrid'],0,$row2['f_orgid'],2);
                $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row2['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",\"dnds\":\"".$row2['f_nds']."\"";
                //echo $cjstr."<br>";
                }
              //sdid2335
              elseif(((strcmp($row['f_typeop1C'],"Перевод с другого счета")==0)||
                      (strcmp($row['f_typeop1C'],"Перевод на другой счет организации")==0)||
                      (strcmp($row['f_typeop1C'],"Оплата от покупателя")==0))&&($row['f_val']!=643))//конвертация
              //~sdid2335
                {
                //sdid3462
                $lcontrbic = $row['f_contrbic1C'];
                if((strlen($lcontrbic)==0)&&($row['f_contrid']>0))//если БИК получателя не указан, но есть контрагент и это рублевая часть покупки
                  {
                  $sql21 = "select f_id from ".DBPref."categs where f_ctgtype=1 and f_objectid=".$row['f_contrid']." and f_valstr='11' and f_objecttype=2";
                  $res21 = $dbh->query($sql21);
                  if($row21 = $res21->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
                    {
                    $sql21 = "select ct.f_valstr from ".DBPref."dogs d,".DBPref."categs ct 
                              where d.f_contrid=".$row['f_contrid']." and d.f_orgid=15 and d.f_dogtype=10 and d.f_valdog=643 and d.f_id=ct.f_objectid and ct.f_ctgtype=5
                                and ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and ctt.f_objectid=d.f_id),0)=0";
                    $res21 = $dbh->query($sql21);
                    if($row21 = $res21->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
                      {
                      if(strlen($row21['f_valstr'])==9)//нашли БИК
                        {$lcontrbic = $row21['f_valstr'];}
                      }
                    }
                  }
                //$sql3 = "select c.f_id cfid,c.f_kod1c ckodc,(select f_kod1c from ".DBPref."dogs where f_contrid=c.f_id and f_dogtype=10 and f_orgid=".$row['f_orgid']." and f_valdog=".$row['f_val'].") dkodc ".
                $sql3 = "select c.f_id cfid,c.f_kod1c ckodc,
                           case 
                             when ifnull((select count(*) from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                            d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                            d.f_id=ct.f_objectid),0)=1 then
                               ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                         d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                         d.f_id=ct.f_objectid),'')
                             else 
                               ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                         d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=5 and ct.f_valstr='$lcontrbic' and 
                                         d.f_id=ct.f_objectid),'')
                           end dkodc
                         from ".DBPref."clients c where UPPER(c.f_cname) like (select UPPER(f_bankname) from ".DBPref."banks where f_bic='$lcontrbic')";
                //~sdid3462
                //(select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct 
                // where d.f_contrid=c.f_id and d.f_dogtype=10 and d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=5 and 
                //   ct.f_valstr='".$row['f_contrbic1C']."' and d.f_id=ct.f_objectid and 
                //   ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and 
                //             ctt.f_objectid=d.f_id),0)=0) dkodc 
                //echo $sql3."<br>";
                $res3 = $dbh->query($sql3);
                if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                  {
                  $cckodc = getClntInf(2,2,$row3['cfid'],0,$row['f_orgid'],2);
                  $lcurscb = getCBRateDop($row['f_val'],$row['f_ppdt'],"{\"nodec\":\"1\"}");
                  //echo $lcurscb;
                  $cjstr="\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row3['dkodc']."\",\"dsum\":\"".$row1['f_clssum']."\",\"curscb\":\"".$lcurscb."\"";
                  }
                else{$answ = $answ."Не найдены данные Банка(3)";$cjstr="";}
                }
              else{$answ = $answ."Не обрабатываемый тип операции<br>";$cjstr="";}
              //echo "$answ<br>";
              }
            else
              {$answ = $answ."Операция для выгрузки не найдена";$cjstr="";}
            }
          }
        if(strlen($cjstr)>0){if(strlen($jstr)>0){$jstr=$jstr.",";}$jstr=$jstr."{".$cjstr."}";}
        //echo $ic.": <br>$dtype<br>".$cjstr."<br>".$jstr."<br>";
        $ic++;
        }
      //echo "answ: ".$answ."<br>";
      //echo "jstr: (".strlen($jstr).") ".$jstr."<br>";
      if(strlen($jstr)==0)
        {
        //echo $row['f_ahtype']."<br>";
        if($row['f_ahtype']==4)//рублевая часть конверсионки
          {
          //sdid3328
          $lcontrbic = $row['f_contrbic1C'];
          if((strlen($lcontrbic)==0)&&($row['f_contrid']>0))//если БИК получателя не указан, но есть контрагент и это рублевая часть покупки
            {
            //echo strlen($lcontrbic)."|".$row['f_contrid']."|1<br>";
            $sql21 = "select f_id from ".DBPref."categs where f_ctgtype=1 and f_objectid=".$row['f_contrid']." and f_valstr='11' and f_objecttype=2";
            //echo "$sql21<br>";
            $res21 = $dbh->query($sql21);
            if($row21 = $res21->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
              {
              $sql21 = "select ct.f_valstr from ".DBPref."dogs d,".DBPref."categs ct 
                       where d.f_contrid=".$row['f_contrid']." and d.f_orgid=15 and d.f_dogtype=10 and d.f_valdog=643 and d.f_id=ct.f_objectid and ct.f_ctgtype=5
                       and ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and ctt.f_objectid=d.f_id),0)=0";
              //echo "$sql21<br>";
              $res21 = $dbh->query($sql21);
              if($row21 = $res21->fetch(PDO::FETCH_ASSOC))//если контрагент является банком
                {
                if(strlen($row21['f_valstr'])==9)//нашли БИК
                  {$lcontrbic = $row21['f_valstr'];}
                }
              }
            }
          $sql3 = "select c.f_id cfid,c.f_kod1c ckodc,
                     case 
                       when ifnull((select count(*) from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                      d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                      d.f_id=ct.f_objectid),0)=1 then
                         ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                   d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=27 and ct.f_valstr='1' and ct.f_objecttype=3 and 
                                   d.f_id=ct.f_objectid),'')
                       else 
                         ifnull((select d.f_kod1c from ".DBPref."dogs d,".DBPref."categs ct where d.f_contrid=c.f_id and d.f_dogtype=10 and 
                                   d.f_orgid=".$row['f_orgid']." and d.f_valdog=643 and ct.f_ctgtype=5 and ct.f_valstr='$lcontrbic' and 
                                   d.f_id=ct.f_objectid),'')
                     end dkodc 
                   from ".DBPref."clients c where UPPER(c.f_cname) like (select UPPER(f_bankname) from ".DBPref."banks where f_bic='$lcontrbic')";
          //~sdid3328
          //echo $sql3."<br>";
          $res3 = $dbh->query($sql3);
          if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
            {
            $cckodc = getClntInf(2,2,$row3['cfid'],0,$row['f_orgid'],2);
            $cjstr  = "\"doctype\":\"".$dtype."\",\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row3['dkodc']."\",\"dsum\":\"".$row['f_sum']."\",\"curscb\":\"".getCBRate($row['f_val'],$row['f_ppdt'])."\"";
            }
          if(strlen($cjstr)>0){if(strlen($jstr)>0){$jstr=$jstr.",";}$jstr=$jstr.$cjstr;}
          }
        elseif(($row['f_ahtype']==7)||($row['f_ahtype']==12))//Комиссия за УНК || Комиссия банка с НДС
          {
          $sql3="select f_contrid,f_kod1c 
                 from ".DBPref."dogs db,".DBPref."categs ct 
                 where db.f_dogtype=10 and ct.f_ctgtype=5 and db.f_id=ct.f_objectid and ct.f_valstr='".$row['f_contrbic1C']."' and
                   ifnull((select count(*) from ".DBPref."categs ctt where ctt.f_ctgtype=27 and ctt.f_valstr='1' and ctt.f_objecttype=3 and 
                             ctt.f_objectid=db.f_id),0)=0 and
                   db.f_valdog=".$row['f_val']." and db.f_orgid=".$row['f_orgid'];
          //echo $sql3."<br>";
          $res3 = $dbh->query($sql3);
          if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
            {
            $cckodc = getClntInf(2,2,$row3['f_contrid'],0,$row['f_orgid'],2);
            $cjstr="\"ckodc\":\"".$cckodc."\",\"dogc\":\"".$row3['f_kod1c']."\"";
            }
          if(strlen($cjstr)>0){if(strlen($jstr)>0){$jstr=$jstr.",";}$jstr=$jstr.$cjstr;}
          }
        //sdid2744
        elseif($row['f_ahtype']==18)//Оплата поставщику
          {
          $cjstr="\"ldogtype\":\"".$row['ldogtype']."\"";
          if(strlen($jstr)>0){$jstr=$jstr.",";}$jstr=$jstr.$cjstr;
          }
        //~sdid2744
        }
      }//sdid3047
    //echo strlen($jstr)."_".$row['f_ahtype']."_123<br> __###___ <br>$jstr<br> __###___ <br>";
    if(strlen($jstr)>0)
      {
      if(m_strpos($jstr, "{")>=0)
        {$jstr = "[".$jstr."]";}
      else
        {$jstr = "[{".$jstr."}]";}
      $contrid1C = $row['f_contrid1C'];
      //echo $contrid1C."<br>";
      if(strlen($contrid1C)<3)
        {
        $contrid1C = getClntInf(2,2,$row['f_contrid'],0,$row['f_orgid'],2);
        }
      $slorgdiv = "";
      if($lorgdiv>0){$slorgdiv = "{\"orgdivnum\":\"".$lorgdiv."\"}";}
      $OP = getOPorgFrom1C($row['f_orgid'],$slorgdiv);
      //echo $contrid1C."<br>";
      //echo "sum:".$row['f_sum']."_val:".$row['val']."_dtype:".$dtype."_type:".$row['f_type']."_orgid1C:".$row['f_orgid1C']."_orgaccid1C:".$row['f_orgaccid1C']."_kod1C:".$row['f_kod1C']."_dt1C:".$row['f_dt1C']."_typeop1C:".$row['f_typeop1C']."_".$row['f_ntypeop1C']."_contrid1C:".$contrid1C."_cursoper:".$row['f_cursoper']."_izvozm:".$izvozm."_ahtype:".$row['f_ahtype']."_OP:".$OP."_valnominal:".$row['valnominal']."<br>jstr:<br>".$jstr."<br>";
      $curcursoper = $row['f_cursoper'];
      //if($curcursoper!=1){$ccursoper=$ccursoper*valnominal;}
      $answ = $answ."Код подразделения ".$OP."<br>";
      $retval = c1c_updAccHist($row['f_sum'],$row['val'],$dtype,$row['f_type'],$row['f_orgid1C'],$row['f_orgaccid1C'],$row['f_kod1C'],substr($row['f_dt1C'],0,10),$row['f_typeop1C'],$row['f_ntypeop1C'],$contrid1C,$curcursoper,$izvozm,$jstr,$row['f_ahtype'],$row['f_nbd1c'],$OP,$row['valnominal']);
      //echo "<br>";
      //var_dump($retval);
      //echo "<br>";
      //echo "<br>retval: $retval<br>";
      if(isset($retval['retval']))
        {
        if($retval['retval']=="true")
          {
          $c1res = 1;
          $answ  = $answ."Результат обработки в 1С: ".$retval['msg'];
          //echo $answ."<br>";
          //обновляем статус записи выписки на "Обновлен в 1С"
          $kai   = Array('curtbl'=>84,'curidx'=>$row['f_id'],'f_status'=>4);
          $kr    = json_decode(editRowTbl($kai));
          if($kr[2]>0){$answ=$answ."обновили статус выписки в системе<br>";}
          }
        else
          {$answ = $answ."Ошибка обновления в 1С: ".$retval['msg'];}
        }
      else
        {$answ = $answ."Ошибка обновления в 1С";}
      }
    else
      {
      $answ = $answ."Не выбраны записи для экспорта<br>";
      }
    $answ = $answ."</td></tr>";
    $a    = array($c1res,$answ);
    $retv = json_encode($a);
    return $retv;
    }

  //выгрузка одной записи выписки по ИД
  function expAccHist1DocIDTo1C($ahid)
    {
    //sdid3047
    try
    {
    $sql = "SELECT 
              (select f_name from ".DBPref."spr where f_type=72 and f_num=ah.f_type) dirtype,
              (select f_uslint from ".DBPref."spr where f_type=4 and f_num=ah.f_val) valnominal,
              (select f_dopprstr from ".DBPref."spr where f_type=4 and f_num=ah.f_val) val,ah.* 
           FROM ".DBPref."acchist ah 
           where ah.f_id=".$ahid;
    $dbh = dbconnect();
    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {$retv  = expAccHist1DocTo1C($row,0,$dbh);}
    else
      {$a    = array(0,"Документ для выгрузки не найден");
       $retv = json_encode($a);}
      }
    catch (\Throwable $e)
      {
      $a    = array(0,"Ошибка выполнения: ".get_throwable_msg($e));
      $retv = json_encode($a);
      }
    //~sdid3047
    return $retv;
    }
//~ // ФУНКЦИИ ПЕРЕНЕСЕНЫ ИЗ expAccHist1DocTo1C.php
  //Определить вид доходности операции по статье бюджета
  function operVidDoh($bdrarticle, $dbh)
    {
    $sql = "SELECT s84.f_num 
            FROM veda_spr s83,
                 veda_spr s84,
                 veda_spr s85,
		 veda_spr s86
            WHERE s86.f_num=".$bdrarticle." 
              and s86.f_type=86 
              and s85.f_type=85 
              and s84.f_type=84 
              and s83.f_type=83 
              and s86.f_dopprint=1
              and s86.f_isuse=1
              and s85.f_uslint=s83.f_num 
              and s85.f_dopprint=s84.f_num
              and s86.f_uslint=s85.f_num";

    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      return $row['f_num']; //1 - Доходная, 2 - Расходная
      }
    else {return 0;}
    }

  // Определить привязаны ли фин.документы к операции
  function operIsHaveFinDocs($operid, $dbh)
    {
      $sql = "select 
                 #si.f_specid,
		 #t.f_name opername,
		 ifnull((select 
		    case when aa.f_mainakt>0 
		         then (select count(a.f_id) from veda_akts a where a.f_id=aa.f_mainakt)
		         else count(aa.f_id)
			  end
		    from veda_akts aa 
		   where aa.f_operid=si.f_id
		 ),0) akts,
		 ifnull((select 
		    case when aa.f_mainakt>0 
		  	 then (select count(a.f_id) 
			         from veda_akts a,veda_akts_details ad1,veda_akts_details_opers ado1 
			        where ad1.f_aktid=aa.f_id 
			          and ado1.f_akts_detailsid=ad1.f_id
				  and ado1.f_operid=si.f_id
				  and a.f_id=aa.f_mainakt
			      )
			 else count(aa.f_id)
			  end
                   from veda_akts aa,veda_akts_details ad,veda_akts_details_opers ado 
                  where ad.f_aktid=aa.f_id 
		    and ado.f_akts_detailsid=ad.f_id 
		    and ado.f_operid=si.f_id
		 ),0) akts1,
		 ifnull((select 
		    case when ss.f_maininv>0 
		         then (select count(s.f_id) from veda_schets s where s.f_id=ss.f_maininv)
		         else count(ss.f_id)
			  end
		    from veda_schets ss 
		   where ss.f_operid=si.f_id
		 ),0) schets,
		 ifnull((select 
		    case when ss.f_maininv>0 
			 then (select count(s.f_id) 
			         from veda_schets s,veda_schets_details sd1,veda_schets_details_opers sdo1 
			        where sd1.f_schetid=ss.f_id 
				  and sdo1.f_schets_detailsid=sd1.f_id
				  and sdo1.f_operid=si.f_id
				  and s.f_id=ss.f_maininv
			      )
			 else count(ss.f_id)
			  end
                   from veda_schets ss,veda_schets_details sd,veda_schets_details_opers sdo 
                  where sd.f_schetid=ss.f_id 
		    and sdo.f_schets_detailsid=sd.f_id 
		    and sdo.f_operid=si.f_id
		 ),0) schets1,
                 ifnull((select count(s.f_id) 
                    from veda_acchist s,veda_acchist_docs d 
                   where s.f_id=d.f_acchistid 
		     and d.f_doctype=3 
		     and d.f_docid=si.f_id
                 ),0) acchist			 
             from veda_spec_invoices si where si.f_id=".$operid;   
             //from veda_spec_invoices si, veda_typeopers t where si.f_id=".$operid." and t.f_id=si.f_idoper";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
          if(($row['akts']+$row['akts1']+$row['schets']+$row['schets1']+$row['acchist']) > 0)
            {
              return 1; 
            }
        }
      else {return 0;}
    }

//~sdid 246

  //считаем плановый бюджет организаций за месяц указнного года
  function recalcBDRPlan($dbh,$bdry,$bdrm,$planfakt)
    {
    //обнуляем все расчетные строки плановых значений БДР за месяц указнного года
    $sql = "update ".DBPref."bdr set f_val".$planfakt."m".$bdrm."=0 
            where f_bdryear=".$bdry." and 
              f_bdrarticle in (select f_num from ".DBPref."spr where f_type=86 and f_num>=171 and f_dopprint>1 order by f_dopprint,f_num)";
    //echo $sql."<br>";
    $dbh->exec($sql);
    //обнуляем все строки плановых значений БДР за месяц указнного года для ГК 
    $sql = "update ".DBPref."bdr set f_val".$planfakt."m".$bdrm."=0 
            where f_bdryear=".$bdry." and f_orgid=1";
    $dbh->exec($sql);
    //считаем плановые суммы первого уровня для ГК
    $sql = "select b.f_bdryear,b.f_bdrarticle,sum(b.f_val".$planfakt."m".$bdrm.") bdrasum from ".DBPref."bdr b 
            where b.f_bdryear=".$bdry." and b.f_bdrarticle not in (select f_num from veda_spr where f_type=86 and f_num>=171 and f_dopprint>1) 
            group by b.f_bdrarticle";
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      $lbdr = new mBDR("bdr");
      $lbdr->fields['f_bdryear']                 = $bdry;
      $lbdr->fields['f_orgid']                   = 1;
      $lbdr->fields['f_bdrarticle']              = $row['f_bdrarticle'];
      $li = $lbdr->mInitAY();
      $lbdr->fields['f_val'.$planfakt.'m'.$bdrm] = $row['bdrasum'];
      $lbdr->addOrUpdate();
      }
    $sql = "select f_id from ".DBPref."clients where f_isourorg=1";
    //echo $sql."<br>";
    $res0 = $dbh->query($sql);
    while($row0 = $res0->fetch(PDO::FETCH_ASSOC))
      {
      $corgid = $row0['f_id'];
      //echo "corgid:".$corgid."<br>";
      $sql = "select f_num,f_namedop from ".DBPref."spr where f_type=86 and f_num>=171 and f_dopprint>1 order by f_dopprint,f_num";
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        $lbdr = new mBDR("bdr");
        $lbdr->fields['f_bdryear']      = $bdry;
        $lbdr->fields['f_orgid']        = $corgid;
        $lbdr->fields['f_bdrarticle']   = $row['f_num'];
        $li = $lbdr->mInitAY();
        //$lbdr->fields['f_valfm'.$bbdrm] = ;
        $vprc = "";
        $vst  = 1;
        $cfsm = substr($row['f_namedop'],0,1);
        if(strcmp($cfsm,"%")==0)
          {
          $vst  = 2;
          $znal = substr($row['f_namedop'],1,1);
          }
        else
          {$znal = substr($row['f_namedop'],0,1);}
        //$znal = substr($row['f_namedop'],0,1);
        $varr = explode("A",substr($row['f_namedop'],$vst));
        //$varr = explode("A",substr($row['f_namedop'],1));
        $lsql = "";
        foreach($varr as $value)
          {
          if(strlen($value)>0)
            {
            if(strlen($lsql)>0)
              {$lsql=$lsql.$znal;}
            //echo "value:".$value."<br>";
            $lsql=$lsql."ifnull((select f_val".$planfakt."m".$bdrm." from ".DBPref."bdr 
                                 where f_bdryear=".$bdry." and f_bdrarticle=".$value." and f_orgid=".$corgid."),0)";
            }
          }
        if($vst==2){$vprc="*100";}
        $sd = "select (".$lsql.")".$vprc." lsm from dual";
        //echo $sd."<br>";
        $res1 = $dbh->query($sd);
        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          {

          if($row1['lsm']>0)
            {
            $lbdr->fields['f_val'.$planfakt.'m'.$bdrm] = $row1['lsm'];
            $lbdr->addOrUpdate();
            }
          }
        }
      //echo "corgid:".$corgid." end<br>";
      }
    return 0;
    }

  //считаем плановый бюджет организаций за указанный год
  function recalcBDRPlanY($dbh,$bdry,$planfakt)
    {
    $km = 1;
    while($km<=12)
      {
      recalcBDRPlan($dbh,$bdry,$km,$planfakt);
      //echo "km=".$km."<br>";
      $km++;
      }
    return 0;
    }

  //sdid - 203
  //Сделать копию операции
  function copyOperation($operation_id, $dbh)
  {
    //sdid3313
    $loginid = 0;
    if(isset($_SESSION['loginid'])){$loginid = $_SESSION['loginid'];}
    $copy_query = "
      DROP TEMPORARY TABLE IF EXISTS veda_temp_table;
    CREATE TEMPORARY TABLE veda_temp_table SELECT * FROM veda_spec_invoices WHERE f_id = $operation_id;
    UPDATE veda_temp_table SET f_id = NULL;
      #UPDATE veda_temp_table JOIN (
      #  SELECT si.f_specid, max(si.f_num_oper)+1 AS max_num_oper
      #  FROM veda_spec_invoices si, veda_spec_invoices user_si
      #  WHERE si.f_specid=user_si.f_specid AND user_si.f_id=$operation_id
      #  ) AS si ON veda_temp_table.f_specid=si.f_specid
      #SET veda_temp_table.f_num_oper=max_num_oper,veda_temp_table.f_status=0,veda_temp_table.f_outbuhperiod=1,veda_temp_table.f_dttmcr=NOW();
      UPDATE veda_temp_table 
      SET f_num_oper=(select max(si.f_num_oper)+1 FROM veda_spec_invoices si where si.f_parenttype=2 and si.f_specid=(select f_specid from veda_temp_table)),
          f_status=0,f_outbuhperiod=1,f_dttmcr=NOW(),f_dttmupd=NOW(),f_userid=$loginid,f_useridupd=$loginid;
    INSERT INTO veda_spec_invoices SELECT * FROM veda_temp_table;";
    //sdid3313
    $dbh->exec($copy_query);
    return $dbh->lastInsertId();
  }
  //sdid1743
  //Добавить операцию в отчет агента
  function addOperInAgentrep($operid,$specid,$dbh)
    {
    $nrepid = 0;
    $sql = "select f_id from ".DBPref."agentreps where f_specid=:specid and f_status=0 ";
    $conn = $dbh->prepare($sql);
    $conn->bindParam(':specid', $specid);
    $conn->execute();
    if($row = $conn->fetch(PDO::FETCH_ASSOC))
      {$nrepid = $row['f_id'];}
    else
      {
      $add_data = Array('curtbl'=>286, "f_specid"=>$specid);
      $nres = json_decode(addRowTbl($add_data), true);
      if($nres[0]=="true"){$nrepid=$nres[2];}
      }
    if($nrepid>0)
      {
      $add_data = Array('curtbl'=>287, "f_agentrepid"=>$nrepid, "f_operid"=>$operid);
      addRowTbl($add_data);
      }
    }
  //~sdid1743

  //сформировать Корректировки маржи по операции закрытой спецификации
  function handle_adjustments($closing_document_id, $tblname, $dbh)
    {
    $table_id = 83; //акты
    if(strcmp($tblname,DBPref."akts_details_opers")==0)
      {
      $table_id = 184; //детализация актов по операциям
      }
    if($table_id == 184)
      {
      $sql = "SELECT s.f_operid, si.f_sum oper_sum, si.f_nds oper_nds,
                (SELECT orgs.f_sno FROM ".DBPref."clients orgs WHERE orgs.f_id=akts.f_orgid) organisation_taxation_system,
                d.f_nds akt_nds,
                case when s.f_sum=0 then (d.f_sum+d.f_ndssum) else s.f_sum end akt_sum, 
                specs.f_status spec_status_id, si.f_isvozm,specs.f_id sid
              from ".$tblname." s,".DBPref."akts_details d,".DBPref."spec_invoices si, ".DBPref."specs specs, ".DBPref."akts akts
              where d.f_id=s.f_akts_detailsid and si.f_id=s.f_operid AND si.f_specid=specs.f_id and d.f_aktid=akts.f_id and s.f_id=".$closing_document_id;
      }
    else
      {
      $sql = "SELECT s.f_operid, s.f_sum akt_sum, s.f_nds akt_nds, 
                (SELECT orgs.f_sno FROM ".DBPref."clients orgs WHERE orgs.f_id=s.f_orgid) organisation_taxation_system, 
                si.f_sum oper_sum, si.f_nds oper_nds, 
                specs.f_status spec_status_id, si.f_isvozm,specs.f_id sid
              FROM $tblname s, ".DBPref."spec_invoices si, ".DBPref."specs specs
              WHERE si.f_id=s.f_operid AND si.f_specid=specs.f_id AND s.f_id=".$closing_document_id;
      }    

    $res = $dbh->query($sql);
    if($row = $res->fetch(PDO::FETCH_ASSOC))
      {                
      if($row['f_operid']>0)
        {
        //$equal_nds = $row['akt_nds'] == $row['oper_nds'] ? 1 : 0; //одинаковые ндс у закр. документа и у операции
        //$equal_sums = $row['akt_sum'] == $row['oper_sum'] ? 1 : 0; //одинаковые суммы у закр. документа и у операции

        //if($equal_nds && $equal_sums)
        //  {
        //  $ki = Array('curtbl'=>35,'curidx'=>$row['f_operid'], 'f_nodoccalc'=>0);
        //  editRowTbl($ki);
        //  }
        //elseif($row['spec_status_id'] == 6 && (!$equal_nds || !$equal_sums))
        //  {
          //Создаём копии операции
          $cfopr = 0;
          //sdid1743
          //$sql = "select count(*) cnt from veda_categs where f_ctgtype=22 and f_objecttype=5 and f_valstr='0' and f_objectid=".$row['f_operid']." and f_valint=".$row['f_operid'];
          $sql = "select count(*) cnt from ".DBPref."spec_invoices_storno where f_operid=".$row['f_operid']." and f_operstornoid=".$row['f_operid'];
          //~sdid1743
          $resw = $dbh->query($sql);
          if($roww = $resw->fetch(PDO::FETCH_ASSOC))
            {$cfopr = $roww['cnt'];}
          if($cfopr==0)
            {
            $negative_sum_operation_id  = copyOperation($row['f_operid'], $dbh);
            //обновляем статус через запрос, т.к. иначе не пролезем по правам на изменения
            //sdid3313
            $llloginid = 0;
            if(isset($_SESSION['loginid'])){$llloginid = $_SESSION['loginid'];}
            $sql = "update ".DBPref."spec_invoices set f_status=4,f_dttmupd=NOW(),f_userid=$llloginid where f_id=".$row['f_operid'];
            //~sdid3313
            $dbh->exec($sql);
            }
          $closing_document_operation_id = copyOperation($row['f_operid'], $dbh);

          $current_date = date('Y-m-d H:i:s');
          $add_nds = ($row["f_isvozm"] == 2) && (($row["akt_nds"] == 0)||($row["akt_nds"] == 3)) && ($row['organisation_taxation_system'] == 1) ? 1 : 0;

          if($cfopr==0)
            {$sopr = -1*$row['oper_sum'];
             $negative_sum_operation_edit_data  = Array('curtbl'=>35,'curidx'=>$negative_sum_operation_id,     'f_nodoccalc'=>1, 'f_dttmsrok'=>$current_date, 
                                                        'f_dttmcr'=>$current_date, 'f_outbuhperiod'=>1, 'f_sum'=>$sopr,
                                                        'f_status'=>11);}
          $closing_document_operation_edit_data = Array('curtbl'=>35,'curidx'=>$closing_document_operation_id, 'f_nodoccalc'=>0, 'f_dttmsrok'=>$current_date, 
                                                        'f_dttmcr'=>$current_date, 'f_outbuhperiod'=>1, 'f_sum'=>$row['akt_sum'], 'f_addnds'=>$add_nds, 
                                                        'f_status'=>11);
          //sdid1743
          //$add_categories_data = Array('curtbl'=> 214, "f_ctgtype"=>22, "f_objectid"=>0, "f_valstr"=>0, "f_valint"=>$row['f_operid'], 
          //                             "f_objecttype"=>5);
          $add_categories_data = Array('curtbl'=> 289, "f_operid"=>$row['f_operid']);
          //~sdid1743
          //привязываем основную операцию
          if($cfopr==0)
            {//sdid1743
             //$add_categories_data["f_objectid"] = $row['f_operid'];
             //$add_categories_data["f_valstr"]   = 0;
             $add_categories_data["f_operstornoid"] = $row['f_operid'];
             $add_categories_data["f_stornotype"]   = 1;
             //~sdid1743
             addRowTbl($add_categories_data);}

          //привязываем копию операции с отрицательной суммой
          if($cfopr==0)
            {
            //sdid1743
            //$add_categories_data["f_objectid"] = $negative_sum_operation_id;
            //$add_categories_data["f_valstr"]   = 1;
            $add_categories_data["f_operstornoid"] = $negative_sum_operation_id;
            $add_categories_data["f_stornotype"]   = 2;
            //~sdid1743
            $lk = json_decode(addRowTbl($add_categories_data), true);
            //if($lk[0]=="true")
            //  {addOperInAgentrep($negative_sum_operation_id,$row['sid'],$dbh);}
            }

          //привязываем копию операции с суммой закр. документа
          //sdid1743
          //$add_categories_data["f_objectid"] = $closing_document_operation_id;
          //$add_categories_data["f_valstr"]   = 2;
          $add_categories_data["f_operstornoid"] = $closing_document_operation_id;
          $add_categories_data["f_stornotype"]   = 3;
          //~sdid1743
          $lk = json_decode(addRowTbl($add_categories_data), true);
          //if($lk[0]=="true")
          //  {addOperInAgentrep($closing_document_operation_id,$row['sid'],$dbh);}

          //Приявзываем закр. документ к копии операции с суммой закр. документа
          editRowTbl(Array('curtbl'=>$table_id, "curidx"=>$closing_document_id, "f_operid"=>$closing_document_operation_id), 
                     Array("only_document_change"=>1));

          if($cfopr==0)
            {editRowTbl($negative_sum_operation_edit_data);}
          editRowTbl($closing_document_operation_edit_data);
          //sdid3313, считаем, что спецификация закрыта и скопированные операции нужно поместить в корректировку, если ее нет или она закрыта, то создать новую
          if($row['sid']>0)
            {
            $corridtoadd = 0;
            $sql = "select ifnull(max(f_num),0) num,ifnull(f_id,0) fid from ".DBPref."corrects where f_specid=".$row['sid']." and f_status<>3 and f_num>0";
            $resnc = $dbh->query($sql);
            if($rownc = $resnc->fetch(PDO::FETCH_ASSOC))
              {$corridtoadd=$rownc['fid'];}
            if($corridtoadd==0)
              {
              $sql = "select (ifnull(max(f_num),0)+1) num from ".DBPref."corrects where f_specid=".$row['sid']." and f_num>0";
              $resnc = $dbh->query($sql);
              if($rownc = $resnc->fetch(PDO::FETCH_ASSOC))
                {
                $add_newcorr_data = Array('curtbl'=> 290,'f_specid'=>$row['sid'],'f_num'=>$rownc['num'],'f_status'=>0);
                $add_newcorr_res  = json_decode(addRowTbl($add_newcorr_data), true);
                if($add_newcorr_res[0]=="true"){$corridtoadd=$add_newcorr_res[2];}
                }
              }
            if($corridtoadd>0)
              {
              if($cfopr==0)
                {
                if(isset($negative_sum_operation_id))
                  {
                  if($negative_sum_operation_id>0)
                    {
                    $add_newcorroper_data = Array('curtbl'=> 291,'f_correctid'=>$corridtoadd,'f_correctoperid'=>$negative_sum_operation_id);
                    json_decode(addRowTbl($add_newcorroper_data), true);
                    }
                  }
                }
              if(isset($closing_document_operation_id))
                {
                if($closing_document_operation_id>0)
                  {
                  $add_newcorroper_data = Array('curtbl'=> 291,'f_correctid'=>$corridtoadd,'f_correctoperid'=>$closing_document_operation_id);
                  json_decode(addRowTbl($add_newcorroper_data), true);
                  }
                }
              }
            }
          //~sdid3313
        //  }
        //elseif(($row['spec_status_id'] == 8 || $row['spec_status_id'] == 17) && (!$equal_nds || !$equal_sums))
        //  {
        //  $ki = Array('curtbl'=>35,'curidx'=>$row['f_operid'], 'f_nodoccalc'=>0);
        //  editRowTbl($ki, $extra_data_hash=array('close_document_id'=>$closing_document_id, 'table_id'=>$table_id));
        //  }
        }
      }
    }
    //~sdid - 203

  //sdid - 353
  //Обнулить пункт "Расчёт поставки без закрывающих документов" у операции
  function reset_nodoccalc($operation_id, $closing_document_hash, $dbh)
  {
  $operation_query = "SELECT typeopers.f_c1doctype, spec_invoices.f_nds oper_nds, spec_invoices.f_sum oper_sum
                      FROM ".DBPref."typeopers typeopers, ".DBPref."spec_invoices spec_invoices
                      WHERE spec_invoices.f_id=$operation_id AND spec_invoices.f_type_oper=typeopers.f_id";
  $response = $dbh->query($operation_query);
  if($operation_row = $response->fetch(PDO::FETCH_ASSOC))
    {
    //sdid - 203
    if(($closing_document_hash['sum'] != $operation_row['oper_sum']) || ($closing_document_hash['nds'] != $operation_row['oper_nds']))
      {return;}
    //~sdid - 203

    if($operation_row['f_c1doctype'] == 3)
      {
      $ki = Array('curtbl'=>35,'curidx'=>$operation_id, 'f_nodoccalc'=>0);
      editRowTbl($ki);
      }
    } 
  }
  //~sdid - 353

  //добавить запись в таблицу
  function addRowTbl($ik,$djstr="")
    {
    $wdopmes = 0;
    $ansn    = "";
    $oid = 0;
    if(strlen($djstr)>0)
      {
      $opars = json_decode($djstr,true);
      if(isset($opars['oid']))
        {$oid = $opars['oid'];}
      }
    $answ   = "";
    $retval = "";
    $curtbl = 0;
    if(isset($ik['curtbl']))
      {$curtbl = $ik['curtbl'];}
    //echo $curtbl;
    if($curtbl>0)
      {
      $candel=1;
      $errmsg="";
      //echo $curtbl;
      //подключаемся к базе
      $dbh = dbconnect();
      //!!!проверяем права на выполнение добавления
      //$_SESSION['loginid']
      $sql = "select f_tablename from ".DBPref."menu_all where f_id=".$curtbl;
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        if(!isset($redirect_uri))
          {$redirect_uri = redirect_uri;}
        $tblname = $row['f_tablename'];
        //echo $tblname;
        $strnames  = "";
        $strvalues = "";
        $curvalue  = "";
         //~1081
        if($curtbl==205)//Перед добавлением - Касса. Подотчет
          {
          if(isset($ik['f_numdt']))
            {
            if($ik['f_numdt'] == 1) 
              {
              $dt = " and csh.f_dt='".$ik['f_dt']."'";
              $sql = "select ifnull(max(csh.f_numdt)+1,1) numdt from veda_cash_details csh where csh.f_userid=".$ik['userid']." ".$dt;
              $res1 = $dbh->query($sql);
              if ($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ik['f_numdt'] = $row1['numdt'];
                }
              }
            }
          }
            //~1081
        //while(list($key, $value) = each($ik)) 
        if(($curtbl==14)||($curtbl==48)||($curtbl==127)||($curtbl==133)||($curtbl==134)||($curtbl==138))//Перед добавлением - Договоры
          {
          // sdid 1776
          //sdid 2373
          //$sql_dog = "select f_typecode from ".DBPref."client_codes where f_typecode in (1,2) and f_contrid=".$ik['orgid'];
          $sql_dog = "select cc.f_typecode from ".DBPref."client_codes cc, ".DBPref."clients c 
                       where cc.f_typecode in (1,2) and cc.f_contrid=".$ik['orgid']." and c.f_noexp1c=0 and c.f_id=".$ik['contrid'];
          //~sdid 2373
          $conn_dog = $dbh->query($sql_dog);
          if($row_dog = $conn_dog->fetch(PDO::FETCH_ASSOC))
            {
            $contrid = getClntInf(2, 2, $ik['contrid'], 0, $ik['orgid'], 2);
            $contrlen = 0;
            if(is_string($contrid))
              {$contrlen = strlen($contrid);}
            if($contrlen === 0)
              {
              $bd1c = intval($row_dog['f_typecode']);
              $bd1c_name = "";
              if($bd1c > 0)
                {
                $sql_bdname = "SELECT f_name FROM " . DBPref . "spr WHERE f_type=110 AND f_num='" . $bd1c . "'";
                $conn_bdname = $dbh->query($sql_bdname);
                if($res_bdname = $conn_bdname->fetch(PDO::FETCH_ASSOC))
                  {$bd1c_name = $res_bdname['f_name'];}
                }
              $export_result = json_decode(export_client_to_1c($dbh, $bd1c, $ik['contrid']), true);
              if($export_result[0] === true)
                {
                $wdopmes=2;
                $ansn = $export_result[1]." - ".$bd1c_name;
                } 
              else
                {
                $retval = "[false,\"Ошибка экспортирования в базу 1С - " . $bd1c_name . "<br>" . $export_result[1] . "Сохранение договора запрещено<br>\"]";
                return $retval;
                }
              }
            }
          // ~ sdid 1776
          if(isset($ik['dogtype']))//делаем автонумерацию для клиентского договора пользования ктк услуги тп
            {if($ik['dogtype']==1)
              {if(isset($ik['subtype']))
                {if(($ik['subtype']==5)||($ik['subtype']==3))
                  {if(isset($ik['f_dogname']))
                    {if(strlen($ik['f_dogname'])==0)
                      {if(isset($ik['orgid']))
                        {if($ik['orgid']>0)
                          {
                          if($ik['subtype']==5)
                            {
                            $sql = "select concat(o.f_abbr,'-КТК/',LPAD((ifnull(max(CONVERT(SUBSTRING(d.f_dogname,8),UNSIGNED INTEGER)),0)+1),4,'0')) nextnumdog 
                                  from veda_dogs d,veda_clients o 
                                  where o.f_id=d.f_orgid and d.f_dogtype=1 and d.f_subtype=5 and d.f_dogname like concat(o.f_abbr,'-КТК/%') and 
                                    length(d.f_dogname)=length('ВА-КТК/0000') and o.f_id=".$ik['orgid'];
                            }
                          elseif($ik['subtype']==3)
                            {
                            $sql = "select concat(o.f_abbr,'-ТП/',LPAD((ifnull(max(CONVERT(SUBSTRING(d.f_dogname,7),UNSIGNED INTEGER)),0)+1),4,'0')) nextnumdog 
                                  from veda_dogs d,veda_clients o 
                                  where o.f_id=d.f_orgid and d.f_dogtype=1 and d.f_subtype=3 and d.f_dogname like concat(o.f_abbr,'-ТП/%') and 
                                    length(d.f_dogname)>=length('ВА-ТП/000') and o.f_id=".$ik['orgid'];
                            }
                          $res1 = $dbh->query($sql);
                          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                            {$ik['f_dogname']=$row1['nextnumdog'];}
                          }
                        }
                      }
                    }
                  }
                //sdid 714
                //sdid 821  
                //elseif(($ik['subtype']==0)) //делаем автонумерацию для Агентского клиентского договора 
                elseif(($ik['subtype']==0)||($ik['subtype']==1)) //делаем автонумерацию для Агентского клиентского договора + договор с подтипом TEO
                  {if(isset($ik['f_dogname']))
                    {if(strlen($ik['f_dogname'])==0)
                      {if(isset($ik['orgid']))
                        {if($ik['orgid']>0)
                          {
                          ////sdid 821  
                          /*    $sql = "select concat(o.f_invmask,LPAD((ifnull(max(CONVERT(SUBSTRING(d.f_dogname,4),UNSIGNED INTEGER)),0)+1),4,'0')) nextnumdog 
                                      from veda_dogs d,veda_clients o 
                                      where o.f_id=d.f_orgid 
                                      and d.f_dogtype=1 
                                      and d.f_subtype=0 
                                      and d.f_dogname like concat(o.f_invmask,'%') 
                                      and length(d.f_dogname)=length('ВА-0000') 
                                      and o.f_id=".$ik['orgid'];
                         */
                              $sql = "select concat(o.f_invmask,LPAD((ifnull(max(CONVERT(SUBSTRING(d.f_dogname,4),UNSIGNED INTEGER)),0)+1),4,'0')) nextnumdog 
                                      from veda_dogs d,veda_clients o 
                                      where o.f_id=d.f_orgid 
                                      and d.f_dogtype=1 
                                      and (d.f_subtype=0 or d.f_subtype=1) 
                                      and d.f_dogname like concat(o.f_invmask,'%') 
                                      and length(d.f_dogname)=length('ВА-0000') 
                                      and o.f_id=".$ik['orgid'];
                //~sdid 821

                            $res1 = $dbh->query($sql);
                            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                              {$ik['f_dogname']=$row1['nextnumdog'];}
                          }
                        }
                      }
                    }
                  }
                //~sdid 714
                }
              }
            //sdid 715
            elseif($ik['dogtype']==3)//делаем автонумерацию для валютного контракта
              {if(isset($ik['orgid']) && isset($ik['bankid']))
                {if(($ik['orgid']>0) && ($ik['bankid']>0))
                  {if(isset($ik['f_dogname']))
                    {if(strlen($ik['f_dogname'])==0)
                      {
                      $teo = "";
                      if($ik['subtype']==1) // При выборе Тип: валютный, Подвид договора: транспортно-экспедиционное обслуживание, к номеру добавляется буква T
                        {$teo = "-T";}
                      $orgcode = "";
                      switch ($ik['orgid'])
                        {
                        case 15: $orgcode="";    //ВЭД Агент
                                 break;  
                        //sdid2420
                        //case 30: $orgcode="-VC"; //ВЭД Консалт
                        case 30: $orgcode="-VE"; //ВЭД Экспедиция
                        //~sdid2420
                                 break;  
                        case 31: $orgcode="-VS"; //ВЭД Сервис
                                 break;  
                        }
                      $bankcode = "";
                      switch ($ik['bankid'])
                        {
                        case 2520: $bankcode="";      //Левобережный
                                 break;  
                        case 4175: $bankcode="-UNI";  //Юникредит
                                 break;  
                        case 3186: $bankcode="-AB";   //Альфа-Банк
                                 break;  
                        case 2723: $bankcode="-SB";   //Сбер
                                 break;  
                        case 3329: $bankcode="-SB";   //Сбер
                                 break;  
                        }
                      //sdid 1285
                      if($ik['orgid']==31) {$bankcode = "";} //ВЭД Сервис - код банка не прописываем
                      //~sdid 1285
                      $sql = "SELECT LPAD((ifnull(max(convert(digits(SUBSTR(d.f_dogname,1,4)),UNSIGNED INTEGER)),0)+1),4,'0')  nextnumdog 
                              FROM veda_dogs d 
                              where d.f_dogtype=3 and d.f_orgid=".$ik['orgid']." and LENGTH(digits(d.f_dogname))<=4 and d.f_id<>2141 and d.f_id<>2143";

                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {$ik['f_dogname']=$row1['nextnumdog'].$orgcode.$bankcode.$teo;}
                      }
                    }
                  }
                else
                  {
                  $candel = -1;
                  $errmsg = "Обязательно должны быть указаны Организация и Банк учета договора. ";//sdid2530
                  }
                }
              }
            //~sdid 715
            //sdid 799
            elseif($ik['dogtype']==6)  //Делаем автонумерацию для типа договора "транспортно/экспедиционные услуги"
              {if(isset($ik['subtype']))
                {if(($ik['subtype']==6)) //С ПОДВИДОМ "ТЭО КТК"
                  {if(isset($ik['f_dogname']))
                    {if(strlen($ik['f_dogname'])==0)
                      {
                        $sql = "select (ifnull(max(CONVERT(s.subdognum,UNSIGNED INTEGER)),0)+1) nextnumdog from
                                (
                                select d.f_dogname subdognum, 
                                       d.f_dogname REGEXP '^[0-9]+$' as isnumeric 
                                from veda_dogs d 
                                where d.f_dogtype=6 and d.f_subtype=6
                                ) s
                                where s.isnumeric=1";

                        $res1 = $dbh->query($sql);
                        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {$ik['f_dogname']=$row1['nextnumdog'];}
                      }
                    } 
                  }
                }
              }
            //~sdid 799
            }
          }
        //sdid 694
        //elseif(($curtbl==13)||($curtbl==27)||($curtbl==55)||($curtbl==60))//Перед добавлением в Клиенты/Контрагенты проверяем ИНН и КПП
        elseif(strcmp($tblname,DBPref."clients")==0) //Перед добавлением - Клиенты/Контрагенты проверяем ИНН и КПП
          {
          if(isset($ik['f_inn'])) // Если у добавляемого Клиента ЮЛ в форму внесен ИНН и номер больше либо равно 10, проверяем наличие этого ИНН в базе клиентов/контрагентов
            {
            if(strlen($ik['f_inn'])>=10)
              {
                // если ещё КПП ввели, ищем по ИНН и КПП
                $kppstr = "";
                if(isset($ik['f_kpp'])) //Если заполнен и КПП в форме ввода                   
                  {if(strlen($ik['f_kpp'])>=9) //Если заполнен и КПП в форме ввода и кол-во символов не меньше 9                  
                      {$kppstr = " and c.f_kpp=".$ik['f_kpp'];}
                  }
                $sql  = "select c.f_cname cname, c.f_inn innnum, ifnull(c.f_kpp,'') kppnum from ".DBPref."clients c where LENGTH(c.f_inn)>=10 and c.f_inn=".$ik['f_inn'].$kppstr;
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                    if(isset($ik['f_kpp'])) //Если заполнен и КПП в форме ввода                   
                      {if(strlen($ik['f_kpp'])>0) //Если заполнен и КПП в форме ввода                   
                        {
                          if((strcmp($row1['kppnum'], $ik['f_kpp'])==0) && (strcmp($row1['innnum'], $ik['f_inn'])==0)) // Если и ИНН и КПП совпадают, не даём сохранить Клиента/Контрагента
                            {
                              $candel = -1;
                              $errmsg = "В системе уже присутствует клиент '".$row1['cname']."' с такими ИНН ".$row1['innnum']." и КПП ".$row1['kppnum'].". ";//sdid2530
                            }
                        }
                      else //Если заполнен только ИНН
                        {
                          if(strcmp($row1['innnum'], $ik['f_inn']) == 0) // Если ИНН совпадают, просим уточнить КПП
                            {
                              $candel = -1;
                              $errmsg = "В системе присутствует клиент '".$row1['cname']."' с таким ИНН, уточните КПП. ИНН ".$row1['innnum'].". ";//sdid2530
                            }
                        }
                      }
                    else //Если заполнен только ИНН, но есть isset f_kpp
                      {
                          if(strcmp($row1['innnum'], $ik['f_inn']) == 0) // Если ИНН совпадают, просим уточнить КПП
                            {
                              $candel = -1;
                              $errmsg = "В системе присутствует клиент '".$row1['cname']."' с таким ИНН, уточните КПП. ИНН ".$row1['innnum'].". ";//sdid2530
                            }
                      }
                  }
              }
            }
          //sdid 2669
          if(isset($ik['f_nalognumber'])) // уникальность "Налоговый номер ин.контрагента", страна не Russia //sdid 2669
            {
            if(isset($ik['country']))
              {
              if(strlen($ik['country'])!=0)
                {
                if($ik['country']!=1)
                  {
                  if(strlen($ik['f_nalognumber'])>0)
                    {
                    $sql = "select f_id from veda_clients where f_nalognumber like '".$ik['f_nalognumber']."'";
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC)) 
                      {$candel = -1;
                       $errmsg = "Этот уникальный Налоговый номер иностранного контрагента уже связан с другим контрагентом в системе. ".$ik['f_nalognumber'];
                      }
                    }
                  else
                    {
                    $candel = -1; 
                    $errmsg = "Необходимо ввести Налоговый номер иностранного контрагента";
                    }
                  }
                }
              }
            }
          //~sdid 2669
          }
        //~sdid 694
        elseif($curtbl==15)//Перед добавлением - спецификации/заявки
          { // FD-107
          //print_r($ik);
          //sdid - 847
          //if(isset($ik['f_dttoclnt'])){if(strlen($ik['f_dttoclnt'])>0){$ik['f_dtaddpergruz']=$ik['f_dttoclnt'];}}
          //    sdid 1429      if(isset($ik['f_dttoclnt'])){if(strlen($ik['f_dttoclnt'])>0){$ik['f_dtaddpergruz']=date("Y.m.d");}}
          //~sdid - 847

          // sdid 1448
          if(isset($ik['comisprep']) && isset($ik['f_dtprepcom'])){if((strcmp($ik['comisprep'],"true")===0) && strlen($ik['f_dtprepcom'])==0) {$ik['f_dtprepcom']=date('Y.m.d H:i:s', time());}}
          // ~sdid 1448
          $cbuhid  = "";
          $coperid = "";
          if(isset($ik['buhid'])){$cbuhid=$ik['buhid'];}
          if(isset($ik['operid'])){$coperid=$ik['operid'];}
          if(strlen($coperid)==0 || strlen($cbuhid)==0 || $coperid==0 || $cbuhid==0)
            {
            $ldogid  = 0;
            if(isset($ik['dogid'])){$ldogid  = $ik['dogid'];}
            elseif(isset($ik['f_dogid'])){$ldogid  = $ik['f_dogid'];}
            if($ldogid>0)
              {
              $contacts_sql = "select c.f_user, c.f_buhid, c.f_id, d.f_contrid, d.f_id, cl.f_contactid, cl.f_id 
                               from ".DBPref."contacts c, ".DBPref."dogs d, ".DBPref."clients cl 
                               where d.f_id=".$ldogid." and cl.f_id=d.f_contrid and c.f_id=cl.f_contactid";
              //echo $contacts_sql."<br>";
              $contacts_res = $dbh->query($contacts_sql);
              if($contacts_row = $contacts_res->fetch(PDO::FETCH_ASSOC))
                {
                if(strlen($coperid)==0 || $coperid==0)
                  {$ik['operid'] = $contacts_row['f_user'];}
                if(strlen($cbuhid)==0 || $cbuhid==0)
                  {$ik['buhid'] = $contacts_row['f_buhid'];}
                }
              }
            }
          //sdid2390
          $parentspecid = 0;
          $extra_specid = 0;
          if(isset($ik['parentspecid'])){$parentspecid=$ik['parentspecid'];}
          if(isset($ik['f_is_extra_spec'])){if((boolean)json_decode(strtolower($ik['f_is_extra_spec'])) === true){$extra_specid=1;}}
          if(($parentspecid>0)&&($extra_specid>0))
            {
            $buh_sql = "select f_buhid from ".DBPref."specs where f_id=:parentspecid";
            $buh_res = $dbh->prepare($buh_sql);
            $buh_res->bindParam(':parentspecid',$parentspecid,PDO::PARAM_INT);
            $buh_res->execute();
            if($buh_row = $buh_res->fetch(PDO::FETCH_ASSOC))
              {
              $ik['buhid'] = $buh_row['f_buhid'];
              }
            }
          //~sdid2390
          //sdid 2084 tz2
          if(isset($ik['todog']))
            {
            if($ik['todog']==0) // Если в поле "Услуги ТО" выбрано значение по-умолчанию (0), не даем сохранить спецификацию
              {
              $candel = -1;
              //$errmsg = "Необходимо выбрать одно из значений поля \"Услуги ТО\": \"Нет ТО\",\"Текущая спецификация\", либо Договор ВА-ТП";
              $errmsg = "Необходимо выбрать одно из значений поля Договор ТО: Нет ТО, Текущий договор по спецификации, либо выбрать Договор ВА-ТП. ";//sdid2530
              }
            }
          //sdid 2084 tz2 2
          // Если выставлена галка Уведомить специалиста ТО
          if(isset($ik['uvedto']))
            {
            //echo " | uvedto=".$ik['uvedto']." | todog=".$ik['todog']." | ";
            if($ik['uvedto']=="true")
              {
              //echo " | uvedto=".$ik['uvedto']." | todog=".$ik['todog']." | ";
              if(isset($ik['todog']))
                {
                //if(strcmp($ik['todog'],"10")!=0 && strcmp($ik['todog'],"0")!=0) // если НЕ вариант Нет ТО и не вариант Не выбрано
                if($ik['todog']!=10 && $ik['todog']!=0) // если НЕ вариант Нет ТО и не вариант Не выбрано
                  {
                  $isdtarrivalto = 0;
                  $ischkvvoz     = 0;
                  $ischktnved    = 0;
                  $ischkmark     = 0;
                  $iscomto       = 0;
                  if(isset($ik['f_dtarrivalto'])) 
                    {
                    if(strlen($ik['f_dtarrivalto'])>0 && strcmp($ik['f_dtarrivalto'],"0000-00-00 00:00:00")!=0 && strcmp($ik['f_dtarrivalto'],"0000-00-00")!=0)
                      {
                      $isdtarrivalto = 1;
                      }
                    }
                  if(isset($ik['f_chkvvoz']))  {if($ik['f_chkvvoz']=="true") {$ischkvvoz = 1;}}
                  if(isset($ik['f_chktnved'])) {if($ik['f_chktnved']=="true") {$ischktnved = 1;}}
                  if(isset($ik['f_chkmark']))  {if($ik['f_chkmark']=="true") {$ischkmark = 1;}}
                  if(isset($ik['f_com_to']))   {if(strlen($ik['f_com_to'])>0) {$iscomto = 1;}}
                  //echo " | uvedto=".$ik['uvedto']." | todog=".$ik['todog']." | isdtarrivalto=".$isdtarrivalto." | ischkvvoz=".$ischkvvoz." | ischktnved=".$ischktnved." | ischkmark=".$ischkmark." | iscomto=".$iscomto." | ";
                  if(($isdtarrivalto==1) || (($ischkvvoz+$ischktnved+$ischkmark+$iscomto)>0) )
                    {
                    /*
                    5.2. ЕСЛИ требования 5.1.1. соблюдены - отправляем уведомление
                    5.2.1. ДОБАВИТЬ в шаблон уведомления (и/ИЛИ) создать новый шаблон для этого уведомления:
                           - дату прибытия в пункт ТО
                           - полные названия по установленным галочкам и задаче из пункта 3.3. (если они есть)
                    */
                    }
                  else
                    {
                    $candel = -2; $errmsg = $errmsg."\nВыставлена галочка Уведомить специалиста ТО.\nУкажите ЛИБО плановую дату прибытия в пункт ТО, ЛИБО задачу для отдела таможни. ";//sdid2530 
                    }
                  }
                else // если вариант Нет ТО, галка уведомить специалиста ТО бессмысленна, делаем отлуп
                  {
                  //echo " | uvedto=".$ik['uvedto']." | todog=".$ik['todog']." | ";
                  $candel = -2; $errmsg = $errmsg."\nВыставлена галочка Уведомить специалиста ТО, но не выбран вариант договора ТО.\nВыберите вариант договора ТО. ";//sdid2530 
                  }
                }
              }
            }
            //~sdid 2084 tz2 2
          //~sdid 2084 tz2
          //sdid 2530
          $fsubtype = 0;
          $strcertdog = "";
          if(isset($ik['subtype'])) {$fsubtype = $ik['subtype'];}
          elseif(isset($ik['f_subtype'])) {$fsubtype = $ik['f_subtype'];}
          if(isset($ik['certdog'])) {$strcertdog = $ik['certdog'];}
          elseif(isset($ik['f_certdog'])) {$strcertdog = $ik['f_certdog'];}
          if(strlen($strcertdog)>0)
            {
            if((substr($strcertdog,0,1)==1)&&($fsubtype!=2))
              {
              $candel = -2; $errmsg = $errmsg."Сертификация с типом \"Сертификат к этой заявке\" доступна только для Подвида заявки \"Сертификация\". ";//sdid2530
              }
            elseif( (substr($strcertdog,0,1)==2) && ($fsubtype!=5) && ($fsubtype!=6) )
              {
              $candel = -2; $errmsg = $errmsg."Сертификация с типом \"Сертификат через доп.заявку\" доступен только для Подвида заявки \"Спецификация\" или \"Заявка\". ";//sdid2530
              }
            }
          //~sdid 2530
          //sdid 2637
          // Нужно запретить возможность создавать Заявки/Спецификации с типом "Спецификация" и подвидом "Сертификация".
          $ftypez = 0;
          if(isset($ik['typez'])) {$ftypez = $ik['typez'];}
          elseif(isset($ik['f_typez'])) {$ftypez = $ik['f_typez'];}
          if(($fsubtype==2)&&($ftypez==2))
            {
            $candel = -2; $errmsg = $errmsg."\nДля типа \"Спецификация\" недопустимо использовать подвид \nСертификация\n";
            }
          //~sdid 2637
          }
        elseif($curtbl==17)//Перед добавлением - счета
          {
          if(isset($ik['f_type']))
            {
            //sdid - 413, если создаем инвойс по вал контракту с far east, то делаем автонумерацию и ставим текущую дату инвойса
            if($ik['f_type']==15)
              {
              if(isset($ik['dogid']))
                {
                if($ik['dogid']>0)//Если указали договор в инвойсе
                  {
                  $sql = "select ifnull(count(*),0) cnt from veda_dogs d 
                          where d.f_id=".$ik['dogid']." and d.f_contrid=1373 and d.f_dogtype=3 and d.f_subtype=1";
                  //echo $sql."|";
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['cnt']>0)
                      {
                      //$sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%' and f_id not in (29951,30003)";
                      //$sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%' and LENGTH(f_num)=7"; //sdid 2991
                      $sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%' and LENGTH(f_num)=8"; //sdid 2991
                      //echo $sql."|";
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        if(isset($ik['f_num']))
                          {$ik['f_num'] = "FE-".str_pad($row1['ninv'],4,"0", STR_PAD_LEFT);}
                        elseif(isset($ik['num']))
                          {$ik['num']   = "FE-".str_pad($row1['ninv'],4,"0", STR_PAD_LEFT);}
                        $ik['f_dt']  = date("Y-m-d");
                        }
                      }
                    }
                  }
                }
              }
            //~sdid 413
            }
          // sdid 1314
          if(isset($ik['f_operid']))
            {
            $f_operid = $ik['f_operid'];
            if (strlen($f_operid) > 0)
              {
              $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $f_operid;
              $conn = $dbh->query($sql);
              if ($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                if ($row['ctgs'] > 0)
                  {
                  $candel = -1;
                  $errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ). ";//sdid2530
                  }
                }
              }
            }
          // ~ sdid 1314
          }
        //sdid 715
        //elseif(($curtbl==48))//Валютные контракты
        //  {if(isset($ik['dogtype']))//делаем автонумерацию для валютного контракта
        //    {if($ik['dogtype']==3)
        //      {
        //       if(isset($ik['f_dogname']))
        //         {if(strlen($ik['f_dogname'])==0)
        //           {if(isset($ik['orgid']) && isset($ik['bankid']))
        //             {if(($ik['orgid']>0) && ($ik['bankid']>0))
        //               {
        //               $teo = "";
        //               if($ik['subtype']==1) // При выборе Тип: валютный, Подвид договора: транспортно-экспедиционное обслуживание, к номеру добавляется буква T
        //                 {$teo = "-T";}
        //               $orgcode = "";
        //               switch ($ik['orgid'])
        //                 {
        //                   case 15: $orgcode="";    //ВЭД Агент
        //                            break;  
        //                   case 30: $orgcode="/VC"; //ВЭД Консалт
        //                            break;  
        //                   case 31: $orgcode="/VS"; //ВЭД Сервис
        //                            break;  
        //                 }
        //               $bankcode = "";
        //               switch ($ik['bankid'])
        //                 {
        //                   case 2520: $bankcode="";      //Левобережный
        //                            break;  
        //                   case 4175: $bankcode="-UNI";  //Юникредит
        //                            break;  
        //                   case 3186: $bankcode="-AB";   //Альфа-Банк
        //                            break;  
        //                   case 2723: $bankcode="-SB";   //Сбер
        //                            break;  
        //                   case 3329: $bankcode="-SB";   //Сбер
        //                            break;  
        //                 }
        //
        //                 if($ik['orgid']==31) {$bankcode = "";} //ВЭД Сервис - код банка не прописываем
        //
        //                 $bankstr=" and d.f_bankid=".$ik['bankid'];
        //                 if(($ik['bankid']==2723)||($ik['bankid']==3329)) //Если Сбер
        //                   {
        //                      $bankstr=" and d.f_bankid in (2723,3329) ";
        //                   }
        //
        //                 if(($ik['orgid']==15)&&($ik['bankid']==2520))
        //                   {
        //                     $sql = "select LPAD((ifnull(max(CONVERT(s.subdognum,UNSIGNED INTEGER)),0)+1),4,'0') nextnumdog from
        //                             (
        //                             select d.f_dogname subdognum, 
        //                                    d.f_dogname REGEXP '^[0-9]+$' as isnumeric 
        //                             from veda_dogs d 
        //                             where d.f_dogtype=3 and d.f_orgid=".$ik['orgid']." and d.f_bankid=".$ik['bankid']." 
        //                             ) s
        //                             where s.isnumeric=1 
        //                             and length(s.subdognum)<5";  
        //                   }  
        //                 else
        //                   {
        //
        //                     $sql = "select LPAD((ifnull(max(CONVERT(s.subdognum,UNSIGNED INTEGER)),0)+1),4,'0') nextnumdog from
        //                             (
        //                             select d.f_dogname, 
        //                                    SUBSTRING(d.f_dogname,1,(POSITION('/' in d.f_dogname)-1)) subdognum, 
        //                                    SUBSTRING(d.f_dogname,1,(POSITION('/' in d.f_dogname)-1)) REGEXP '^[0-9]+$' as isnumeric 
        //                             from veda_dogs d 
        //                             where d.f_dogtype=3 and d.f_orgid=".$ik['orgid'].$bankstr." 
        //                             ) s
        //                             where s.isnumeric=1 
        //                             and s.f_dogname like '%".$orgcode.$bankcode.$teo."%'";  
        //                   }
        //               $res1 = $dbh->query($sql);
        //               if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
        //                 {$ik['f_dogname']=$row1['nextnumdog'].$orgcode.$bankcode.$teo;}
        //               }
        //             }
        //           }
        //         }
        //      }
        //    }
        //  }
        //~sdid 715
//sdid 1207
        elseif(($curtbl==54))//Перед добавлением - Страхование перевозок
          {
            if(isset($ik['status']))
              {
                if($ik['status'] == 3)
                  {
                    $ik['stskdt'] = date("Y-m-d");
                  }
             }
          }
//~sdid 1207
        //sdid 707
        elseif(($curtbl==58))//Перед добавлением - Вх./Исх. корреспонденция
          {if(isset($ik['type']))//делаем автонумерацию для входящей/исходящей корреспонденции
            {if(isset($ik['orgid']))
               {if($ik['orgid']>0)
                  {
                        if($ik['type']==1) // входящие
                          {
                            $sql = "select (ifnull(max(CONVERT(l.f_num,UNSIGNED INTEGER)),0)+1) nextnumiolet from veda_inoutlet l where l.f_type=1 and l.f_orgid=".$ik['orgid'];
              
                          }
                        elseif($ik['type']==2) // исходящие
                          {
                            $sql = "select (ifnull(max(CONVERT(l.f_num,UNSIGNED INTEGER)),0)+1) nextnumiolet from veda_inoutlet l where l.f_type=2 and l.f_orgid=".$ik['orgid'];
                          }
                        $res1 = $dbh->query($sql);
                        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {$ik['f_num']=$row1['nextnumiolet'];}
                  }
               }
            }
          }
        //~sdid 707
        elseif($curtbl==82)//Перед добавлением - реестр инвойсов
          {
          $ik['f_type']       = 15;//ставим тип - Invoice
          $ik['f_doptype']    = 1;//подвид - Invoice FE
          if(isset($ik['dogid']))
            {
            if($ik['dogid']>0)//Если указали договор в инвойсе
              {
              $sql = "select f_valdog from ".DBPref."dogs where f_id=".$ik['dogid'];
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//корректируем валюту на валюту контракта
                {$ik['val']=$row1['f_valdog'];}
              //sdid - 413, если создаем инвойс по вал контракту с far east, то делаем автонумерацию и ставим текущую дату инвойса
              $sql = "select ifnull(count(*),0) cnt from veda_dogs d 
                      where d.f_id=".$ik['dogid']." and d.f_contrid=1373 and d.f_dogtype=3 and d.f_subtype=1";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if($row1['cnt']>0)
                  {
                  //$sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%'";
                  //$sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%' and LENGTH(f_num)=7"; //sdid 2991
                  $sql = "select ifnull((max(CONVERT(SUBSTRING(f_num,4),UNSIGNED INTEGER))+1),1) ninv from veda_schets where f_num like 'FE-%' and LENGTH(f_num)=8"; //sdid 2991
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    $ik['f_num'] = "FE-".str_pad($row1['ninv'],4,"0", STR_PAD_LEFT);
                    $ik['f_dt']  = date("Y-m-d");
                    }
                  }
                }
              //~sdid 413
              }
            }
          }
        //sdid 712
        elseif($curtbl==83)//Перед добавлением - Закрывающие документы
          {
          if(isset($ik['type']))
            {
            if(($ik['type']==10)||($ik['type']==23)||($ik['type']==25))//если тип документа "Реализация (акты, накладные)" или "АКТ от иностранного поставщика" или "Товар от иностранного поставщика"
              {$ik['status']=0;}
            }
          //sdid 1361
          if((isset($ik['dogid'])||isset($ik['f_dogid']))&&(isset($ik['type'])||isset($ik['f_type']))&&(isset($ik['dogtype'])||isset($ik['f_dogtype'])))
            {
            $typdoc = 0;
            $dogid = 0;
            $dogtype = 0;
            if(isset($ik['type'])){$typdoc = $ik['type'];}
            elseif(isset($ik['f_type'])){$typdoc = $ik['f_type'];}
            if($typdoc==7) //Поступления (акты, накладные)
              {
              if(isset($ik['dogid'])){$dogid = $ik['dogid'];}
              elseif(isset($ik['f_dogid'])){$dogid = $ik['f_dogid'];}
              if(isset($ik['dogtype'])){$dogtype = $ik['dogtype'];}
              elseif(isset($ik['f_dogtype'])){$dogtype = $ik['f_dogtype'];}

              if($dogid==0 || $dogtype==0)
                {
                $candel = -1; 
                $errmsg = "Необходимо заполнить поля 'Договор' и 'Тип договора'. ";//sdid2530
                }
              } 
            }
          //~sdid 1361
          // sdid 1314
          $operid = 0;
          if(isset($ik['operid'])){$operid=$ik['operid'];}
          elseif(isset($ik['f_operid'])){$operid=$ik['f_operid'];}
          if($operid > 0)
            {
            $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $operid;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if($row['ctgs'] > 0)
                {
                $candel = -1;
                $errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ). ";//sdid2530
                }
              }
            }
          // ~ sdid 1314
          }
        //sdid3236
        elseif(($curtbl==121))//Перед добавлением - Группы пользователей
          {if(isset($ik['f_dopprstr'])) //делаем автонумерацию для доверенностей
            {if(strlen($ik['f_dopprstr'])>0)
              {if(isset($ik['f_namedop']))
                {if(!filter_var($ik['f_namedop'],FILTER_VALIDATE_EMAIL))
                  {$candel = -1;
                  $errmsg = "Использовать в Дефекты РП можно только с указанной корректной почтой!!!";}
                }
              else
                {$candel = -1;
                $errmsg = "Использовать в Дефекты РП можно только с указанной почтой!!!";}
              }
            }
          }
        //~sdid3236
        elseif(($curtbl==129))//Перед добавлением - Доверенности
          {if(isset($ik['orgid'])) //делаем автонумерацию для доверенностей
            {
             if($ik['orgid']>0)
               {
                 $sql = "select (ifnull(max(CONVERT(t.f_num,UNSIGNED INTEGER)),0)+1) nextnumtrust from veda_trusts t where t.f_orgid=".$ik['orgid'];
               }
            }
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$ik['f_num']=$row1['nextnumtrust'];}
          }
        //~sdid 712
        elseif($curtbl==146)//Перед добавлением - Пользователи-объекты
          {
          if(isset($ik['idobj'])&&isset($ik['typerole']))
            {
            if(($ik['idobj']>0)&&($ik['typerole']>0))
              {
              $sql  = "select ifnull(sum(f_part),0) sp,(select f_name from veda_spr where f_type=75 and f_num=p.f_typerole) typerole 
                       from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=".$ik['idobj']." and p.f_typerole=".$ik['typerole'];
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if(($row1['sp']+$ik['f_part'])>1)
                  {
                  $candel = 0;
                  $errmsg = "Сумма долей роли \"".$row1['typerole']."\" превышает 1 !!! ";//sdid2530
                  }
                }
              }
            }
          }
        //sdid1583
        elseif(($curtbl==147)||(strcmp($tblname,DBPref."acchist_docs")==0))//Перед добавлением - ПП-документы
          {
          $lahtype=0;
          if(isset($ik['lahtype'])){$lahtype=$ik['lahtype'];unset($ik['lahtype']);}
          // sdid 1314
          $doctype = 0;
          $doctype_field = "f_doctype";
          if(isset($ik['doctype'])){$doctype=$ik['doctype'];$doctype_field="doctype";}
          elseif(isset($ik['f_doctype'])){$doctype=$ik['f_doctype'];}
          $docid = 0;
          if(isset($ik['docid'])){$docid=$ik['docid'];}
          elseif(isset($ik['f_docid'])){$docid=$ik['f_docid'];}
          if(($doctype==4)||($doctype==5)){$ik[$doctype_field]=3;$doctype=3;}
          //sdid2462 - запрещаем добавлять документы по выпискам в закрытом периоде
          $lcando = 1;
          $acchistid = 0;
          if(isset($ik['acchistid'])){$acchistid=$ik['acchistid'];}
          elseif(isset($ik['f_acchistid'])){$acchistid=$ik['f_acchistid'];}
          //sdid2685
          $canaddupddelacchistl = 0;
          if(isset($ik['canaddupddelacchistl'])){$canaddupddelacchistl=$ik['canaddupddelacchistl'];unset($ik['canaddupddelacchistl']);}
          if($canaddupddelacchistl==0)
            {
            if($acchistid>0)
              {
              $dtbuhclserp = "";
              $sql_dtbuhcls = "SELECT MAX(f_dtbuhcls) dtbuhcls FROM ".DBPref."settings where f_settype=1";
              $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
              if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                {$dtbuhclserp = strtotime($row_dtbuhcls["dtbuhcls"]);}
              if(strlen($dtbuhclserp)>0)
                {
                $sql_dtbuhcls = "SELECT f_dt1C dt1c FROM ".DBPref."acchist ah where ah.f_id=$acchistid";
                $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
                if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                  {
                  $ldt1c = strtotime($row_dtbuhcls["dt1c"]);
                  if($ldt1c < $dtbuhclserp)
                    {
                    if(!isset($errmsg)) {$errmsg="";} //sdid 2569
                    $lcando = 0;$candel=-1;$errmsg = $errmsg."Выписка в закрытом бух.периоде, добавление связанных документов запрещено<br>";
                    }
                  }
                }
              }
            }
          //~sdid2685
          if($lcando>0)
            {
          //~sdid2462 - запрещаем добавлять документы по выпискам в закрытом периоде
            if($doctype==3 && $docid>0)
              {
              $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $docid;
              $conn = $dbh->query($sql);
              if($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                if($row['ctgs'] > 0)
                  {
                  $candel = -1;
                  $errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ). ";//sdid2530
                  }
                }
              if($candel!=-1)
                {
                $sql="select 
                        case 
                          when si.f_idoper=385 then si.f_id
                          when ifnull((select f_idoper from ".DBPref."spec_invoices where f_parentid=si.f_id and f_idoper=385 limit 1),0)=385 then 
                            (select f_id from ".DBPref."spec_invoices where f_parentid=si.f_id and f_idoper=385 limit 1)
                          else 0
                        end doperid
                      from ".DBPref."spec_invoices si where si.f_id=$docid";
                $conn1 = $dbh->query($sql);
                if($row1 = $conn1->fetch(PDO::FETCH_ASSOC))
                  {
                  $doperid = $row1['doperid'];
                  if($row1['doperid']>0)//если привязываем выписку к операции "Товар. В ДОЛГ.", то создаем подоперацию "Товар. В ДОЛГ. Оплата" и привязываем к ней
                    {
                    $sql = "select f_id from ".DBPref."spec_invoices where f_parentid=".$doperid." and f_idoper=387 limit 1";
                    $conn1 = $dbh->query($sql);
                    if($row1 = $conn1->fetch(PDO::FETCH_ASSOC))//связываем с уже имеющейся операций "Товар. В ДОЛГ. Оплата"
                      {
                      if(isset($ik['docid'])){$ik['docid']=$row1['f_id'];}
                      elseif(isset($ik['f_docid'])){$ik['f_docid']=$row1['f_id'];}
                      }
                    else
                      {
                      //sdid 3472
                      //$sql = "select * from ".DBPref."spec_invoices where f_id=".$doperid;
                      $sql = "select si.*, d.f_contrid dcontrid
                                from veda_spec_invoices si,veda_specs s,veda_dogs d 
                               where s.f_id=si.f_specid and d.f_id=s.f_dogid and si.f_id=".$doperid;
                      //~sdid 3472
                      $conn1 = $dbh->query($sql);
                      if($row1 = $conn1->fetch(PDO::FETCH_ASSOC))
                        {
                        $moprret = json_decode(makeOper(2,$row1['f_specid'],date("Y-m-d"),"",0,$row1['f_sum'],$row1['f_val'],0,1,"",
                               //"","",0,"",$row1['f_id'],387,$row1['f_contrid'],$row1['f_orgid'],$row1['f_dogid'],0,"", //sdid 3472
                               "","",0,"",$row1['f_id'],387,$row1['dcontrid'],$row1['f_orgid'],$row1['f_dogid'],0,"", //sdid 3472
                               0,0,0,0,-5,"","",181,"",0,
                               "","",2,0,0,0,0,0,0,"0000-00-00","0000-00-00",""),true);
                        //var_dump($moprret);
                        if($moprret[0]=="true")
                          {
                          if(isset($ik['docid'])){$ik['docid']=$moprret[2];}
                          elseif(isset($ik['f_docid'])){$ik['f_docid']=$moprret[2];}
                          }
                        else
                          {
                          $candel = -1;
                          $errmsg = "Ошибка создания операции \"Товар. В ДОЛГ. Оплата\". ";//sdid2530
                          }
                        }
                      }
                    }
                  }
                }
              //sdid 3472
              if($candel!=-1)
                {
                $stop_ah = 0;
                $stop_si = 0;
                $contr_ah = 0;
                $contr_si = 0;
                $cname_ah = "";
                $cname_si = "";
                $sql = "select ah.f_typeop1C, ah.f_contrid, c.f_cname from veda_acchist ah, veda_clients c where c.f_id=ah.f_contrid and ah.f_id=$acchistid";
                $res3 = $dbh->query($sql);
                if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                  {
                  if(strcmp($row3['f_typeop1C'],"Оплата от покупателя")==0) {$stop_ah = 1;}
                  $contr_ah = $row3['f_contrid'];
                  $cname_ah = $row3['f_cname'];
                  }
                $sql = "SELECT s84.f_num bdr, t.f_c1doctype c1doctype, si.f_contrid, c.f_cname 
                          FROM veda_spec_invoices si,veda_spr s83,veda_spr s84,veda_spr s85,veda_spr s86, veda_clients c, veda_typeopers t 
                         WHERE s86.f_num=si.f_bdrarticle and s86.f_type=86 and s85.f_type=85 and s84.f_type=84 and s83.f_type=83 and s86.f_dopprint=1 and s86.f_isuse=1
                           and s85.f_uslint=s83.f_num and s85.f_dopprint=s84.f_num and s86.f_uslint=s85.f_num and c.f_id=si.f_contrid and t.f_id=si.f_idoper and si.f_id=$docid";
                $res2 = $dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row2['bdr']==1) || ($row2['c1doctype']!=4 && $row2['c1doctype']!=5 && $row2['c1doctype']!=18)) {$stop_si = 1;}
                  $contr_si = $row2['f_contrid'];
                  $cname_si = $row2['f_cname'];
                  }
                if(($stop_ah==1 && $stop_si==1) && ($contr_ah!=$contr_si))
                  {
                  $candel = -1;
                  $errmsg1 = "Вы пытаетесь привязать выписку с контрагентом \"".$cname_ah."\" к операции с контрагентом \"".$cname_si."\". Сохранение невозможно, измените контрагента или выберите другую операцию";
                  //$errmsg1 = str_replace('"','\"',$errmsg1);
                  $errmsg1 = str_replace('"','',$errmsg1);
                  if(strlen($errmsg)>0) {$errmsg .= "<br>".$errmsg1;}
                  else {$errmsg = $errmsg1;}
                  }
                }
              //~sdid 3472
              }
            }
          // ~ sdid 1314
          }
        //~sdid1583
        // sdid 2125
        elseif($curtbl==200 || strcmp($tblname, DBPref."settings")===0) //Перед добавлением - Администрирование - Настройки
          {
          if ($ik['settype'] == 3) // Запрет выгрузки в 1С
            {
            $check = check1CExchangeBan();
            if ($check[0])
              {
              $candel = -1;
              $errmsg = "Такая настройка уже установлена. Воспользуйтесь поиском по типу настройки. ";//sdid2530
              }
            }
          }
        // ~ sdid 2125
//sdid 524
        elseif(($curtbl==222)||($curtbl==184))//Перед добавлением - АКТы - детализация
          {
          if(isset($ik['operid']))
            {
            if(isAllowCreateFD($ik['operid'])==0)
              {
              $candel = -1;
              $errmsg = "Документы нельзя привязать к закрытым и согласованным спецификациям. Обратитесь к главному бухгалтеру. ";//sdid2530
              }
            }
          }
//~sdid 524
        elseif($curtbl==186)//Перед добавлением - ЭДО-спецификации
          {
          $ik['f_type']       = 1;
          $ik['f_objtype']    = 181;
          $ik['f_detailtype'] = 15;
          }
        elseif($curtbl==187)//Перед добавлением - Счет-спецификации
          {
          $ik['f_type']       = 2;
          $ik['f_objtype']    = 17;
          $ik['f_detailtype'] = 15;
          }
        elseif($curtbl==196)//Перед добавлением - реестр инвойсов
          {
          $ik['f_type']       = 16;
          $ik['f_doptype']    = 1;
          }
        elseif($curtbl==220) //Перед добавлением - категории - падежи
          {
          $ik['f_objecttype']=135;
          $ik['f_objdtype']=4;
          }
        // sdid 1314
        elseif($curtbl==270) //Перед добавлением - категория доп статья бюджета
          {
          if ($ik['ctgtype'] == 32 || strcmp($ik['ctgtype'], "32")==0)
            {
            $sql_ctg = "SELECT * FROM ".DBPref."categs WHERE f_ctgtype=32 AND f_objecttype=5 AND f_objectid=".$ik['objectid'];
            $res_ctg = $dbh->query($sql_ctg);
            if($row_ctg = $res_ctg->fetch(PDO::FETCH_ASSOC))
              {return "[false,\"Операция (ID ".$ik['objectid'].") уже имеет установленную дополнительную статью бюджета.\"]";}
            }
          }
        elseif($curtbl==271)//Перед добавлением - Категории - Учет расхода
          {
          $id = 0;
          if(isset($ik['objectid'])){$id = $ik['objectid'];}
          //sdid 1314 20.09.2023
          if($id>0)
            {
            $sql = "SELECT COUNT(s.f_id) paid_invoices FROM ".DBPref."schets s  WHERE s.f_operid = ".$id;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {if($row['paid_invoices'] > 0){$candel = -1;$errmsg = "Запрещена смена учета расхода, если по операции уже выставлен счет. ";}}//sdid2530
            $sql = "SELECT COUNT(s.f_id) paid_invoices FROM ".DBPref."akts s  WHERE s.f_operid = ".$id;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {if($row['paid_invoices'] > 0){$candel = -1;$errmsg = "Запрещена смена учета расхода, если по операции уже создан акт. ";}}//sdid2530
            $sql = "SELECT COUNT(ad.f_id) paid_acchist FROM ".DBPref."acchist_docs ad WHERE ad.f_doctype = 3 AND ad.f_docid = ".$id;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {if($row['paid_acchist'] > 0){$candel = -1;$errmsg = "Запрещена смена учета расхода, если по операции уже произведена оплата. ";}}//sdid2530
            if($candel!=-1)
              {
              if($ik['ctgtype'] == 33 || strcmp($ik['ctgtype'], "33") == 0)
                {$sql_ctg = "SELECT * FROM ".DBPref."categs WHERE f_ctgtype=33 AND f_objecttype=5 AND f_objectid=".$id;
                $res_ctg = $dbh->query($sql_ctg);
                if($row_ctg = $res_ctg->fetch(PDO::FETCH_ASSOC))
                  {return "[false,\"Операция (ID ".$id.") уже имеет установленную категорию расхода.\"]";}
                if($ik['valstr']==2||strcmp($ik['valstr'],"2")==0)//Расход ОВЭД
                  { 
                  $sql_bdr = "SELECT ctg.f_valstr FROM ".DBPref."typeopers t, ".DBPref."spec_invoices si, ".DBPref."categs ctg 
                              WHERE t.f_type=si.f_type_oper AND t.f_subtype=si.f_sub_type_oper AND ctg.f_objectid=t.f_id AND ctg.f_ctgtype=32 
                                AND ctg.f_objecttype=4 AND si.f_id=".$id;
                  $res_bdr = $dbh->query($sql_bdr);
                  if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                    {$bdr = $row_bdr['f_valstr'];
                    $add_bdr = ["curtbl" => 270,"ctgtype" => 32,"objectid" => $id,"valstr" => $bdr, "objecttype" => 5];
                    addRowTbl($add_bdr);}
                  }
                }
              }
            }
          }
        // ~ sdid 1314
        elseif($curtbl==223)//Перед добавлением - АКТ-спецификации
          {
          $ik['f_type']       = 3;
          $ik['f_objtype']    = 83;
          $ik['f_detailtype'] = 15;
          }
        elseif($curtbl==246)//Перед добавлением - ЛКК. Клиенты
          {
          //$ik['f_login'];
          if(filter_var($ik['f_login'], FILTER_VALIDATE_EMAIL)){}
          else{$candel = -1;$errmsg = "Некорректный формат логина, должен быть email. ";}//sdid2530
          }             	
        //sdid 895
        elseif(($curtbl==251))//Перед добавлением - Прочие заявки/документы
          {
          $sql = "";
          if(isset($ik['doctypeid'])) //делаем автонумерацию для заявок
            {
            if($ik['doctypeid']==1) // тип документа - Заявка перевозчику
              {
              if(isset($ik['objecttypeid']))
                {
                if($ik['objecttypeid']==77) // тип объекта - Маршруты
                  {
                  $sql = "select (ifnull(max(CONVERT(od.f_docnum,UNSIGNED INTEGER)),0)+1) nextnumdoc from veda_other_docs od where od.f_doctypeid=1 and od.f_objecttypeid=77"; //.$ik['orgid'];
                  }
                }
              }
            }
          if(strlen($sql)>0)
            {
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ik['f_docnum']=$row1['nextnumdoc'];
              $ik['useridotv']=$_SESSION['loginid'];
              }
            }
          }
        //sdid 2987
        elseif($curtbl==333)// Сборные поставки
          {
          if(isset($ik['f_name']))
            {
            if(strlen($ik['f_name'])==0)
              {
              $candel = -1;
              $errmsg = "Поле \"Наименование\" не должно быть пустым и должно содержать уникальное значение.";
              }
            }
          }
        //~sdid 2987
        //sdid2232
        elseif(strcmp($tblname,DBPref."maspayfe_opers")==0)//Перед добавлением - обобщенные платежи - операции
          {
          if(isset($ik['maspayfeid']))
            {
            $sql = "SELECT f_acchistid FROM ".DBPref."maspayfe WHERE f_id=".$ik['maspayfeid'];
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if($row['f_acchistid'] > 0)
                {
                $candel = -1;
                $errmsg = "Запрещено добавлять операции к разнесенному обобщенному платежу (присутствует ссылка на ПП). ";//sdid2530
                }
              }
            }
          }
        //~sdid2232
        //~sdid 895 $_SESSION['loginid']
        $menomes=0;
        if(isset($ik['menomes']))
          {if($ik['menomes']==1){$menomes=1;}}
        foreach((Array)$ik as $key => $value)
          { 
          //$retval=$retval.$key." = ".$value." | "; 
          if(($key!="0")&&($key!="1")&&($key!="id")&&($key!="oper")&&($key!="curtbl"))
            {
            $value = str_replace("\\","&bsol;",$value);
            $curvalue = $value;
            if(strlen($strnames)>0)
              {$strnames=$strnames.",";$strvalues=$strvalues.",";}
            if(strcmp(substr($key,0,2),"f_")!=0)
              {$strnames=$strnames."f_";}
            $strnames=$strnames.$key;
            if((strcmp($value,"true")==0)||(strcmp($value,"Yes")==0))
              {$strvalues=$strvalues."1";$curvalue="1";}
            else
              if((strcmp($value,"false")==0)||(strcmp($value,"No")==0))
                {$strvalues=$strvalues."0";$curvalue="0";}
              else
                {
                if((strcmp($value,"CURDATE()")==0))
                  {$strvalues=$strvalues.$value;$curvalue=$value;}
                else
                  {
                  if(is_string($value))
                    {
                    //if(!((strcmp($value,"''")==0)||(strcmp($value,"' '")==0)))
                    //  {$value = str_replace("'","\'",$value);}
                    if(strcmp(substr($value,0,1),"'")==0){$strvalues=$strvalues.$value;}
                    else{$strvalues=$strvalues."'".str_replace("'","\'",$value)."'";}
                    }
                  else
                    {$strvalues=$strvalues.$value;}
                  }
                }
            }
          }
        if($candel==1)
          {
          if(strlen($strnames)>0)
            {
            //echo $strnames."\n";
            if(isTblDttmcrUserid($tblname)==1)
              {
              $strnames  = $strnames.",f_dttmcr,f_userid,f_dttmupd,f_useridupd";
              $strvalues = $strvalues.",NOW(),".$_SESSION['loginid'].",NOW(),".$_SESSION['loginid'];
              }
            $sql = "insert into ".$tblname." (".$strnames.") values (".$strvalues.")";
            //if($_SESSION['loginid']==130)
            //  {echo $sql."<br>";}
            //if(strcmp($tblname,"veda_acchist")==0)
            //  {echo $sql."<br>";}
            $dbh->exec($sql);
            $kk=$dbh->lastInsertId();
            //if(strcmp($tblname,"veda_acchist")==0)
            //  {echo $kk."<br>";}
            //!!!!!вставили новую запись
            if($kk>0)
              {
              //sdid - 353
              if(strcmp($tblname,DBPref."akts")==0)//После добавления - акта
                {
                //sdid - 203
                $sql = "SELECT s.f_operid,s.f_sum akt_sum,s.f_nds akt_nds,s.f_type,
                          si.f_sum oper_sum,si.f_nds oper_nds,si.f_nodoccalc,si.f_parenttype, si.f_specid sid
                        FROM $tblname s,".DBPref."spec_invoices si 
                        WHERE s.f_type=7 and s.f_operid>0 and si.f_id=s.f_operid and s.f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  $specstatus = 0;
                  if($row['f_parenttype']==2)//если операция по спецификации ищем статус операции
                    {
                    $sql = "select s.f_status from ".DBPref."specs s where s.f_id=".$row['sid'];
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {$specstatus=$row1['f_status'];}
                    }
                  if((($row['oper_sum']==$row['akt_sum']) && ($row['oper_nds']==$row['akt_nds']))||
                     ($specstatus == 8)||
                     ($specstatus == 17)||
                     (($row['f_parenttype']==2)&&($specstatus!=6)&&($specstatus!=8)&&($specstatus!=17)))
                    {
                    if($row['f_nodoccalc']==1)
                      {$ki = Array('curtbl'=>35,'curidx'=>$row['f_operid'], 'f_nodoccalc'=>0);
                      editRowTbl($ki);}
                    if(($specstatus == 8) || ($specstatus == 17))//Пишем письма ответственному бухгалтеру и менеджеру об изменении суммы маржи в РП
                      {
//sdid 887
                          $subject = "Cумма маржи в РП была изменена";
                          $postbody = "Cумма маржи в РП была изменена. К <a href=\"".$redirect_uri."?pgid=145&obid=".$row['f_operid']."\">операции</a> добавлен закрывающий документ";
                          $sbsh = 0;
                          $sbsh1 = 0;
                          if($menomes==0) 
                            { 
                                $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 35, $row['f_operid'], 151, 6, 7); 
                                $sbsh1 = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 35, $row['f_operid'], 151, 6, 8); 
                            }                     
                          if($sbsh == 0) { mSendMail(2,13,0,$subject,$postbody,"",35,$row['f_operid']); }
                          if($sbsh1 == 0) { mSendMail(2,14,0,$subject,$postbody,"",35,$row['f_operid']); }
// ----------------------- orig
//                      $subject   = "Cумма маржи в РП была изменена";
//                      $postbody  = "К <a href=\"".$redirect_uri."?pgid=145&obid=".$row['f_operid']."\">операции</a> добавлен закрывающий документ";
//                      mSendMail(2,13,0,$subject,$postbody,"",35,$row['f_operid']);
//                      mSendMail(2,14,0,$subject,$postbody,"",35,$row['f_operid']);
// ----------------------- ~orig
//~sdid 887
                      }
                    }
                  elseif(($row['f_parenttype']==2)&&($specstatus==6))
                    {handle_adjustments($kk, $tblname, $dbh);}
                  //$new_operation_id = $row['f_operid'];
                  //  if($new_operation_id>0)
                  //    {
                  //      $closing_document_hash = array('sum'=>$row['f_sum'], 'nds'=>$row['f_nds']);
                  //      reset_nodoccalc($new_operation_id, $closing_document_hash, $dbh);
                  //    }
                  }
                //sdid - 203
                //handle_adjustments($kk, $tblname, $dbh);
                //~sdid - 203
                // sdid 1865
                //sdid 2725
                //$sql = "SELECT a.f_type, a.f_mainakt, a.f_nds FROM ".$tblname." a WHERE a.f_id=".$kk;
                $sql = "SELECT a.f_type, a.f_mainakt, a.f_nds, a.f_ismainakt, a.f_operid, a.f_sum, a.f_ndssum, a.f_val, a.f_dt, 
                               ifnull(a.f_com,'') f_com FROM ".$tblname." a WHERE a.f_id=".$kk;
                //~sdid 2725
                $conn = $dbh->query($sql);
                if($row = $conn->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_type'] == 10 && $row['f_mainakt'] > 0)
                    {
                    $main_akt = $row['f_mainakt'];
                    $nds_type = $row['f_nds'];
                    $akt_update = ['curtbl' => 83, 'curidx' => $main_akt];
                    $sql_akt_query = "SELECT 
                                        COUNT(a.f_id) total, SUM(a.f_ndssum) ndssum, 
                                        (SELECT COUNT(ak.f_id) FROM ".DBPref."akts ak WHERE ak.f_nds=".$nds_type." AND ak.f_mainakt=".$main_akt.") cnt 
                                      FROM ".DBPref."akts a WHERE a.f_mainakt=".$main_akt;
                    $sql_akt_conn = $dbh->query($sql_akt_query);
                    if($sql_akt_row = $sql_akt_conn->fetch(PDO::FETCH_ASSOC))
                      {
                      if($sql_akt_row['total']==$sql_akt_row['cnt'])
                        {$akt_update['f_nds'] = $nds_type;}
                      $akt_update['f_ndssum'] = $sql_akt_row['ndssum'];
                      $akt_update_result = json_decode(editRowTbl($akt_update), true);
                      }
                    }
                  //sdid 2725
                  if($row['f_type']==26) //Commercial invoice
                    {
                    if(($row['f_mainakt']==0)&&($row['f_operid']==0)) // ручное добавление акта
                      {
                      // Добавляем запись в akts_details
                      $ki = Array('curtbl'=>180,'f_aktid'=>$kk,'f_num'=>0,'f_count'=>1,'f_price'=>$row['f_sum'],'f_sum'=>$row['f_sum'],
                                  'f_nds'=>$row['f_nds'],'f_ndssum'=>$row['f_ndssum'],'f_val'=>$row['f_val'],'f_grnd'=>$row['f_com']);
                      $res = json_decode(addRowTbl($ki), true);
                      //if($res[0]=="true"){$nrepid=$nres[2];}
                      }
                    }
                  //~sdid 2725
                  }
                // sdid 1865
                }
              //~sdid - 353
              elseif(strcmp($tblname,DBPref."remote_clnt")==0)//После добавления - 
                {
                $sql = "select f_login,f_bpassword,f_name1,f_isuse,f_fromlkk from ".$tblname." where f_fromlkk=0 and f_id=".$kk;
                //echo $sql."|";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row['f_isuse']==1)and($row['f_fromlkk']==0))//регистрируем пользователя ЛКК
                    {
                    $data = array(
                      'email'       => $row['f_login'],
                      'first_name'  => $row['f_name1'],
                      'iswork'      => $row['f_isuse'],
                      'password'    => $row['f_bpassword']
                                 );
                    $curl = curl_init(lkurl."/private/api/v1/users/auth/sign-up/");
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $opars = json_decode($response,true);
                    $wdopmes = 1;
                    if($opars['result'])
                      {
                      $ansn = $ansn." Зарегистрировали пользователя ЛКК";
                      $kik  = Array('curtbl'=>246,'curidx'=>$kk,'f_resreg'=>1);
                      editRowTbl($kik);
                      }
                    else
                      {
                      $ansn = $ansn." Ошибка регистрации пользователя ЛКК";
                      $kik  = Array('curtbl'=>246,'curidx'=>$kk,'f_resreg'=>0);
                      editRowTbl($kik);
                      }
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."bdr")==0)//После добавления - 
                {
                $sql = "select f_bdryear from $tblname where f_id=$kk";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {recalcBDRPlanY($dbh,$row['f_bdryear'],"p");}//пересчитать плановые значения БДР при вставке новой записи
                }
              elseif(strcmp($tblname,DBPref."cash")==0)//После добавления - 
                {
                if(($curtbl==67)&&(isset($ik['ispod'])&&(isset($ik['f_outsum']))))
                  {
                  //if(strcmp($ik['ispod'], "true") == 0 && $ik['f_outsum'] > 0)
                  if($ik['ispod']==1 && $ik['f_outsum']>0)
                    { // касса, делаем подотчет
                    $cash_details = array();
                    $cash_details['curtbl'] = 205;
                    $cash_details['f_cashid'] = $kk; // кассовый документ
                    $cash_details['f_insum'] = $ik['f_outsum']; // приход подотчет - расход касса,
                    $cash_details['f_outsum'] = $ik['f_insum']; // и наоборот
                    $cash_details['f_userid'] = $ik['podid']; // ответственное лицо
                    $cash_details['f_bdr'] = $ik['bdr']; // статья БДР
                    $cash_details['f_dt'] = $ik['f_dt']; // дата
                    $cash_details['f_val'] = $ik['val']; // валюта
                    $cash_details['f_com'] = $ik['f_operdesc']; // описание
                    $cash_details['f_ispod'] = "1";
                    addRowTbl($cash_details);
                    }
                  }
                } 
              elseif(strcmp($tblname,DBPref."cash_details")==0)//После добавления - 
                {
                if(isset($ik['ispod'])&&isset($ik['f_outsum']))
                  {
                  if($curtbl == 205 && (strcmp($ik['ispod'],"true")==0) && $ik['f_outsum']>0)
                    { // если касса. подотчет, то добавляем зеркальную в кассу
                    $cash = array();
                    $cash['curtbl'] = 67;
                    $cash['f_dt'] = $ik['f_dt']; // дата
                    $cash['f_operdesc'] = $ik['f_com']; // описание
                    $cash['f_insum'] = $ik['f_outsum']; // расход-приход, и наоборот
                    $cash['f_outsum'] = $ik['f_insum'];
                    $cash['f_val'] = $ik['val']; // валюта
                    $cash['f_idoper'] = $ik['f_idoper']; // ид операции
                    $cash['f_bdr'] = $ik['bdr']; // БДР
                    $cash['f_ispod'] = "1";
                    $cash['f_podid'] = $ik['userid'];
                    addRowTbl($cash);
                    }
                  }
                } 
              elseif(strcmp($tblname,DBPref."specs")==0)//После добавления - 
                { //обрабатываем вставку спецификации
                //$sql = "select s.*,cl.f_ensdogid,cl.f_enstovar,cl.f_enscustom,cl.f_enslogist,cl.f_enslostprof ".
                //       "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl ".
                //       "where d.f_id=s.f_dogid and cl.f_id=d.f_contrid and s.f_id=".$kk;
                $sql = "select * from ".DBPref."specs where f_id=".$kk;
                //echo $sql;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_operid']>0)
                    {
                    $prjparts = array();
                    $prjparts['curtbl'] = 146;
                    $prjparts['f_typeobj'] = 1;
                    $prjparts['f_idobj'] = $kk;
                    $prjparts['f_typerole'] = 1;
                    $prjparts['f_idsubj'] = $row['f_operid'];
                    $prjparts['f_part'] = 1;
                    $prjparts['f_ismain'] = 1;
          
                    addRowTbl($prjparts);
                    }
                  if($row['f_buhid']>0) 
                    {
                    $prjparts = array();
                    $prjparts['curtbl'] = 146;
                    $prjparts['f_typeobj'] = 1;
                    $prjparts['f_idobj'] = $kk;
                    $prjparts['f_typerole'] = 2;
                    $prjparts['f_idsubj'] = $row['f_buhid'];
                    $prjparts['f_part'] = 1;
                    $prjparts['f_ismain'] = 1;
          
                    addRowTbl($prjparts);
                    }
                  //добавляем бухгалтера по переводам для спецификации
                  if((($row['f_typez']==2)||($row['f_typez']==3))&&($row['f_dogid']>0))
                    {
                    $sql = "select f_orgid from ".DBPref."dogs where f_id=".$row['f_dogid'];
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row1['f_orgid']==15){$sql="select f_id from ".DBPref."users where f_dolzcode=27 and f_isactived=1";}
                      else{$sql="select f_id from ".DBPref."users where f_dolzcode=26 and f_isactived=1";}
                      $res2 = $dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        $ki = Array('curtbl'=>146,'f_typeobj'=>1,'f_idobj'=>$kk,'f_typerole'=>4,'f_idsubj'=>$row2['f_id']);
                        $ar = json_decode(addRowTbl($ki), true);
                        }
                      }
                    }
                  $iskp = 0;$iskps = "Спецификации";$iskpid=15;
                  if($row['f_typez']==1)
                    {$iskp = 1;$iskps = "КП";$iskpid=47;}
                  if($row['f_wens']>0)//Признак «Страхование»
                    {
                    $dn = 2;
                    if($row['f_typez']==1)
                      {$dn = 1;}
                    //$ki = Array('curtbl'=>54,'f_objtype'=>$dn,'f_objid'=>$kk,'f_dogid'=>$row['f_ensdogid'],
                    //            'f_tovar'=>$row['f_enstovar'],'f_custom'=>$row['f_enscustom'],'f_logist'=>$row['f_enslogist'],
                    //            'f_lostprof'=>$row['f_enslostprof']);
                    $ki = Array('curtbl'=>54,'f_objtype'=>$dn,'f_objid'=>$kk);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      if($row['f_uvedins']==1)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о страховании";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$ar[2]."\">Страховании</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 54, $ar[2], 151, 4, 11); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],8,0,"Для ".$iskps." создана запись о страховании","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$ar[2]."\">Страховании</a>","",54,$ar[2]);
                            }
                          }                     
                        //mSendMail($_SESSION['loginid'],8,0,"Для ".$iskps." создана запись о страховании","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$ar[2]."\">Страховании</a>","",54,$ar[2]);
                        //~sdid 1546
                        }
                      }
                    //$sql = "insert into ".DBPref."ensures (f_userid,f_dttmcr,f_objtype,f_objid) values (".$_SESSION['loginid'].",NOW(),".$dn.",".$kk.")";
                    //$kkk = $dbh->exec($sql);
                    //if($kkk>0)
                    //  {
                    //  $kkk  = $dbh->lastInsertId();
                    //  if($row['f_wpost']==1)
                    //    {mSendMail($_SESSION['loginid'],8,0,"Для ".$iskps." создана запись о страховании","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kkk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$kkk."\">Страховании</a>");}
                    //  }
                    }
                  //sdid 2224 блокируем этот блок. т.к. иначе будет создаваться дубль сертификата
                  /*if($row['f_wsert']>0)//Признак «Сертификация»
                    {
                    $ki = Array('curtbl'=>56,'f_specid'=>$kk);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      if($row['f_uvedcrt']==1)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о сертификации";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar[2], 151, 7, 13); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>","",56,$ar[2]);
                            }
                          }                     
                        //~sdid 1546
                        //mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>","",56,$ar[2]);
                        }
                      }
                    //$sql = "insert into ".DBPref."certificates (f_userid,f_dttmcr,f_specid) values (".$_SESSION['loginid'].",NOW(),".$kk.")";
                    //echo $sql;
                    //$kkk = $dbh->exec($sql);
                    //if($kkk>0)
                    //  {
                    //  $kkk  = $dbh->lastInsertId();
                    //  if($row['f_uved']==1)
                    //    {mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kkk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$kkk."\">Сертификации</a>","",56,$kkk);}
                    //  }
                    }*/
                  //~sdid 2224
                  //sdid 2224
                  ///////////////////////////////////
                  //if(($row['f_certdog']>0) && ($row['f_subtype']!=2)) // Если поле Сертицикация не пусто, не 0 и подтип не равен Сертификация //sdid 2224 2024-03-24
                  if($row['f_certdog']>0) // Если поле Сертицикация не пусто, не 0 и подтип не равен Сертификация //sdid 2224 2024-03-24
                    {
                    $strcertdog = (string) $row['f_certdog']; 
                    if(substr($strcertdog,0,1)==1) // Если выбрана текущая спецификация
                      {
                      // Создаем запись в таблице "Сертификация" по текущей спецификации
                      $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                      $typez = 0;
                      if(isset($ik['typez'])) {$typez = $ik['typez'];}
                      elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                      if($typez==1)
                      {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                      $cst = 0;
                      if($iskp==1){$cst = 23;}
                      //sdid 2224 2024-03-24
                      //if($row['f_parentspecid']==0) // если лонли, создаем запись Сертификации. Убрали проверку sdid2753
                      //  {
                        $ki = Array('curtbl'=>56,'f_specid'=>$kk);
                        $ar = json_decode(addRowTbl($ki), true);
                        if($ar[0]=="true")
                          {
                          if($row['f_uvedcrt']==1)
                            {
                            $subject = "Для ".$iskps." создана запись о сертификации";
                            $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>";
                            $sbsh = 0;
                            if($menomes==0) 
                              { 
                              $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar[2], 151, 7, 13); 
                              if($sbsh == 0) 
                                { 
                                mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>","",56,$ar[2]);
                                }
                              }                     
                            }
                          }
                      //  }
                      //~sdid 2224 2024-03-24
                      }
                    elseif(substr($strcertdog,0,1)==2) // Если выбран клиентский договор 
                      {
                      //Создаем Заявку "Сертификация"
                      $certdogparm = substr($strcertdog,1);
                      //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                      $obj = new ArrayObject($ik);
                      $ki1 = $obj->getArrayCopy();
                      $ki1['curtbl']       = 15;
                      if(isset($ki1['f_parentspecid'])) {$ki1['f_parentspecid']=$kk;}
                      elseif(isset($ki1['parentspecid'])) {$ki1['parentspecid']=$kk;}
                      if(isset($ki1['f_dogid'])) {$ki1['f_dogid']=$certdogparm;}
                      elseif(isset($ki1['dogid'])) {$ki1['dogid']=$certdogparm;}
                      if(isset($ki1['f_subtype'])) {$ki1['f_subtype']=2;}
                      elseif(isset($ki1['subtype'])) {$ki1['subtype']=2;}
                      else{$ki1['f_subtype']=2;}
                      if(isset($ki1['f_dt'])) {$ki1['f_dt']=date("Y-m-d");}
                      elseif(isset($ki1['dt'])) {$ki1['dt']=date("Y-m-d");}
                      else{$ki1['f_dt']=date("Y-m-d");}
                      if(isset($ki1['f_typez'])) {$ki1['f_typez']=3;}
                      elseif(isset($ki1['typez'])) {$ki1['typez']=3;}
                      else{$ki1['f_typez']=3;}
                      if(isset($ki1['f_kod1cb'])) {$ki1['f_kod1cb']="";}
                      elseif(isset($ki1['kod1cb'])) {$ki1['kod1cb']="";}
                      if(isset($ki1['f_kod1cp'])) {$ki1['f_kod1cp']="";}
                      elseif(isset($ki1['kod1cp'])) {$ki1['kod1cp']="";}
                      if(isset($ki1['f_wens'])) {$ki1['f_wens']=false;}
                      elseif(isset($ki1['wens'])) {$ki1['wens']=false;}
                      if(isset($ki1['f_wsert'])) {$ki1['f_wsert']=true;}
                      elseif(isset($ki1['wsert'])) {$ki1['wsert']=true;}
                      if(isset($ki1['f_wpost'])) {$ki1['f_wpost']=false;}
                      elseif(isset($ki1['wpost'])) {$ki1['wpost']=false;}
                      if(isset($ki1['f_sbor'])) {$ki1['f_sbor']=false;}
                      elseif(isset($ki1['sbor'])) {$ki1['sbor']=false;}
                      if(isset($ki1['f_wto'])) {$ki1['f_wto']=false;}
                      elseif(isset($ki1['wto'])) {$ki1['wto']=false;}
                      if(isset($ki1['f_uvedins'])) {$ki1['f_uvedins']=false;}
                      elseif(isset($ki1['uvedins'])) {$ki1['uvedins']=false;}
                      if(isset($ki1['f_uvedto'])) {$ki1['f_uvedto']=false;}
                      elseif(isset($ki1['uvedto'])) {$ki1['uvedto']=false;}
                      if(isset($ki1['f_uvedcrt'])) {$ki1['f_uvedcrt']=true;} // !!!!!!
                      elseif(isset($ki1['uvedcrt'])) {$ki1['uvedcrt']=true;} // !!!!!!
                      else{$ki1['f_uvedcrt']=true;}
                      if(isset($ki1['f_postid'])) {$ki1['f_postid']=false;}
                      elseif(isset($ki1['postid'])) {$ki1['postid']=false;}
                      if(isset($ki1['f_todog'])) {$ki1['f_todog']=10;}
                      elseif(isset($ki1['todog'])) {$ki1['todog']=10;}
                      else{$ki1['f_todog']=10;}
                      if(isset($ki1['f_certdog'])) {$ki1['f_certdog']=0;} //sdid 2224 2024-03-24
                      elseif(isset($ki1['certdog'])) {$ki1['certdog']=0;} //sdid 2224 2024-03-24
                      else{$ki1['f_certdog']=0;}
                      if(isset($ki1['id'])) {unset($ki1['id']);}
                      if(isset($ki1['curidx'])) {unset($ki1['curidx']);}
                      if(isset($ki1['f_num'])) {unset($ki1['f_num']);}
                      if(isset($ki1['num'])) {unset($ki1['num']);}
                      if(isset($ki1['f_tpdog'])) {unset($ki1['f_tpdog']);}
                      if(isset($ki1['tpdog'])) {unset($ki1['tpdog']);}
                      $ar = json_decode(addRowTbl($ki1), true);
                      if($ar[0]=="true")
                        {
                        $newcertid = $ar[2]; 
                        $wdopmes = 1;
                        $ansn = $ansn."Добавили связанную заявку - Сертификация";
                        //sdid 2224 2024-03-24
                        // Ищем связанную с родительской Сертификацию и привязываем ее к созданной Заявке "Сертификация"
                        $newtpid = $ar[2]; //2024-02-19
                        $sql = "select f_id from veda_certificates where f_specid=".$kk;
                        $res_crt = $dbh->query($sql);
                        if($row_crt = $res_crt->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>56,'curidx'=>$row_crt['f_id'],'f_specid'=>$newtpid); 
                          editRowTbl($ki);
                          }
                        else
                          {
                        //~sdid 2224 2024-03-24
                          // Создаем новую запись "Сертификация" и привязываем ее к новой заявке "Сертификация"
                          $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                          $typez = 0;
                          if(isset($ik['typez'])) {$typez = $ik['typez'];}
                          elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                          if($typez==1)
                          {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                          $cst = 0;
                          if($iskp==1){$cst = 23;}
                        
                          $ki2 = Array('curtbl'=>56,'f_specid'=>$ar[2],'f_status'=>$cst);
                          $ar2 = json_decode(addRowTbl($ki2), true);
                        
                          if($ar2[0]=="true")
                            {
                            if($row['f_uvedcrt']==1)
                              {
                              $subject = "Для ".$iskps." создана запись о сертификации";
                              $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$newcertid."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar2[2]."\">Сертификации</a>";
                              $sbsh = 0;
                              if($menomes==0) 
                                { 
                                $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar2[2], 151, 7, 13); 
                                if($sbsh == 0) 
                                  { 
                                  mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$newcertid."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar2[2]."\">Сертификации</a>","",56,$ar2[2]);
                                  }
                                }                     
                              }
                            }
                        //sdid 2224 2024-03-24
                          }
                        //~sdid 2224 2024-03-24
                        }
                      else
                        {
                        $wdopmes = 1;
                        $anns = $ansn."Ошибка добавления заявки - Сертификация";
                        }
                      }
                    }
                  ///////////////////////////////////
                  //~sdid 2224
                  //sdid 2084 tz2 2
                  /*if($row['f_wto']>0)//Признак «Таможенное оформление»
                    {
                    //sdid = 391
		    if(($row['f_tpdog']>0) && ($row['f_subtype']!=3))
		      {
                      //Создаем Заявка "Поручение ТП"
                      //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                      $obj = new ArrayObject($ik);
                      $ki1 = $obj->getArrayCopy();
                      $ki1['curtbl']       = 15;
                      $ki1['parentspecid'] = $kk;
                      $ki1['dogid']        = $row['f_tpdog'];
                      $ki1['subtype']      = 3;
                      $ki1['f_dt']         = date("Y-m-d");
                      $ki1['typez']        = 3; //2022.11.09
                      $ki1['f_kod1cb']     = "";
                      $ki1['f_kod1cp']     = "";
                      
                      $ki1['wens']=false;
                      $ki1['wsert']=false;
                      $ki1['wpost']=false;
                      $ki1['sbor']=false;
                      //$ki1['wto']=false;
                      $ki1['uved']=false;
                      $ki1['uvedins']=false;
                      $ki1['uvedto']=false;
                      $ki1['uvedcrt']=false;
                      $ki1['f_postid']=0;
                      
                      unset($ki1['id']);
                      //unset($ki1['curidx']);
                      unset($ki1['oper']);
                      unset($ki1['f_num']);
                      unset($ki1['tpdog']);
                      
                      $ar = json_decode(addRowTbl($ki1), true);
                      if($ar[0]=="true")
                        {
                        $ansn = $ansn." Добавили связанную заявку - Поручение ТП";$wdopmes = 1;
                        }
                      }
                    // ~sdid = 391
                    else
                      {
                      $cst = 0;
                      //echo $iskp."<br>";
                      if($iskp==1){$cst = 23;}
                      $ki = Array('curtbl'=>61,'f_specid'=>$kk,'f_status'=>$cst);
                      $ar = json_decode(addRowTbl($ki), true);
                      if($ar[0]=="true")
                        {
                        if($row['f_uvedto']==1)
                          {
                          //sdid 1546
                          $subject = "Для ".$iskps." создана запись информации о ДТ";
                          $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                          $sbsh = 0;
                          if($menomes==0) 
                            { 
                            $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                            if($sbsh == 0) 
                              { 
                              mSendMail($_SESSION['loginid'],22,0,"Для ".$iskps." создана запись информации о ДТ","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>","",61,$ar[2]);
                              }
                            }                     
                          //mSendMail($_SESSION['loginid'],22,0,"Для ".$iskps." создана запись информации о ДТ","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>","",61,$ar[2]);
                          //~sdid 1546
                          }
                        }
                      }
                    }*/
                  //~sdid 2084 tz2 2
                  //sdid 2084
                  ///////////////////////////////////
                  //if(($row['f_todog']>0) && ($row['f_subtype']!=3)) // Если поле Договор ТО не пуст и не 0 
                  if(($row['f_todog']>0)) // Если поле Договор ТО не пуст и не 0 //2024-03-12
                    {
                    $strtodog = (string) $row['f_todog']; //sdid 2084 tz2 2
                    if(substr($strtodog,0,1)==2) // Если выбрана текущая спецификация
                      {
                      //sdid 2290
                      $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;$typez = 0; 
                      if(isset($ik['typez'])) {$typez = $ik['typez'];} elseif(isset($ik['f_typez'])) {$typez = $ik['f_typez'];}
                      if($typez==1) {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                      $cst = 0;
                      if($iskp==1){$cst = 23;}

                      if($row['f_parentspecid']==0) // если лонли, создаем инф.о.ТО
                        {
                        // Создаем запись "Информация о ТО" по текущей спецификации
                        $ki = Array('curtbl'=>61,'f_specid'=>$kk,'f_status'=>$cst);
                        $ar = json_decode(addRowTbl($ki), true);
                        if(($ar[0]=="true") && ($row['f_uvedto']==1) && ($menomes==0)) //ЕСЛИ f_uvedto=1, уведомляем письмом!!
                          {
                          $subject = "Для ".$iskps." создана запись информации о ДТ";
                          $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                          $sbsh = 0;
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                          if($sbsh == 0) {mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$ar[2]);}
                          }
                        } 
                      //elseif($row['f_parentspecid']>0) // Иначе по parent вытягиваем данные: комментарий ТО, наличие иных связанных Информация о ТО, приыязанных к род.спец.
                      //  {
                      //  $sql = "select s.f_comisprep, s.f_ from veda_specs s where ";
                      //  }
                    //~sdid 2290
                      }
                    elseif(substr($strtodog,0,1)==3) // Если выбран договор ВА-ТП
                      {
                      //Создаем Заявку "Поручение ТП"
                      $tpdogparm = substr($strtodog,1);
                      //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                      $obj = new ArrayObject($ik);
                      $ki1 = $obj->getArrayCopy();
                      $ki1['curtbl']       = 15;
                      if(isset($ki1['f_parentspecid'])) {$ki1['f_parentspecid']=$kk;}
                      elseif(isset($ki1['parentspecid'])) {$ki1['parentspecid']=$kk;}
                      else {$ki1['f_parentspecid']=$kk;}
                      if(isset($ki1['f_dogid'])) {$ki1['f_dogid']=$tpdogparm;}
                      elseif(isset($ki1['dogid'])) {$ki1['dogid']=$tpdogparm;}
                      if(isset($ki1['f_subtype'])) {$ki1['f_subtype']=3;}
                      elseif(isset($ki1['subtype'])) {$ki1['subtype']=3;}
                      else{$ki1['f_subtype']=3;}
                      if(isset($ki1['f_dt'])) {$ki1['f_dt']=date("Y-m-d");}
                      elseif(isset($ki1['dt'])) {$ki1['dt']=date("Y-m-d");}
                      else{$ki1['f_dt']=date("Y-m-d");}
                      if(isset($ki1['f_typez'])) {$ki1['f_typez']=3;}
                      elseif(isset($ki1['typez'])) {$ki1['typez']=3;}
                      else{$ki1['f_typez']=3;}
                      if(isset($ki1['f_kod1cb'])) {$ki1['f_kod1cb']="";}
                      elseif(isset($ki1['kod1cb'])) {$ki1['kod1cb']="";}
                      if(isset($ki1['f_kod1cp'])) {$ki1['f_kod1cp']="";}
                      elseif(isset($ki1['kod1cp'])) {$ki1['kod1cp']="";}
                      if(isset($ki1['f_wens'])) {$ki1['f_wens']=false;}
                      elseif(isset($ki1['wens'])) {$ki1['wens']=false;}
                      if(isset($ki1['f_wsert'])) {$ki1['f_wsert']=false;}
                      elseif(isset($ki1['wsert'])) {$ki1['wsert']=false;}
                      if(isset($ki1['f_wpost'])) {$ki1['f_wpost']=false;}
                      elseif(isset($ki1['wpost'])) {$ki1['wpost']=false;}
                      if(isset($ki1['f_sbor'])) {$ki1['f_sbor']=false;}
                      elseif(isset($ki1['sbor'])) {$ki1['sbor']=false;}
                      if(isset($ki1['f_uvedins'])) {$ki1['f_uvedins']=false;}
                      elseif(isset($ki1['uvedins'])) {$ki1['uvedins']=false;}
                      if(isset($ki1['f_uvedto'])) {$ki1['f_uvedto']=false;}
                      elseif(isset($ki1['uvedto'])) {$ki1['uvedto']=false;}
                      if(isset($ki1['f_uvedcrt'])) {$ki1['f_uvedcrt']=false;}
                      elseif(isset($ki1['uvedcrt'])) {$ki1['uvedcrt']=false;}
                      if(isset($ki1['f_postid'])) {$ki1['f_postid']=0;}
                      elseif(isset($ki1['postid'])) {$ki1['postid']=0;}
                      else{$ki1['f_postid']=0;}
                      if(isset($ki1['f_todog'])) {$ki1['f_todog']=20;} //sdid 2296 2024-03-12
                      elseif(isset($ki1['todog'])) {$ki1['todog']=20;} //sdid 2296 2024-03-12
                      else{$ki1['f_todog']=20;}
                      if(isset($ki1['f_certdog'])) {$ki1['f_certdog']=0;} //sdid 2224 2024-03-24
                      elseif(isset($ki1['certdog'])) {$ki1['certdog']=0;} //sdid 2224 2024-03-24
                      else{$ki1['f_certdog']=0;}
                      if(isset($ki1['id'])) {unset($ki1['id']);}
                      if(isset($ki1['curidx'])) {unset($ki1['curidx']);}
                      if(isset($ki1['f_num'])) {unset($ki1['f_num']);}
                      if(isset($ki1['num'])) {unset($ki1['num']);}
                      if(isset($ki1['f_tpdog'])) {unset($ki1['f_tpdog']);}
                      if(isset($ki1['tpdog'])) {unset($ki1['tpdog']);}
                      $ar = json_decode(addRowTbl($ki1), true);
                      if($ar[0]=="true")
                        {
                        $newtpid = $ar[2]; //2024-02-19
                        $wdopmes = 1;
                        $ansn = $ansn."Добавили связанную заявку - Поручение ТП";
                        //sdid 2290 2297 2024-03-13
                        // Ищем связанную с родительской Информацию о ТО и привязываем ее к созданной ВА-ТП
                        $sql = "select f_id from veda_dt where f_specid=".$kk;
                        $res_dt = $dbh->query($sql);
                        if($row_dt = $res_dt->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>61,'curidx'=>$row_dt['f_id'],'f_specid'=>$newtpid); 
                          editRowTbl($ki);
                          }
                        else
                          {
                        //~sdid 2290 2297 2024-03-13
                          // Создаем новую запись "Информация о ТО" и привязываем ее к новой заявке "Поручение ТП"
                          $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                          $typez = 0;
                          if(isset($ik['typez'])) {$typez = $ik['typez'];}
                          elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                          if($typez==1)
                          {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                          $cst = 0;
                          if($iskp==1){$cst = 23;}
                          $ki = Array('curtbl'=>61,'f_specid'=>$ar[2],'f_status'=>$cst);
                          $ar = json_decode(addRowTbl($ki), true);
                          if($ar[0]=="true")
                            {
                            if($row['f_uvedto']==1)
                              {
                              if($menomes==0)
                                {
                                $subject = "Для ".$iskps." создана запись информации о ДТ";
                                //$postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                                //2024-02-19
                                $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$newtpid."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                                $sbsh = 0;
                                if($menomes==0) 
                                  { 
                                  $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                                  if($sbsh == 0) {mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$ar[2]);}
                                  }                     
                                }
                              }
                            }
                        //sdid 2290 2297 2024-03-13
                          }
                        //~sdid 2290 2297 2024-03-13
                        }
                      else
                        {
                        $wdopmes = 1;
                        $anns = $ansn."Ошибка добавления заявки - Поручение ТП";
                        }
                      }
                    }
                  ///////////////////////////////////
                  //~sdid 2084
                  if(($row['f_wpost']>0)&&($row['f_sbor']==0))//Признак «Поставки» - клиентом заказана услуга поставки товара
                    {
                    $ki = Array('curtbl'=>102,'f_contrrecid'=>0);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      $ki = Array('curtbl'=>15,'curidx'=>$kk,'f_postid'=>$ar[2]);
                      editRowTbl($ki);
                      if($row['f_uved']==1)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о доставке";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$ar[2]."\">Доставке</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 102, $ar[2], 151, 2, 14); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],2,0,$subject,$postbody,"",102,$ar[2]);
                            }
                          }                     
                        //mSendMail($_SESSION['loginid'],2,0,"Для ".$iskps." создана запись о доставке","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$ar[2]."\">Доставке</a>","",102,$ar[2]);
                        //~sdid 1546
                        }
                      }
                    //$sql = "insert into ".DBPref."shipments (f_userid,f_dttmcr) values (".$_SESSION['loginid'].",NOW())";
                    ////echo $sql;
                    //$dbh->exec($sql);
                    //$ki=$dbh->lastInsertId();
                    //if($ki>0)
                    //  {
                    //  $sql = "update ".DBPref."specs set f_postid=".$ki." where f_id=".$kk;
                    //  //echo $sql;
                    //  $dbh->exec($sql);
                    //  if($row['f_wpost']==1)
                    //    {mSendMail($_SESSION['loginid'],2,0,"Для ".$iskps." создана запись о поставке","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kkk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=90&obid=".$ki."\">Поставке</a>","",102,$ki);}
                    //  }
                    }
                  //sdid 1780
                  if(strlen($row['f_dttoclnt'])>0)
                    {  
                    $ki = Array('curtbl'=>15,'curidx'=>$kk,'f_dtuueol'=>$row['f_dttoclnt']);
                    editRowTbl($ki);
                    }
                  //~sdid 1780
                  }
                  // sdid 1429
                  $specs_update = [];
                  $sql_routes =   "SELECT COUNT(routes.f_id) r_count, COUNT(CASE routes.f_status WHEN 5 THEN 1 ELSE NULL END) s_count
                                  FROM ".DBPref."specs specs, ".DBPref."routes routes, ".DBPref."shipments shipments 
                                  WHERE specs.f_postid=shipments.f_id AND routes.f_postid=shipments.f_id AND specs.f_id=".$kk;
                  $res_routes = $dbh->query($sql_routes);

                  if($row_routes = $res_routes->fetch(PDO::FETCH_ASSOC))
                    {
                    if ($row_routes['r_count']==$row_routes['s_count'] && $row_routes['r_count']>0)
                      {
                      $specs_update['f_dtaddpergruz'] = date("Y.m.d", time()); // Дата внесения информации о передаче груза
                      }
                    }
                  if (!empty($specs_update))
                    {
                    $specs_update["curtbl"] = 15;
                    $specs_update["curidx"] = $kk;
                    editRowTbl($specs_update);
                    }
                  // ~sdid 1429
                }
              elseif(strcmp($tblname,DBPref."spec_details")==0)//После добавления - товары
                {
                $smt = 0;$sval=643;
                $sql = "select sum((f_count*f_price)) smt,f_val,sum(f_price_all) sall 
                        from ".DBPref."spec_details where f_specid=(select f_specid from ".DBPref."spec_details where f_id=".$kk.") group by f_specid";
                //echo $sql."|";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($smt>0){ $smt = $row['smt'];}
                  else {$smt = $row['sall'];}
                  $sval=$row['f_val'];}
                //echo $smt."|".$sval;
                $sql = "select f_status,f_id from ".DBPref."ensures where f_objtype=2 and f_objid in (select f_specid from ".DBPref."spec_details where f_id=".$kk.")";
                //echo $sql."|";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  //echo $row['f_status']."|";
                  if($row['f_status']==0)
                    {
                    $ki = Array('curtbl'=>54,'curidx'=>$row['f_id'],'f_enssump'=>$smt,'f_enssumpval'=>$sval);
                    editRowTbl($ki);
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."prjparts")==0)//После добавления - 
                {
                $sql = "select f_typeobj,f_zam,f_idobj,f_typerole,f_ismain,f_idsubj from ".$tblname." where f_id=".$kk;
                //echo $sql."|";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  //отрправляем уведомление сотруднику бухгалтерии, которого назначали замещающим бухгалтером по спецификации/заявке
                  if(($row['f_zam']==1)&&($row['f_typerole']==2)&&($row['f_typeobj']==1))
                    {
                    mSendMail($_SESSION['loginid'],0,$row['f_idsubj'],"По спецификации/заявке Вы назначены замещающим бухгалтером.",
                        "По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_idobj']."\">спецификации/заявке</a> Вы назначены замещающим бухгалтером. ","",0,$kk);
                    }
                  if($row['f_ismain']>0)
                    {
                    $sql = "update ".$tblname." set f_ismain=0 where f_typeobj=".$row['f_typeobj']." and f_idobj=".$row['f_idobj'].
                           " and f_typerole=".$row['f_typerole']." and f_id<>".$kk;
                    //echo $sql."|";
                    $dbh->exec($sql);
                    if(($row['f_typeobj']==1)&&($row['f_typerole']==1))
                      {
                      $sql = "update ".DBPref."specs set f_operid=".$row['f_idsubj']." where f_id=".$row['f_idobj'];
                      $dbh->exec($sql);
                      }
                    elseif(($row['f_typeobj']==1)&&($row['f_typerole']==2))
                      {
                      $sql = "update ".DBPref."specs set f_buhid=".$row['f_idsubj']." where f_id=".$row['f_idobj'];
                      //echo $sql."|";
                      $dbh->exec($sql);
                      }
                    elseif(($row['f_typeobj']==5)&&($row['f_typerole']==3))
                      {
                      $sql = "update ".DBPref."dt set f_operto=".$row['f_idsubj']." where f_id=".$row['f_idobj'];
                      $dbh->exec($sql);
                      }
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."clients")==0)//После добавления - 
                {
                $sql = "select f_id,f_matype from ".$tblname." where f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_matype']>0)
                    {
                    $sql = "insert into ".DBPref."categs (f_ctgtype,f_objectid,f_valstr,f_objecttype) values (1,".$kk.",'".$row['f_matype']."',2)";
                    $dbh->exec($sql);
                    }
                  }
//sdid 1727 
                // 2. При вводе нового контрагента с признаком "Наша организация" для видов операций с подвидом 0 реализовать добавление категории НДС по умолчанию 
                //    для новой организации в соответствии с выбранной СНО
                if($curtbl==26)
                  { // найдем типы операций с subtype=0 и id=контора, к которым не привязана категория НДС
                  $sql = "select f_isourorg,f_sno from ".$tblname." where f_id=".$kk;
                  $res2 = $dbh->query($sql);
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row2['f_isourorg']==true)
                      {  
                      $sql = "select t.f_id tfid,ifnull(ca.f_id,0) cfid
                                from 
                                  veda_spr sp86,
                                  veda_spr sp85,
                                  veda_typeopers t
                                left join veda_categs ca on 
                                  ca.f_ctgtype=7                # Вид категории - НДС 
                                 and ca.f_valint=".$kk ."
                                 and ca.f_objecttype=4          # veda_spr.f_type=68 f_num=4 (тип объекта - Вид операции)
                                 and ca.f_objectid=t.f_id
                               where 
                                 t.f_id>0 and t.f_bdrarticle>0 
                                 and sp86.f_type=86 and sp86.f_num=t.f_bdrarticle 
                                 and sp85.f_type=85 and sp85.f_num=sp86.f_uslint
                                 and sp85.f_dopprint=1";
                      $stavka = 0;
                      if($row2['f_sno']==2) {$stavka=0;}
                      else {$stavka=2;}
                      
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        { // к найденым операциям прикручиваем категорию НДС
                        if($row1['cfid']==0) 
                          {
                          $ki = Array('curtbl'=>211,
                                      //'curidx'=>$row['f_id'],
                                      'f_ctgtype'   =>7,
                                      'f_objectid'  =>$row1['tfid'],
                                      'f_valstr'    =>$stavka,
                                      'f_valint'    =>$kk,
                                      'f_objecttype'=>4,
                                      'f_objdtype'  =>0 
                                     );
                          addRowTbl($ki);
                          }
                        else
                          {
                          $ki = Array('curtbl'=>211,
                                      'curidx'=>$row1['cfid'],//$row1['tfid'],
                                      'f_valstr'    =>$stavka,
                                     );
                          editRowTbl($ki);
                          }   
                        }
                      }
                    }
                  }
//~sdid 1727 

                }
              elseif(strcmp($tblname,DBPref."contacts")==0)//После добавления - 
                {
                $sql = "select f_id,f_type from ".$tblname." where f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_type']>0)
                    {
                    $sql = "insert into ".DBPref."categs (f_ctgtype,f_objectid,f_valstr,f_objecttype) values (1,".$kk.",'".$row['f_type']."',1)";
                    $dbh->exec($sql);
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."acchist_docs")==0)//После добавления - документа к операции выписки
                {
                $sql = "select h.f_sum hsum,h.f_dt1C hdt,h.f_val hval,d.f_doctype,d.f_docid,d.f_acchistid,d.f_curs,h.f_type,d.f_clssum 
                          ,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) ovaln #sdid1583
                        from ".$tblname." d,".DBPref."acchist h where h.f_id=d.f_acchistid and d.f_id=".$kk;
                //echo $sql."|";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  //echo $row['f_doctype']."|";
                  $ppid = $row['f_acchistid'];
                  $pptp = $row['f_type'];
                  $cdid = $row['f_docid'];
                  $hval = $row['hval'];
                  //sdid1583
                  $ovaln  = $row['ovaln'];
                  $clssum = $row['f_clssum'];
                  //~sdid1583
                  $hsum = $row['hsum'];
                  $hdt  = substr($row['hdt'],0,10);
                  $curs = $row['f_curs'];
                  if($row['f_doctype']==1)//если привязали счет, считаем, что он оплачен, уведомляем инициатора
                    {
                    $chst = 3;
                    $ival = $hval;
                    $sql  = "select f_val,f_sum from ".DBPref."schets where f_id=".$cdid;
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$ival = $row1['f_val'];$isum = $row1['f_sum'];}
                    if($ival!=$hval){$hcursb=getCBRate($ival,$hdt);if($hcursb>1){if(round(($isum*($hcursb+$hcursb*0.01)),2)>$hsum){$chst=2;}}}
                    elseif($ival==$hval){if($isum>$row['f_clssum']){$chst=2;}}
                    //echo $hcursb."|".$hsum."|".$isum."|".($isum*($hcursb+$hcursb*0.01))."".$chst;
                    $ki = Array('curtbl'=>17,'curidx'=>$cdid,'f_status'=>$chst);
                    editRowTbl($ki);
                    $sql = "select i.f_userid operuser,s.f_id,s.f_orgid,s.f_contrid,s.f_num,s.f_dt,s.f_sum,(select f_namedop from ".DBPref."spr where f_type=4 and f_num=s.f_val) val,s.f_val,s.f_userid,s.f_dogtype,s.f_dogid, ".
                           "(select f_cname from ".DBPref."clients where f_id=s.f_orgid) orgid,".
                           "(select f_cname from ".DBPref."clients where f_id=s.f_contrid) contrid ".
                           "from ".DBPref."schets s,".DBPref."spec_invoices i where i.f_id=s.f_operid and s.f_id=".$row['f_docid'];
                    //echo $sql;
                    $res = $dbh->query($sql);
                    if($row = $res->fetch(PDO::FETCH_ASSOC))
                      {
                      mSendMail($_SESSION['loginid'],0,$row['operuser'],"Прошла оплата счета на сумму ".$row['f_sum']." ".$row['val'],
                        "Прошла оплата <a href=\"".$redirect_uri."?pgid=17&obid=".$row['f_id']."\">счета ".$row['f_num']." от ".$row['f_dt']."</a><br>Контрагент: ".$row['contrid'],"",17,$kk);
                      }
                    }
                  elseif($row['f_doctype']==3)//если привязали операцию
                    {
                    $acchistid  = $row['f_acchistid'];
                    $acchistval = $row['hval'];
                    $sql = "select s.f_parenttype,s.f_specid,s.f_wloans,s.f_dtid,s.f_com,s.f_contrid,s.f_orgid,s.f_userid,s.f_id,".
                           "s.f_idoper,s.f_sum,s.f_val,(select f_namedop from ".DBPref."spr where f_type=4 and f_num=s.f_val) val, ".
                           "(select f_cname from ".DBPref."clients where f_id=s.f_contrid) contrid,
                            t.f_ppuved,t.f_ppuvedt,t.f_ppuvedadr,t.f_ppuvedshablon,t.f_ppuvedequalvals,t.f_ppuvedsubjshablon,
                            s.f_parentid,#sdid1583
                            t.f_name tname ".
                           "from ".DBPref."spec_invoices s,".DBPref."typeopers t where t.f_id=s.f_idoper and s.f_id=".$row['f_docid'];
                    //echo $sql."|";
                    $res = $dbh->query($sql);
                    if($row = $res->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row['f_idoper']==72)//если комиссия за покупку валюты привязывалась, то ставим тип в ПП - Комиссия за покупку валюты
                        {
                        $ki = Array('curtbl'=>84,'curidx'=>$acchistid,'f_ahtype'=>6);
                        editRowTbl($ki);
                        }
                      $sql = "select f_id from ".DBPref."typeopers where upper(f_name) like upper('%поручение на оплату%') and f_isuse=1 and f_id=".$row['f_idoper'];
                      //echo $sql."|";
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        mSendMail($_SESSION['loginid'],0,$row['f_userid'],"Произведена оплата поручения на сумму ".$row['f_sum']." ".$row['val'],
                          "Оплачено <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">поручение</a><br>Для контрагента: ".$row['contrid'],"",35,$row['f_id']);
                        }
                      //echo $row['f_ppuved']."|";
                      if($row['f_ppuved']==1)//отправляем уведомления при привязке операций к выписке
                        {
                        $needuved = 1;
                        if($row['f_ppuvedequalvals']==1)//Признак уведомления при привязки документа выписки только при совпадении валют
                          {if($acchistval!=$row['f_val']){$needuved = 0;}}
                        if($needuved==1)
                          {
                          $tousr = $row['f_ppuvedadr'];
                          $togrp = 0;
                          if($row['f_ppuvedt']==2){$togrp = $row['f_ppuvedadr'];}
                          //echo "usr:".$tousr."|grp:".$togrp."|";
//sdid 887
                          if($row['f_ppuvedsubjshablon']>0)
                            {$subject = lGetPostSubjShablon($row['f_ppuvedsubjshablon'], 0, 0, 0, 0, 6, $row['f_id']);}
                          else
                            {$subject = "Поступила оплата по операции ".$row['tname']." на сумму ".$row['f_sum']." ".$row['val'];}
                        
                          $postbody = "Выполнена <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">операция ".$row['tname']."</a><br>Контрагент: ".$row['contrid'];
                        
                          if($row['f_ppuvedshablon']>0)
                            {$postbody = $postbody . "<br>" . lGetPostShablon($row['f_ppuvedshablon'], 0, 0, 0, 0, 6, $row['f_id']);}
                        
                          mSendMail($_SESSION['loginid'],$togrp,$tousr,$subject,$postbody,"",35,$row['f_id']);
// ----------------------- orig
//                          mSendMail($_SESSION['loginid'],$togrp,$tousr,"Поступила оплата по операции ".$row['tname']." на сумму ".$row['f_sum']." ".$row['val'],
//                            "Выполнена <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">операция ".$row['tname']."</a><br>Контрагент: ".$row['contrid'],"",35,$row['f_id']);
// ----------------------- ~orig
//~sdid 887
                          }
                        }
                      if($row['f_idoper']==53)//поручение не покупку валюты
                        {
//sdid 887
                        if($row['f_ppuvedsubjshablon']>0)
                          {$subject = lGetPostSubjShablon($row['f_ppuvedsubjshablon'], 0, 0, 0, 0, 6, $row['f_id']);}
                        else
                          {$subject = "Произведена покупка валюты на сумму ".$row['f_sum']." ".$row['val'];}

                        $postbody = "Выполнено <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">поручение на покупку валюты</a><br>Для контрагент: ".$row['contrid'];

                        if($row['f_ppuvedshablon']>0)
                          {$postbody = $postbody . "<br>" . lGetPostShablon($row['f_ppuvedshablon'], 0, 0, 0, 0, 6, $row['f_id']);}

                        mSendMail($_SESSION['loginid'],0,$row['f_userid'],$subject,$postbody,"",35,$row['f_id']);
// ----------------------- orig
                    //    mSendMail($_SESSION['loginid'],0,$row['f_userid'],"Произведена покупка валюты на сумму ".$row['f_sum']." ".$row['val'],
                    //      "Выполнено <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">поручение на покупку валюты</a><br>Для контрагент: ".$row['contrid'],"",35,$row['f_id']);
// ----------------------- ~orig
//~sdid 887
                        }
                      elseif($row['f_idoper']==54)//Поручение на валютный перевод
                        {
//sdid 887
                        if($row['f_ppuvedsubjshablon']>0)
                          {$subject = lGetPostSubjShablon($row['f_ppuvedsubjshablon'], 0, 0, 0, 0, 6, $row['f_id']);}
                        else
                          {$subject = "Произведен валютный перевод ".$row['f_sum']." ".$row['val'];}

                        $postbody = "Выполнен <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">валютный перевод</a><br>Для контрагент: ".$row['contrid'];

                        if($row['f_ppuvedshablon']>0)
                          {$postbody = $postbody . "<br>" . lGetPostShablon($row['f_ppuvedshablon'], 0, 0, 0, 0, 6, $row['f_id']);}
// ----------------------- orig
//                        mSendMail($_SESSION['loginid'],0,$row['f_userid'],"Произведен валютный перевод ".$row['f_sum']." ".$row['val'],
//                          "Выполнен <a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">валютный перевод</a><br>Для контрагент: ".$row['contrid'],"",35,$row['f_id']);
// ----------------------- ~orig
//~sdid 887
                        //sdid1583
                        if($row['f_parentid']>0)
                          {
                          $sql = "select f_id,f_idoper from ".DBPref."spec_invoices where f_idoper=387 and (f_id=".$row['f_parentid']." or f_id=(select f_parentid from ".DBPref."spec_invoices where f_idoper=53 and f_id=".$row['f_parentid']."))";
                          $res1 = $dbh->query($sql);
                          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если привязали документ выписки к операции "Оплата товара. Поручение на валютный перевод", порожденной "Товар. В ДОЛГ. Оплата", то создаем корректировку
                            {
                            if($row1['f_idoper']==387)
                              {
                              $sql = "select si.*, 
                                        sd.f_id sdid,sd.f_contrid sdcontrid,
                                        case
                                          when (select max(f_dttmsrok) from ".DBPref."spec_invoices where f_idoper=386 and f_parenttype=2 and f_specid=s.f_id and f_dttmsrok<'$hdt') IS NOT NULL and 
                                               (select max(f_dttmsrok) from ".DBPref."spec_invoices where f_idoper=386 and f_parenttype=2 and f_specid=s.f_id and f_dttmsrok<'$hdt')<DATE_SUB('$hdt', INTERVAL 1 MONTH) then
                                            DATE_SUB(CAST(DATE_FORMAT('$hdt','%Y-%m-01') as DATE),INTERVAL 1 DAY)
                                          when (select max(f_dttmsrok) from ".DBPref."spec_invoices where f_idoper=386 and f_parenttype=2 and f_specid=s.f_id and f_dttmsrok<'$hdt') IS NOT NULL then
                                            (select max(f_dttmsrok) from ".DBPref."spec_invoices where f_idoper=386 and f_parenttype=2 and f_specid=s.f_id and f_dttmsrok<'$hdt')
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' and s.f_perpravdt<'$hdt' and MONTH(s.f_perpravdt)<>MONTH('$hdt') then 
                                            DATE_SUB(CAST(DATE_FORMAT('$hdt','%Y-%m-01') as DATE),INTERVAL 1 DAY)
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' then s.f_perpravdt
                                          else si.f_dttmcr
                                        end dtfrom,
                                        case
                                          when (select max(f_dttmsrok) from ".DBPref."spec_invoices where f_idoper=386 and f_parenttype=2 and f_specid=s.f_id and f_dttmsrok<'$hdt') IS NOT NULL then 
                                            'курс ЦБ на дату последней переоценки'
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' and s.f_perpravdt<'$hdt' and MONTH(s.f_perpravdt)<>MONTH('$hdt') then 
                                            'курс ЦБ на дату последней переоценки'
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' then 'курс ЦБ на дату ППС'
                                          else 'курс ЦБ на дату возникновения долга'
                                        end dttext
                                      from ".DBPref."spec_invoices si, ".DBPref."specs s, ".DBPref."dogs sd 
                                      where s.f_id=si.f_specid and sd.f_id=s.f_dogid and si.f_parenttype=2 and si.f_id=".$row['f_id'];
                              //echo "$sql|";
                              $res1 = $dbh->query($sql);
                              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                                {
                                $sum_korr = round(round($clssum,2)*(getcbrate($hval,$hdt) - getcbrate($hval, $row1['dtfrom'])),2);
                                $oprcomm = round($clssum,2)." ".$ovaln." курс ЦБ на дату перевода "
                                           .date("d.m.Y",strtotime($hdt))." - ".getcbrate($hval, $hdt)." ".$row1['dttext']." "
                                           .date("d.m.Y",strtotime($row1['dtfrom']))." - ".getcbrate($hval, $row1['dtfrom']).
                                           " Расчет курсовой разницы: ".round($clssum,2)."*(".getcbrate($hval, $hdt)."-".getcbrate($hval, $row1['dtfrom']).")=$sum_korr руб.";
                                //echo "$oprcomm|$sum_korr";
                                if($sum_korr!=0)
                                  {
                                  $moprret = json_decode(makeOper(2,$row1['f_specid'],$hdt,"",0,$sum_korr,643,0,1,"",
                                         "","",0,$oprcomm,$row['f_id'],388,$row1['f_contrid'],$row1['f_orgid'],$row1['f_dogid'],0,"",
                                         0,0,0,0,-5,"","",181,"",0,
                                         "","",2,0, 0,0,0,0,0,"0000-00-00","0000-00-00",""),true);
                                  if($moprret[0]=="true")//создали операцию корректировки
                                    {
                                    //создаем корректировку
                                    $kai  = Array('curtbl'     => 190
                                                 ,'f_orgid'    => $row1['f_orgid']
                                                 ,'f_dt'       => $hdt
                                                 ,'f_type'     => 2
                                                 ,'f_status'   => 1
                                                 ,'f_com'      => 'Курсовая разница по кредиторской задолженности в конце месяца'
                                                 ,'f_orgaccid' => $row1['f_orgaccid']
                                                 );
                                    $ar   = json_decode(addRowTbl($kai), true);
                                    if($ar[0]=="true")//создаем фин документ курсовой разницы
                                      {
                                      $bcontr   = 0;
                                      $bdogid   = 0;
                                      $orgaccid = 0;
                                      $sql  = "SELECT d.f_bankid,bc.f_id 
                                                 ,case 
                                                    when ifnull((select f_id from ".DBPref."bank_accounts ba 
                                                          where ba.f_clntid=d.f_orgid and ba.f_val=d.f_valdog and 
                                                            ba.f_bic=(select f_valstr from ".DBPref."categs where f_ctgtype=5 and f_objecttype=3 and f_objectid=bc.f_id) 
                                                            limit 1),0)=0 then
                                                      ifnull((select f_id from ".DBPref."bank_accounts ba 
                                                          where ba.f_clntid=d.f_orgid and ba.f_val=bc.f_valdog and 
                                                            ba.f_bic=(select f_valstr from ".DBPref."categs where f_ctgtype=5 and f_objecttype=3 and f_objectid=bc.f_id) 
                                                            limit 1),0)
                                                    else
                                                      ifnull((select f_id from ".DBPref."bank_accounts ba 
                                                          where ba.f_clntid=d.f_orgid and ba.f_val=d.f_valdog and 
                                                            ba.f_bic=(select f_valstr from ".DBPref."categs where f_ctgtype=5 and f_objecttype=3 and f_objectid=bc.f_id) 
                                                            limit 1),0)
                                                  end orgaccid
                                               FROM ".DBPref."dogs d,".DBPref."dogs bc
                                               where bc.f_contrid=d.f_bankid and bc.f_orgid=d.f_orgid and bc.f_dogtype=10 and bc.f_valdog=643 and 
                                                 (select count(*) from ".DBPref."categs where f_ctgtype=27 and f_objecttype=3 and f_objectid=bc.f_id)>0 and d.f_id=".$row1['f_dogid'];
                                      //echo $sql."<br>";
                                      $res2 = $dbh->query($sql);
                                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                                        {
                                        $bcontr   = $row2['f_bankid'];
                                        $bdogid   = $row2['f_id'];
                                        $orgaccid = $row2['orgaccid'];
                                        }
                                      if($orgaccid>0)
                                        {
                                        $ki = Array('curtbl'=>190,'curidx'=>$ar[2],'f_orgaccid'=>$orgaccid);
                                        editRowTbl($ki);
                                        $dtacchistid = 0;
                                        $ktacchistid = 0;
                                        if($sum_korr<0)
                                          {$debet="52";                $kredit="76.06";
                                           $dtval=$row['f_val'];       $ktval=0;
                                           $dtsum=$sum_korr*(-1);      $ktsum=0;
                                           $dtdogtype=4;               $ktdogtype=2;
                                           $dtcontr=$bcontr;           $ktcontr=$row1['sdcontrid'];
                                           $dtdogid=$bdogid;           $ktdogid=$row1['f_specid'];}
                                        else
                                          {$debet="76.06";             $kredit="52";
                                           $dtval=0;                   $ktval=$row['f_val'];
                                           $dtsum=0;                   $ktsum=$sum_korr;
                                           $dtdogtype=2;               $ktdogtype=4;
                                           $dtcontr=$row1['sdcontrid']; $ktcontr=$bcontr;
                                           $dtdogid=$row1['f_specid'];  $ktdogid=$bdogid;}
                                        $OP   = getOPorgFrom1C($row1['f_orgid'],$row1['f_orgdivision']);
                                        $kai  = Array('curtbl'=>191,'f_difrateid'=>$ar[2],'f_debet'=>$debet,'f_kredit'=>$kredit,'f_dtunit'=>$OP,
                                                      'f_ktunit'=>$OP,
                                                      'f_dtcontr'=>$dtcontr,'f_ktcontr'=>$ktcontr,
                                                      'f_dtdogtype'=>$dtdogtype,'f_ktdogtype'=>$ktdogtype,
                                                      'f_dtdogid'=>$dtdogid,'f_ktdogid'=>$ktdogid,
                                                      'f_dtacchistid'=>$dtacchistid,'f_ktacchistid'=>$ktacchistid,
                                                      'f_dtsum'=>$dtsum,'f_ktsum'=>$ktsum,
                                                      'f_grnd'=>$oprcomm
                                                      ,'f_dval'=>$dtval,'f_kval'=>$ktval
                                                      ,'f_operid'=>$moprret[2],'f_orgaccid'=>$orgaccid
                                                     );
                                        $ar   = json_decode(addRowTbl($kai), true);
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            }
                          }
                        //~sdid1583
                        }
                      //Таможенные платежи: ... Поручение на пополнение ЕЛС
                      elseif(($row['f_idoper']==76)||($row['f_idoper']==78)||($row['f_idoper']==80)||
                         ($row['f_idoper']==99)||($row['f_idoper']==122)||($row['f_idoper']==124)||($row['f_idoper']==178)
                         )//привязали поручение на пополнение ЕЛС
                        {
                        $tppayc=2;
                        if($row['f_idoper']==78){$tppayc=1;}
                        if($row['f_idoper']==99){$tppayc=3;}
                        $ki = Array('curtbl'=>63,'f_orgid'=>$row['f_orgid'],'f_typepay'=>$tppayc,'f_dtid'=>$row['f_dtid'],'f_ppid'=>$ppid,'f_ppdt'=>'CURDATE()','f_outsum'=>0,
                                    'f_ppsum'=>$row['f_sum'],'f_com'=>$row['f_com'],'f_idoper'=>$row['f_id']);
                        $ar = json_decode(addRowTbl($ki), true);
                        if($ar[0]=="true")
                          {$answ = $answ."Создана операция по ЕЛС<br>";}
                        }
                      elseif($row['f_idoper']==176)//Таможенные платежи: Задолженность ТП по заявлению
                        {
                        $sql = "select dt.f_id dtid,dt.f_zayavsum,dt.f_specid,d.f_orgid,d.f_contrid,".
                               "  ifnull((select count(*) from ".DBPref."spec_invoices where f_idoper in (177,178) and f_parenttype=2 and ".
                               "            f_specid=dt.f_specid),0) zocnt ".
                               "from ".DBPref."dt dt,".DBPref."specs s,".DBPref."dogs d ".
                               "where dt.f_specid=s.f_id and d.f_id=s.f_dogid and s.f_id=".$row['f_specid'];
                        $res1 = $dbh->query($sql);
                        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {
                          if(($row1['f_zayavsum']>0)&&($row1['zocnt']==0))
                            {
                            //Таможенные платежи: Задолженность ТП по заявлению. Списание с ЕЛС
                            makeOper(2,$row['f_specid'],"","",0,$row1['f_zayavsum'],643,0,1,"",
                                   "","",0,"",0,177,$row1['f_contrid'],$row1['f_orgid'],0,0,"",
                                   0,0,0,0,-5,"","",185,"",0,
                                   "","",2,$row1['dtid'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                            //Таможенные платежи: Задолженность ТП по заявлению. Поручение на пополнение ЕЛС
                            makeOper(2,$row['f_specid'],"","",0,$row1['f_zayavsum'],643,0,1,"",
                                   "","",0,"",0,178,$row1['f_contrid'],$row1['f_orgid'],0,0,"",
                                   0,0,0,0,-5,"","",185,"",0,
                                   "","",2,$row1['dtid'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                            }
                          }
                        }
                      //echo $row['f_wloans']."|".$row['f_parenttype']."|".$row['f_specid']."|";
                      //sdid - 1066
                      //if(($row['f_wloans']==1)&&($row['f_parenttype']==2)&&($row['f_specid']>0))//если привязали к операции с отметкой кредита
                      //  {
                      //  $lnsid = 0;
                      //  $sql1 = "select f_id,f_prc,f_val,f_minamount,f_sumcom,f_sumcomval from ".DBPref."lns ".
                      //          "where f_specid=".$row['f_specid'];
                      //  $res1 = $dbh->query($sql1);
                      //  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      //    {
                      //    $dtp = 0;
                      //    if($pptp==1)//если списали средства - оплата
                      //      {$dtp = 1;}
                      //    elseif($pptp==0)//если зачислили средства - возмещение
                      //      {$dtp = 2;}
                      //    $ki = Array('curtbl'=>171,'f_lnsid'=>$row1['f_id'],'f_doctype'=>$dtp,'f_docid'=>$cdid);
                      //    $ar = json_decode(addRowTbl($ki), true);
                      //    if($ar[0]=="true")
                      //      {$answ = $answ."Создана операция по финансированию<br>";}
                      //    if($pptp==0)//если все возместили делаем операцию оплаты комиссии
                      //      {
                      //      //$sql2 = "select count(*) cnt from veda_lns_docs ld ".
                      //      //        "where ld.f_doctype=4 and (select count(*) from veda_acchist_docs where f_doctype=3 and ".
                      //      //        "  f_docid=ld.f_docid and f_iscls=1)=0 and ld.f_lnsid=".$row1['f_id'];
                      //      //$res2 = $dbh->query($sql2);
                      //      //if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      //      //  {
                      //      //  if($row2['cnt']==0)
                      //      //    {
                      //          if($row1['f_sumcom']>0)
                      //            {
                      //            makeOper(2,$row['f_specid'],"","",0,$row1['f_sumcom'],$row1['f_val'],0,2,"",
                      //                    "","",0,"Комиссия за пользование денежными средствами",0,101,$row['f_contrid'],$row['f_orgid'],0,0,"",
                      //                    0,1,1,0,-5,"","",175,"Комиссия за пользование денежными средствами",0,
                      //                    "","",0,$row1['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      //            }
                      //          if($row1['f_prc']>0)
                      //            {
                      //            $osum = 0;
                      //            $sql3 = "select sum(f_sum) osm from veda_spec_invoices where f_parenttype=2 and f_specid=".$row['f_specid']." and f_id in (select d.f_docid from veda_lns_docs d,veda_lns l where l.f_id=d.f_lnsid and d.f_doctype=3 and l.f_specid=".$row['f_specid'].")";
                      //            $res3 = $dbh->query($sql3);
                      //            if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                      //              {$osum = $row3['osm'];}
                      //            $csum = 0;
                      //            $sql3 = "select DATE_FORMAT(oph.f_dt1C,'%Y-%m-%d') dte,ophd.f_clssum smfprc,oih.f_ppdt dtb ".
                      //                    "from   veda_spec_invoices oi,veda_spec_invoices op,veda_lns_docs oid,veda_lns_docs opd, ".
                      //                    "       veda_acchist_docs oihd, veda_acchist_docs ophd,veda_acchist oih, veda_acchist oph ".
                      //                    "where  oi.f_parenttype=2 and op.f_parenttype=2 and oi.f_specid=".$row['f_specid']." and op.f_specid=".$row['f_specid']." and oi.f_id=op.f_parentid and ".
                      //                    "       oid.f_doctype=4 and oi.f_id=oid.f_docid and opd.f_doctype=1 and op.f_id=opd.f_docid and ".
                      //                    "       oihd.f_doctype=3 and oihd.f_docid=oi.f_id and ophd.f_doctype=3 and ophd.f_docid=op.f_id and ".
                      //                    "       oih.f_id=oihd.f_acchistid and oph.f_id=ophd.f_acchistid";
                      //            //echo $sql3."|";
                      //            $res3 = $dbh->query($sql3);
                      //            while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                      //              {//echo $row3['smfprc']."_".$row1['f_prc']."_".$row3['dtb']."_".$row3['dte']."|";
                      //               $csum = $csum+round(($row3['smfprc']*$row1['f_prc']/365/100)*countDaysBetweenDates($row3['dtb'],$row3['dte']),2);}
                      //            //echo $csum."|";
                      //            $csum = $csum+$row1['f_sumcom']-$osum;
                      //            if($csum<$row1['f_minamount']){$csum=$row1['f_minamount'];}
                      //            if($csum>0)
                      //              {
                      //              makeOper(2,$row['f_specid'],"","",0,$csum,$row1['f_val'],0,2,"",
                      //                      "","",0,"Комиссия за пользование денежными средствами",0,101,$row['f_contrid'],$row['f_orgid'],0,0,"",
                      //                      0,1,1,0,-5,"","",175,"Комиссия за пользование денежными средствами",0,
                      //                      "","",0,$row1['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      //              }
                      //            }
                      //      //    }
                      //      //  }
                      //      }
                      //    }
                      //  }
                      //~sdid - 1066
                      }
                    //echo $answ;
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."routes")==0)//После добавления - запись в маршрут
                {
                $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
                $dbh->exec('SET CHARACTER SET utf8');
//sdid 1140
//                $sql = "select f_status,f_parentid,f_postid from $tblname where f_id=".$kk;
                //sdid 2084 tz2 2
                //$sql = "select f_status,f_parentid,f_postid,ifnull(date(f_p2dt),'0000-00-00') f_p2dt from $tblname where f_id=".$kk; 
                //sdid 3131
                //$sql = "select f_status,f_parentid,f_postid,ifnull(f_p1dt,'0000-00-00') f_p1dt,ifnull(f_p2dt,'0000-00-00 00:00:00') f_p2dt, f_p2iscustom,f_routetype,f_com from $tblname where f_id=".$kk;//sdid2860
                $sql = "select f_status,f_parentid,f_postid,ifnull(f_p1dt,'0000-00-00') f_p1dt,ifnull(f_p2dt,'0000-00-00 00:00:00') f_p2dt, 
                               f_p2iscustom,f_routetype,f_com,f_p1dtppsdt from $tblname where f_id=".$kk;//sdid2860
                //~sdid 3131
                //~sdid 2084 tz2 2
//~sdid 1140
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row['f_status']==3)&&($row['f_parentid']==0))//Готов к доставке
                    {
                    $ap   = 1;
                    $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($row['f_status']!=$row1['f_status']){$ap=0;}}
                    if($ap==1)
                      {
                      $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>1);
                      editRowTbl($ki);
                      }
                    }
                  elseif(($row['f_status']==1)&&($row['f_parentid']==0))//если статус выполнение
                    {
                    $ap   = 1;
                    $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($row['f_status']!=$row1['f_status']){$ap=0;}}
                    if($ap==1)
                      {
                      $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>2);
                      editRowTbl($ki);
                      }
                    }
                  //sdid 1971
                  if($row['f_status'] == 2)
                    {
                    $sql_rs = "SELECT DISTINCT s.f_id 
                               FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                               WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                 AND r.f_id = ".$kk;
                    $conn_rs = $dbh->query($sql_rs);
                    while ($row_rs = $conn_rs->fetch(PDO::FETCH_ASSOC)) // Берем каждую спецификацию связанную с маршрутом
                      {
                      $spec_id = $row_rs['f_id'];
                      $spec_update = ['curtbl' => 15, 'curidx' => $spec_id, 'f_status' => 15]; // Выставляем статус "В пути"
                      $update_result = editRowTbl($spec_update);
                      }
                    }
                  //~ sdid 1971
                  //sdid 3131
                  if($row['f_status'] == 5) // статус Прибыл
                    {
                     if(($row['f_postid']>0)&&($row['f_p1dtppsdt']==1))  
                       {
                       $sql_rs = "SELECT DISTINCT s.f_id 
                                  FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                                  WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                    AND r.f_id = ".$kk;
                       $conn_rs = $dbh->query($sql_rs);
                       while($row_rs = $conn_rs->fetch(PDO::FETCH_ASSOC)) // Берем каждую спецификацию связанную с маршрутом
                         {
                         $spec_update = ['curtbl'=>15,'curidx'=>$row_rs['f_id'],'f_perpravdt'=>$row['f_p1dt']]; 
                         $update_result = editRowTbl($spec_update);
                         }
                       }
                    }
                  //~sdid 3131
                  //sdid 3273
                  // Если новый маршрут с установленной галкой "Дата отправки = Дата ППС", снимаем галки во всех остальных маршрутах в поставке
                  if(($row['f_postid']>0)&&($row['f_p1dtppsdt']==1))
                    {
                    $sql = "select f_id from veda_routes where f_postid=".$row['f_postid']." and f_id<>$kk";
                    $res2 = $dbh->query($sql);
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      $r_update = ['curtbl'=>77,'curidx'=>$row2['f_id'],'f_p1dtppsdt'=>0]; 
                      $r_result = editRowTbl($r_update);
                      }
                    }
                  //~sdid 3273
                  //sdid 2084 tz2 2
                  // Если маршрут с признаком ТО, прописываем дату прибытия в связанных спецификациях
                  if($row['f_p2iscustom'] == 1)
                    {
                    $sql_rs = "SELECT DISTINCT s.f_id 
                               FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                               WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                 AND r.f_id = ".$kk;
                    $conn_rs = $dbh->query($sql_rs);
                    while ($row_rs = $conn_rs->fetch(PDO::FETCH_ASSOC)) // Берем каждую спецификацию связанную с маршрутом
                      {
                      $spec_id = $row_rs['f_id'];
                      $spec_update = ['curtbl' => 15, 'curidx' => $spec_id, 'f_dtarrivalto' => $row['f_p2dt']]; // Прописываем дату прибытия в пункт ТО
                      $update_result = editRowTbl($spec_update);
                      }
                    }
                  //~sdid 2084 tz2 2
                  //sdid2860
                  if($row['f_routetype']==5)
                    {
                    if((strcmp($row['f_p2dt'],"0000-00-00 00:00:00")!=0)||(strcmp($row['f_p1dt'],"0000-00-00")!=0))
                      {
                      $subject = "Установлена дата отправления/прибытия по маршруту ЖД РФ";
                      if(strcmp($row['f_p1dt'],"0000-00-00")===0){$p1dt="пусто";}
                      else{$p1dt=format_dt($odt1,0,0);}
                      if((strcmp($row['f_p2dt'],"0000-00-00")===0)||(strcmp($row['f_p2dt'],"0000-00-00 00:00:00")===0)){$p2dt="пусто";}
                      else{$p2dt=format_dt($row['f_p2dt'],0,0)." ".substr($row['f_p2dt'],11,8);}
                      $p1dtstr  = "Дата отправления: ".$p1dt."<br>";
                      $p2dtstr  = "Дата прибытия: ".$p2dt."<br>";
                      $postbody = "Установлена дата отправления/прибытия по маршруту ЖД РФ:<br>".
                                  $p1dtstr."".
                                  $p2dtstr.
                                  "Комментарий: ".$row['f_com']."<br>".
                                  "<a href=\"".$redirect_uri."?pgid=77&obid=".$kk."\">ссылка на маршут</a><br>";
                      $sbsh = 0;
                      if($menomes==0){$sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 77, $kk, 151, 9, 17);}
                      if($sbsh==0){mSendMail($_SESSION['loginid'],18,0,$subject,$postbody,"",77,$kk);}
                      }
                    }
                  //~sdid2860
//sdid 1140
                  // если добавлен маршрут, меняем "Дату передачи груза клиенту" в связанных спецификациях
                  // и направляем соответствующие уведомления в бух.
                  $sql = "select rs.f_specid, 
                                 concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname,
                                 ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt,
                                 r.f_status
                            from veda_routes_spec rs, veda_specs s, veda_dogs d, veda_routes r ". 
                          "where s.f_id=rs.f_specid and d.f_id=s.f_dogid and r.f_id=rs.f_routeid and rs.f_routeid=".$kk." order by f_specid";
                          //"where s.f_id=rs.f_specid and d.f_id=s.f_dogid and r.f_id=rs.f_routeid and r.f_status=5 and rs.f_routeid=".$kk;
                  $cntRoutes=0;
                  $curSpecId=0;
                  $cntSpecId=0;
                  $sId=[]; $sName=[]; $sMode=[]; //$sDate = [];

                  $res1 = $dbh->query($sql);
                  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    { $cntRoutes++;
                      if($cntSpecId>0 && $row1['f_specid']==$curSpecId) {continue;}

                      if((strcmp($row1['f_dttoclnt'],"0000-00-00") != 0) && ($row1['f_status'] == 5)) // если в спецификации присутствует дата и связанный маршрут доставлен
                         { if($row['f_status'] != 5) // если маршрут не доставлен, аннулируем "Дату передачи груза клиенту" в связанных спецификациях
                             { $curSpecId=$row1['f_specid'];
                               $sId[$cntSpecId]=$row1['f_specid']; $sName[$cntSpecId]=$row1['specname']; $sMode[$cntSpecId]=1;
                               $cntSpecId++; }
                           else // если маршрут доставлен, если дата в спец. меньше даты добавленного маршрута, обновляем дату в спец.
                             { if(strtotime($row1['f_dttoclnt']) < strtotime($row['f_p2dt']))
                                 { $curSpecId=$row1['f_specid'];
                                   $sId[$cntSpecId]=$row1['f_specid']; $sName[$cntSpecId]=$row1['specname']; $sMode[$cntSpecId]=2;
                                   $cntSpecId++; }
                             }
                         }
                      //$cntRoutes++;
                    }
                  if($cntRoutes == 0) // если не нашли ничего в связке маршрут-спецификация, ищем через поставку
                    {
                      $sql = "select s.f_id,
                                     concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname,
                                     ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt
                                from veda_routes r, veda_specs s, veda_dogs d 
                               where s.f_postid=r.f_postid and d.f_id=s.f_dogid and r.f_status=5 and r.f_id=".$kk." order by f_id";
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        { $cntRoutes++;
                          if($cntSpecId>0 && $row1['f_id']==$curSpecId) {continue;}

                          if(strcmp($row1['f_dttoclnt'],"0000-00-00") != 0) // если в спецификации присутствует дата
                            { if($row['f_status'] != 5) // если маршрут не доставлен, аннулируем "Дату передачи груза клиенту" в связанных спецификациях
                                { $curSpecId=$row1['f_id'];
                                  $sId[$cntSpecId]=$row1['f_id']; $sName[$cntSpecId]=$row1['specname']; $sMode[$cntSpecId]=1;
                                  $cntSpecId++;
                                } 
                              else // если маршрут доставлен, если дата в спец. меньше даты добавленного маршрута, обновляем дату в спец.
                                { if(strtotime($row1['f_dttoclnt']) < strtotime($row['f_p2dt']))
                                    { $curSpecId=$row1['f_id'];
                                      $sId[$cntSpecId]=$row1['f_id']; $sName[$cntSpecId]=$row1['specname']; $sMode[$cntSpecId]=2;
                                      $cntSpecId++;
                                    }
                                }
                            }
                          //$cntRoutes++;
                        }
                    }
                  if(is_countable($sId))
                    {
                      if(count($sId)>0)
                        {
                          $ii=0;
                          while($ii < count($sId))
                            {
                              if($sMode[$ii]==1)
                                {
                                  $ki = Array('curtbl'=>15,'curidx'=>$sId[$ii],'f_dttoclnt'=>"0000-00-00");
                                  editRowTbl($ki);
                                  
                                  $subject = "В Спец/Заявке № ".$sName[$ii]." добавлен маршрут";
                                  $postbody = "В Спец/Заявке № <a href=\"".$redirect_uri."?pgid=15&obid=".$sId[$ii]."\">".
                                              $sName[$ii]."</a> добавлен маршрут, дата получения товара аннулирована";
                                  mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$sId[$ii]);                     
                                  //mSendMail($_SESSION['loginid'],0,130,$subject,$postbody,"",0,0);                     
                                }
                              elseif($sMode[$ii]==2)
                                {
                                  $ki = Array('curtbl'=>15,'curidx'=>$sId[$ii],'f_dttoclnt'=>$row['f_p2dt']);
                                  editRowTbl($ki);
                                  
                                  $subject = "В Спец/Заявке № ".$sName[$ii]." добавлен маршрут";
                                  $postbody = "В Спец/Заявке № <a href=\"".$redirect_uri."?pgid=15&obid=".$sId[$ii]."\">".
                                              $sName[$ii]."</a> добавлен маршрут, дата получения товара обновлена";
                                  mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$sId[$ii]);                     
                                  //mSendMail($_SESSION['loginid'],0,130,$subject,$postbody,"",0,0);                     
                     
                                }
                              $ii++;
                            }
                        }
                    }
//~sdid 1140
                  }
                //если добавили новый КТК в 0 маршрут, то прописываем для него историю КТК 
                /*$sql = "select r.f_ktkid,k.f_mlid,r.f_postid,(select ifnull(count(*),0) from ".$tblname." where f_ktkid=r.f_ktkid and f_postid=r.f_postid) kcnt ".
                       "from ".$tblname." r,".DBPref."ktk k ".
                       "where k.f_id=r.f_ktkid and r.f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row['f_ktkid']>0)&&($row['f_mlid']>0)&&($row['f_postid']>0)&&($row['kcnt']<=1))//если добавили не расчетный контейнер первый раз в текущей доставке
                    {
                    $sql = "insert into ".DBPref."ktk_hist (f_specid,f_ktkid,f_dttmcr,f_userid,f_place,f_status) values (".$row['f_postid'].",".$row['f_ktkid'].",NOW(),".$_SESSION['loginid'].",'',0)";
                    $dbh->exec($sql);
                    }
                  }*/
//sdid 962
//  sdid 1429     $sql = "select r.f_status,r.f_parentid,r.f_postid,r.f_p1dtp,r.f_p1dt,r.f_p1dt,r.f_p2dt,r.f_p2dtp,r.f_p2iscustom,
                  $sql = "select r.f_status,r.f_parentid,r.f_postid,r.f_p1dt,r.f_p1dt,r.f_p2dt,r.f_p2iscustom,
                        r.f_p1pointtype,r.f_p2pointtype,r.f_p1location,r.f_p2location,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 
                            (select f_namedop from veda_spr where f_type=39 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED))
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=39 and f_num=(select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)))
                          else 0
                        end country1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED))
                          else 0
                        end city1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then 
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          else 0 
                        end point1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then 
                            case 
                              when CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)>=10000 then 2
                              else 1
                            end
                          else 0 
                        end p1pointtyped,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 
                            (select f_namedop from veda_spr where f_type=39 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED))
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=39 and f_num=(select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)))
                          else 0
                        end country2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED))
                          else 0
                        end city2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then 
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          else 0 
                        end point2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then 
                            case 
                              when CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)>=10000 then 2
                              else 1
                            end
                          else 0 
                        end p2pointtyped,
                        ifnull((select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id),0) cntpot,
                        ifnull((SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ') FROM ".DBPref."routes_ktk rk,".DBPref."ktk k 
                         where k.f_id=rk.f_ktkid and rk.f_routeid=r.f_id),'') ktk 
                      from ".$tblname." r where r.f_id=".$kk;

              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                // При изменении Локации отправления/Локации прибытия, обновляем Тип пункта, Страну, Город, Порт/Станцию
                //echo $op1location."|".$op2location;
                  $p1ptype = $row1['f_p1pointtype'];
                  if($row1['p1pointtyped']>0){$p1ptype = $row1['p1pointtyped'];}
                  $p2ptype = $row1['f_p2pointtype'];
                  if($row1['p2pointtyped']>0){$p2ptype = $row1['p2pointtyped'];}
                  $ki = Array('curtbl'        =>77,
                              'curidx'        =>$kk,
                              'f_p1country'   =>$row1['country1num'],
                              'f_p1city'      =>$row1['city1num'],
                              'f_p1point'     =>$row1['point1num'],
                              'f_p1pointtype' =>$p1ptype,
                              'f_p2country'   =>$row1['country2num'],
                              'f_p2city'      =>$row1['city2num'],
                              'f_p2point'     =>$row1['point2num'],
                              'f_p2pointtype' =>$p2ptype
                             );
                  editRowTbl($ki);
                }
//~sdid 962

                }
              elseif(strcmp($tblname,DBPref."routes_ktk")==0)//После добавления - записи в связку маршрута и КТК
                {
                $sql = "select rk.f_ktkid,k.f_mlid,r.f_postid,(select ifnull(count(*),0) from ".$tblname." where f_ktkid=rk.f_ktkid and ".
                       "f_routeid in (select f_id from ".DBPref."routes where f_postid=r.f_postid)) kcnt ".
                       "from ".$tblname." rk,".DBPref."ktk k,".DBPref."routes r ".
                       "where r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid and rk.f_id=".$kk;
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row['f_ktkid']>0)&&($row['f_mlid']>0)&&($row['f_postid']>0)&&($row['kcnt']<=1))//если добавили не расчетный контейнер первый раз в текущей доставке
                    {
                    $sql = "insert into ".DBPref."ktk_hist (f_specid,f_ktkid,f_dttmcr,f_userid,f_place,f_status) values (".$row['f_postid'].",".$row['f_ktkid'].",NOW(),".$_SESSION['loginid'].",'',0)";
                    //echo $sql."<br>";
                    $dbh->exec($sql);
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."ensures")==0)//После добавления - записи страховки
                {
                $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
                $dbh->exec('SET CHARACTER SET utf8');
                //$sql = "select f_objtype,f_objid from ".$tblname." where f_objtype in (1,2) and f_id=".$kk; //sdid 3550
                //sdid 3550
                $sql = "select f_objtype,f_objid,f_ensdt,ifnull(f_sumens,0) sumens, f_sumensval, 
                        case ifnull(f_ensnum,'') when '_' then '' else ifnull(f_ensnum,'') end fensnum 
                        from ".$tblname." where f_objtype in (1,2) and f_id=".$kk; 
                //~sdid 3550
                //echo $sql."<br>";
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_objid']>0)
                    {
                    //$sql1 = "select c.f_ensdogid,c.f_enstovar,c.f_enscustom,c.f_enslogist,c.f_enslostprof ". //sdid 3550
                    $sql1 = "select c.f_ensdogid,c.f_enstovar,c.f_enscustom,c.f_enslogist,c.f_enslostprof, c.f_id contrid, d.f_id dogid, d.f_orgid orgid ". //sdid 3550
                            "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients c ".
                            "where c.f_id=d.f_contrid and d.f_id=s.f_dogid and s.f_id=".$row['f_objid'];
                    //echo "|".$kk."|".$sql1."<br>";
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if(($row1['f_ensdogid']>0)||($row1['f_enstovar']>0)||($row1['f_enscustom']>0)||
                         ($row1['f_enslogist']>0)||($row1['f_enslostprof']>0))
                        {
                        $ki = Array('curtbl'=>54,'curidx'=>$kk,'f_dogid'=>$row1['f_ensdogid'],'f_tovar'=>$row1['f_enstovar'],
                                    'f_custom'=>$row1['f_enscustom'],'f_logist'=>$row1['f_enslogist'],'f_lostprof'=>$row1['f_enslostprof']);
                        editRowTbl($ki);
                        }
                      //sdid 3550
                      // Если задана дата полиса
                      $buhdoctype = 0;
                      $ensdt = $row['f_ensdt'];
                      if((strcmp($ensdt,"0000-00-00")!=0) && strlen($ensdt)>0)
                        {
                        // Если контрагент Ингосстрах и указан номер договора
                        if(($row1['contrid']==2554) && $row1['dogid']>0)
                          {
                          // Если у договора присутствует категория "Тип бух. документа"
                          $sql = "select f_valstr from veda_categs where f_ctgtype=36 and f_objecttype=3 and f_objectid=".$row1['dogid'];
                          $res3 = $dbh->query($sql);
                          if($row3 = $res3->fetch(PDO::FETCH_ASSOC)) {$buhdoctype = $row3['f_valstr'];}
                          // создаем новое поручение на оплату (операцию) по данным страховки с созданием закрывающего документа
                          $retval = makeOper($row['f_objtype'], $row['f_objid'], "", "", 0, $row['sumens'], $row['f_sumensval'], 0, 1, "",
                            "", "", 0, "", 0, 85, $row1['contrid'], $row1['orgid'], $row1['dogid'], 0, "",
                            0, 1, 7,/*$row['f_contraccid']*/ 0,/*$row['f_orgaccid']*/ -5, $row['fensnum'], $ensdt,/*$row['f_bdrarticle']*/ 181, "", 0,
                            "", "", 0, 0, 0, 0, 0, 0, 0, "0000-00-00", "0000-00-00", 
                            //"{\"oprobjtype\":\"54\",\"oprobjid\":\"" . $curidx . "\",\"schetsum\":\"" . $row['sumens'] . "\"}"
                            "{\"oprobjtype\":\"54\",\"oprobjid\":\"".$kk."\",\"schetsum\":\"".$row['sumens']."\",\"aktdt\":\"".$ensdt."\",\"tdocb\":\"".$buhdoctype."\"}"
                          );
                          }
                        }
                      //~sdid 3550
                      }
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."lns")==0)//После добавления - финансирование
                {
                $sql = "select f_status from ".$tblname." where f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  $nstatus = $row['f_status'];
                  $sql     = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=96 and f_num=".$nstatus;
                  $res1    = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      mSendMail($_SESSION['loginid'],$mgrp,$musr,"Добавлена запись финансирования со статусом ".$row1['f_name'],
                        "В <a href=\"".$redirect_uri."?pgid=18&obid=".$kk."\">финансирование</a> добавлена запись со статусом \"".$row1['f_name']."\"","",18,$kk);
                      }
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."dt")==0)//После добавления - ДТ
                {
                $sql = "select f_uvedins from ".$tblname." where f_id=".$kk;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_uvedins']==1)//установили признак уведомления страхования
                    {
                    //sdid 1546
                    $subject = "В информации о ДТ установили признак уведомления отдела страхования ";
                    $postbody = "В <a href=\"".$redirect_uri."?pgid=61&obid=".$kk."\">информации о ДТ</a> установили признак уведомления отдела страхования";
                    $sbsh = 0;
                    if($menomes==0) 
                      { 
                      $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $kk, 151, 1, 11); 
                      if($sbsh == 0) 
                        { 
                        mSendMail($_SESSION['loginid'],8,0,$subject,$postbody,"",61,$kk);
                        }
                      }                     
                    //mSendMail($_SESSION['loginid'],8,0,"В информации о ДТ установили признак уведомления отдела страхования ",
                    //      "В <a href=\"".$redirect_uri."?pgid=61&obid=".$kk."\">информации о ДТ</a> установили признак уведомления отдела страхования","",61,$kk);
                    //~sdid 1546
                    }
                  }
                // sdid 2168
                $sql = "select 
                            dt.f_upels,
                            dt.f_id,
                            dt.f_specid,
                            dt.f_sumst,
                            dt.f_sumip,
                            dt.f_sumsp,
                            dt.f_sumnds,
                            dt.f_sumndsval,
                            dt.f_sumipval,
                            dt.f_sumstval,
                            dt.f_sumspval, 
                            d.f_contrid,
                            d.f_orgid
                        from $tblname dt,".DBPref."specs s,".DBPref."dogs d 
                        where d.f_id=s.f_dogid and s.f_id=dt.f_specid and dt.f_id=".$kk;
                $conn = $dbh->query($sql);
                if($row = $conn->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_upels']==1 && $row['f_specid'] > 0)
                    {
                    if($row['f_sumnds']>0)
                      {
                      makeOper(2,$row['f_specid'],"","",0,$row['f_sumnds'],$row['f_sumndsval'],0,-5,"",
                          "","",0,"",0,80,$row['f_contrid'],$row['f_orgid'],0,0,"",
                          0,0,0,0,-5,"","",-5,"",0,
                          "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      }
                    if($row['f_sumip']>0)
                      {
                      makeOper(2,$row['f_specid'],"","",0,$row['f_sumip'],$row['f_sumipval'],0,-5,"",
                          "","",0,"",0,78,$row['f_contrid'],$row['f_orgid'],0,0,"",
                          0,0,0,0,-5,"","",-5,"",0,
                          "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      }
                    if($row['f_sumst']>0)
                      {
                      makeOper(2,$row['f_specid'],"","",0,$row['f_sumst'],$row['f_sumstval'],0,-5,"",
                          "","",0,"",0,76,$row['f_contrid'],$row['f_orgid'],0,0,"",
                          0,0,0,0,-5,"","",-5,"",0,
                          "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      }
                    if($row['f_sumsp']>0)
                      {
                      makeOper(2,$row['f_specid'],"","",0,$row['f_sumsp'],$row['f_sumspval'],0,-5,"",
                          "","",0,"",0,99,$row['f_contrid'],$row['f_orgid'],0,0,"",
                          0,0,0,0,-5,"","",-5,"",0,
                          "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      }
                    }
                  }
                // ~ sdid 2168
                }
              elseif(strcmp($tblname,DBPref."schets")==0)//После добавления - счет
                {
                #sdid1415
                $sql = "select s.f_doptype,s.f_operid,s.f_adddetail,s.f_sum,s.f_nds,s.f_ndssum,s.f_val,s.f_grnd 
                        from ".$tblname." s where s.f_id=".$kk;
                #~sdid1415
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  #sdid1415
                  if($row['f_adddetail']==1)
                    {
                    $ki = Array('curtbl'=>179,'f_schetid'=>$kk,'f_num'=>1,'f_count'=>1,'f_price'=>$row['f_sum'],'f_sum'=>$row['f_sum'],'f_nds'=>$row['f_nds'],
                                'f_ndssum'=>$row['f_ndssum'],'f_val'=>$row['f_val'],'f_grnd'=>$row['f_grnd']);
                    $ar = json_decode(addRowTbl($ki), true);
                    }
                  #~sdid1415
                  //echo $row['f_doptype']."|".$oid."|";
                  if($row['f_doptype']==1)//если вставляли invoice
                    {
                    //echo $row['f_operid']."|";
                    if($row['f_operid']>0)
                      {
                      $sql = "select i.f_specid from ".DBPref."spec_invoices i where i.f_id=".$row['f_operid'];
                      $res = $dbh->query($sql);
                      if($row = $res->fetch(PDO::FETCH_ASSOC))
                        {
                        $ki = Array('curtbl'=>187,'f_objid'=>$kk,'f_detailid'=>$row['f_specid']);
                        $ar = json_decode(addRowTbl($ki), true);
                        }
                      }
                    elseif($oid>0)
                      {
                      $ki = Array('curtbl'=>187,'f_objid'=>$kk,'f_detailid'=>$oid);
                      $ar = json_decode(addRowTbl($ki), true);
                      }
                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."akts_details_opers")==0)//После добавления - операции к детализации акта
                {
                //sdid - 882
                if(usenumdocreestr==1)
                  {
                  $naddndr = 0;
                  $ndrid   = 0;
                  $sql = "select a.f_id aid,si.f_id siid,a.f_orgid oid,ado.f_id adoid,a.f_dt adt,si.f_specid spid
                          from ".$tblname." ado,".DBPref."akts_details ad,".DBPref."akts a,".DBPref."spec_invoices si,".DBPref."typeopers t 
                          where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and si.f_id=ado.f_operid and ado.f_operid>0 and t.f_id=si.f_idoper and 
                            si.f_parenttype=2 and si.f_isvozm=1 and ad.f_ndssum>0 and a.f_type=7 and ado.f_id=$kk ";
                  $res = $dbh->query($sql);
                  if($row = $res->fetch(PDO::FETCH_ASSOC))
                    {
                    $sql = "select ifnull(count(*),0) cnt,ifnull(ndr.f_id,0) ndrid from ".DBPref."numdocreestr ndr 
                            where ndr.f_typedoc=8 and ndr.f_orgid=".$row['oid']." and ndr.f_docid=".$row['aid']." and 
                              (select si.f_specid from ".DBPref."numdocreestr_docs ndrd,".DBPref."akts_details_opers ado,".DBPref."spec_invoices si
                               where ndrd.f_numdocreestrid=ndr.f_id and ado.f_id=ndrd.f_docid and si.f_id=ado.f_operid limit 1)=".$row['spid'];
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row1['cnt']>0)
                        {
                        $ndrid = $row1['ndrid'];
                        $sql   = "select ifnull(count(*),0) cnt from ".DBPref."numdocreestr_docs ndrd where ndrd.f_docid=".$row['siid']." ";
                        $res2  = $dbh->query($sql);
                        if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                          {
                          if($row2['cnt']==0)
                            {$naddndr=1;}
                          }
                        }
                      else
                        {$naddndr=1;}
                      }
                    if($naddndr==1)
                      {
                      if($ndrid==0)
                        {
                        $sql = "select ifnull(min(nd.f_numdoc),0) numdoc,nd.f_id
                                from ".DBPref."numdocreestr nd
                                where nd.f_orgid=".$row['oid']." and nd.f_typedoc=8 and nd.f_year=".substr($row['adt'],3,1)." and nd.f_out1C=0 and nd.f_docid=0";
                        $res2    = $dbh->query($sql);
                        if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                          {
                          $ndrid = $row2['f_id'];
                          $ki    = Array('curtbl'=>255,'curidx'=>$ndrid,'f_docid'=>$row['aid'],'f_doctype'=>184);
                          editRowTbl($ki);
                          }
                        }
                      if($ndrid>0)
                        {
                        $ki = Array('curtbl'=>257,'f_numdocreestrid'=>$ndrid,'f_docid'=>$row['adoid']);
                        addRowTbl($ki);
                        }
                      }
                    }
                  }
                //~sdid - 882
                //sdid - 203
                $sql = "select ifnull((select f_type from ".DBPref."akts where f_id=d.f_aktid),0) f_type,s.f_akts_detailsid,t.f_nomenkid,d.f_nomenk,s.f_operid,t.f_c1doctype, i.f_sum oper_sum, i.f_nds oper_nds, 
                               case when s.f_sum=0 then (d.f_sum+d.f_ndssum) else s.f_sum end akt_sum, 
                               d.f_nds akt_nds,i.f_parenttype,i.f_specid sid,i.f_nodoccalc
                        from ".$tblname." s,".DBPref."akts_details d,".DBPref."spec_invoices i,".DBPref."typeopers t 
                        where d.f_id=s.f_akts_detailsid and i.f_id=s.f_operid and s.f_operid>0 and t.f_id=i.f_idoper and 
                              not (i.f_outbuhperiod=1 and i.f_status<>7) and s.f_id=".$kk;
                //~sdid - 203
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row['f_nomenk']==0)&&($row['f_nomenkid']>0))
                    {
                    $ki = Array('curtbl'=>180,'curidx'=>$row['f_akts_detailsid'],'f_nomenk'=>$row['f_nomenkid']);
                    editRowTbl($ki);
                    }
                  //sdid - 203,353
                  if($row['f_type']==7)
                    {
                    $specstatus = 0;
                    if($row['f_parenttype']==2)//если операция по спецификации ищем статус операции
                      {
                      $sql = "select s.f_status from ".DBPref."specs s where s.f_id=".$row['sid'];
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {$specstatus=$row1['f_status'];}
                      }
                    if((($row['oper_sum']==$row['akt_sum']) && ($row['oper_nds']==$row['akt_nds']))||
                       ($specstatus == 8)||
                       ($specstatus == 17)||
                       (($row['f_parenttype']==2)&&($specstatus!=6)&&($specstatus!=8)&&($specstatus!=17)))
                      {
                      if(($row['f_nodoccalc']==1)&&($row['oper_sum']==$row['akt_sum'])&&($row['oper_nds']==$row['akt_nds']))
                        {$ki = Array('curtbl'=>35,'curidx'=>$row['f_operid'], 'f_nodoccalc'=>0);
                        editRowTbl($ki);}
                      if(($specstatus == 8) || ($specstatus == 17))//Пишем письма ответственному бухгалтеру и менеджеру об изменении суммы маржи в РП
                        {
                        if(($row['oper_sum']!=$row['akt_sum'])||($row['oper_nds']!=$row['akt_nds']))
                          {
//sdid 887
                          $subject = "Cумма маржи в РП была изменена";
                          $postbody = "Cумма маржи в РП была изменена. К <a href=\"".$redirect_uri."?pgid=145&obid=".$row['f_operid']."\">операции</a> добавлен закрывающий документ";
                          $sbsh = 0;
                          $sbsh1 = 0;
                          if($menomes==0) 
                            { 
                            $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 35, $row['f_operid'], 151, 6, 7); 
                            $sbsh1 = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 35, $row['f_operid'], 151, 6, 8); 
                            }                     
                          if($sbsh == 0) { mSendMail(2,13,0,$subject,$postbody,"",35,$row['f_operid']); }
                          if($sbsh1 == 0) { mSendMail(2,14,0,$subject,$postbody,"",35,$row['f_operid']); }
// ----------------------- orig
//                        $subject   = "Cумма маржи в РП была изменена";
//                        $postbody  = "К <a href=\"".$redirect_uri."?pgid=145&obid=".$row['f_operid']."\">операции</a> добавлен закрывающий документ";
//                        mSendMail(2,13,0,$subject,$postbody,"",35,$row['f_operid']);
//                        mSendMail(2,14,0,$subject,$postbody,"",35,$row['f_operid']);
// ----------------------- ~orig
//~sdid 887
                          }
                        }
                      }
                    elseif(($row['f_parenttype']==2)&&($specstatus==6))
                      {handle_adjustments($kk, $tblname, $dbh);}
                    //~sdid - 203,353

                    }
                  }
                }
              elseif(strcmp($tblname,DBPref."spec_invoices")==0)//!!! это событие не отрабатывает, т.к. всатвляем не через addrow если добавили операцию
                {
          
                }
              //sdid 636
              elseif(strcmp($tblname,DBPref."dogs")==0)//После добавления - договора
                {
                $dbh1 = dbconnect();
                $sql1 = "select d.f_id dogid, d.f_dogname dogname, d.f_dogdate dogdate, co.f_buhid, co.f_id contactid, co.f_name contactname   
                         from veda_dogs d, veda_clients c, veda_contacts co  
                         where c.f_id=d.f_contrid 
                           and (select count(*) from veda_categs where f_ctgtype=1 and f_objecttype=2 and f_objectid=d.f_contrid and f_valstr='2')>0
                           and d.f_dogtype in (1,11) 
                           and co.f_id=c.f_contactid
                           and co.f_buhid=0
                           and d.f_id=".$kk;
                $res1 = $dbh1->query($sql1);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  mSendMail($_SESSION['loginid'],41,0,"Для клиента \"".$row1['contactname']."\" (не имеет назначенного ответственного бухгалтера) добавлен новый договор № ".$row1['dogname']." от ".format_dt($row1['dogdate'],0,0),
                                                      "Для клиента <a href=\"".$redirect_uri."?pgid=46&obid=".$row1['contactid']."\">\"".$row1['contactname']."\"</a> (не имеет назначенного ответственного бухгалтера) создана запись о <a href=\"".$redirect_uri."?pgid=14&obid=".$row1['dogid']."\">Договоре № ".$row1['dogname']." от ".format_dt($row1['dogdate'],0,0)."</a>","",14,$row1['dogid']);
                  }
                }
              //sdid2232
              elseif(strcmp($tblname,DBPref."maspayfe_opers")==0)//После добавления - обобщенные платежи - операции
                {
                // sdid 2549
                //$sql = "SELECT sum(si.f_sum) mpesum,mp.f_sum mpsum,mp.f_id
                //    FROM ".DBPref."maspayfe_opers mpe, ".DBPref."maspayfe mp, ".DBPref."spec_invoices si
                //    WHERE si.f_id=mpe.f_operid AND mp.f_id=mpe.f_maspayfeid AND mp.f_id=(select f_maspayfeid from veda_maspayfe_opers where f_id=$kk)";
                $sql = "SELECT 
                               CASE 
                                   WHEN mp.f_type = 4 THEN 
                                        SUM(mpe.f_sum)
                                   ELSE
                                        sum(si.f_sum) 
                               END mpesum,
                               mp.f_sum mpsum,
                               mp.f_id
                        FROM ".DBPref."maspayfe_opers mpe, ".DBPref."maspayfe mp, ".DBPref."spec_invoices si 
                        WHERE si.f_id=mpe.f_operid AND mp.f_id=mpe.f_maspayfeid AND mp.f_id=(select f_maspayfeid from veda_maspayfe_opers where f_id=$kk)";
//                $sql = "SELECT sum(mpe.f_sum) mpesum,mp.f_sum mpsum,mp.f_id
//                    FROM ".DBPref."maspayfe_opers mpe, ".DBPref."maspayfe mp
//                    WHERE mp.f_id=mpe.f_maspayfeid AND mp.f_id=(select f_maspayfeid from veda_maspayfe_opers where f_id=$kk)";
                // ~ sdid 2549
                //echo "$sql<br>":
                $conn = $dbh->query($sql);
                if($row = $conn->fetch(PDO::FETCH_ASSOC))
                  {
                  //echo $row['mpesum']."|".$row['mpsum']."<br>";
                  if($row['mpesum'] != $row['mpsum'])
                    {
                    $new_sum = $row['mpesum'];
                    $update_maspayfe = ['curtbl' => 230, 'curidx' => $row['f_id'], 'f_sum' => $new_sum];
                    $result = editRowTbl($update_maspayfe);
                    }
                  }
                }
              //~sdid2232
              //~sdid 636
              //elseif(strcmp($tblname,DBPref."cash")==0)//если добавили кассовый расход
              //  {
              //  $sql = "select s.* from ".$tblname." s ".
              //         "where s.f_id=".$kk;
              //  $res = $dbh->query($sql);
              //  if($row = $res->fetch(PDO::FETCH_ASSOC))
              //    {
              //    $ki = Array('curtbl'=>,'f_typeobj'=>1,'f_cashid'=>$kk,'f_insum'=>4,'f_idsubj'=>$row2['f_id']);
              //    $ar = json_decode(addRowTbl($ki), true);
              //    }
              //  }
              //echo "[true,\"ok\"]";
              if($wdopmes==1)
                {$retval = "[true,\"ok\",".$kk.",".$wdopmes.",\"".$ansn."\"]";}
              //sdid1776
              elseif($wdopmes==2)
                {$retval = "[true,\"ok\",".$kk.",".$wdopmes.",\"".$ansn."\"]";}
              //~sdid1776
              else
                {$retval = "[true,\"ok\",".$kk."]";}
              }
            else
              //{echo "[false,\"Ни одна запись не добавлена\n".$sql."\"]";}
              {$retval = "[false,\"Ни одна запись не добавлена\n".$sql."\"]";}
            }
          else
            //{echo "[false,\"Не опредлены данные для добавления\"]";}
            {$retval = "[false,\"".getErrCl(7)."\"]";}
          }
        else
          {
          if($candel==-1)
            {
            $retval = "[false,\"".$errmsg."\"]";
            }
          else
            {
            $a = array(false,$errmsg);
            $retval  = json_encode($a);
            }
          //$retval = "[\"false\",\"".$errmsg."\"]";
          }
        }
      else
        //{echo "[false,\"Не найдена таблица для добавления данных\"]";}
        {$retval = "[false,\"".getErrCl(8)."\"]";}
      }
    else
      //{echo "[false,\"Не определена таблица для добавления данных\"]";}
      {$retval = "[false,\"".getErrCl(9)."\"]";}
    return $retval;
    }

  //обновить запись в таблице
  //sdid - 203
  function editRowTbl($ik, $extra_data_hash=array())
    {
    $wdopmes = 0;
    $ansn    = "";
    $kr     = 0;//можем редактировать
    $errmsg = "";
    if(!isset($redirect_uri))
      {$redirect_uri = redirect_uri;}
    $ans    = "";
    $retval = "";
    $curtbl = 0;
    if(isset($ik['curtbl']))
      {$curtbl = $ik['curtbl'];}
    $curidx = 0;
    if(isset($ik['curidx']))
      {$curidx = $ik['curidx'];}
    // sdid 2504
    $no_further_update = [$curidx];
    if(isset($ik['no_further_update']))
      {
      $no_further_update = $ik['no_further_update'];
      unset($ik['no_further_update']);
      }
    // ~ sdid 2504
//sdid 1257
//sdid 1388
//    $oinsdogid = 0; // ID договра о страховании грузов у текущей закписи ДО обновления
//    if(isset($ik['f_dogid'])) {if(is_numeric($ik['f_dogid'])) {$oinsdogid = $ik['f_dogid'];}}
//~sdid 1388
//~sdid 1257
//sdid 1214
      //$nroutecom = ""; // Новый текст комментария в маршруте
      //if(isset($ik['f_com'])) {$nroutecom = $ik['f_com'];}
      //echo "|".$nroutecom."|";
//~sdid 1214

    //$ans=$ans."|curtbl=".$curtbl."|curidx=".$curidx;
    //echo "|curtbl=".$curtbl."|curidx=".$curidx."<br>";
    if(($curtbl>0) and ($curidx>0))
      {
      //подключаемся к базе
      $dbh = dbconnect();
      $sql = "select f_tablename from ".DBPref."menu_all where f_id=".$curtbl;
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        //echo $sql."<br>";
        $tblname   = $row['f_tablename'];
        $strvalues = "";
        //while(list($key, $value) = each($ik)) 
        if($curtbl==82)//реестр инвойсов
          {
          $ik['f_type']       = 15;
          $ik['f_doptype']    = 1;
          }
        elseif($curtbl==15)//Спецификации/Заявки
          {
          //sdid - 847
          if(isset($ik['f_dttoclnt']))
            {
            if(strlen($ik['f_dttoclnt'])>0)
              {
              // sdid 1429
//            $sql  = "select f_dtaddpergruz from $tblname where f_id=$curidx and f_dtaddpergruz='0000-00-00'";
//            $res1 = $dbh->query($sql);
//            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
//              //{$ik['f_dtaddpergruz']=$ik['f_dttoclnt'];}}}
//              {$ik['f_dtaddpergruz']=date("Y.m.d");}}
              $sql_routes = "SELECT COUNT(routes.f_id) r_count, COUNT(CASE routes.f_status WHEN 5 THEN 1 ELSE NULL END) s_count
                             FROM " . DBPref . "specs specs, " . DBPref . "routes routes, " . DBPref . "shipments shipments 
                             WHERE specs.f_postid=shipments.f_id AND routes.f_postid=shipments.f_id AND specs.f_id=" . $curidx;
              $res_routes = $dbh->query($sql_routes);
              if ($row_routes = $res_routes->fetch(PDO::FETCH_ASSOC))
                {
                if ($row_routes['r_count'] == $row_routes['s_count'] && $row_routes['r_count'] > 0)
                  {
                  $ik['f_dtaddpergruz'] = date("Y.m.d", time()); // Дата внесения информации о передаче груза
                  }
                }
              // ~sdid 1429
              }
            }
          //~sdid - 847

          // sdid 1448
          //sdid 1803
          //$sqlComCheck = "select * from ".$tblname." where f_id=:curidx";
          $sql1 = "select f_comisprep,f_id,f_dogid,length(ifnull(f_kod1cb,'')) lenkod1cb,length(ifnull(f_kod1cp,'')) lenkod1cp
                     ,f_parentspecid,f_is_extra_spec #2390
                   from ".$tblname." where f_id=:curidx";
          /*$resComCheck = $dbh->prepare($sqlComCheck);
          $resComCheck->bindParam(':curidx',$curidx,PDO::PARAM_INT);
          $resComCheck->execute();
          if ($rowComCheck = $resComCheck->fetch(PDO::FETCH_ASSOC))
            {
            if($rowComCheck['f_comisprep']==0)
              {
              if(isset($ik['comisprep']) && isset($ik['f_dtprepcom'])){if((strcmp($ik['comisprep'],"true")===0) && strlen($ik['f_dtprepcom'])==0) {$ik['f_dtprepcom']=date('Y.m.d H:i:s', time());}}
              }
            }*/
          $res1 = $dbh->prepare($sql1);
          $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
          $res1->execute();
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            if($row1['f_comisprep']==0)
              {
              if(isset($ik['comisprep']) && isset($ik['f_dtprepcom'])){if((strcmp($ik['comisprep'],"true")===0) && strlen($ik['f_dtprepcom'])==0) {$ik['f_dtprepcom']=date('Y.m.d H:i:s', time());}}
              }
            if(isset($ik['dogid']))
              {
              if($row1['f_dogid'] != $ik['dogid'])
                {
                if($row1['lenkod1cb']>3 || $row1['lenkod1cp']>3)
                  {
                  $kr = -3;
                  $errmsg = "В спецификации/заявке (ИД ".$row1['f_id'].") присутствуют коды 1С договора с покупателем/комитентом.\nИзменение номера договора запрещено.";
                  //$ansmes = $ansmes."В спецификации/заявке (ИД ".$row1['f_id'].") присутствуют коды 1С договора с покупателем/комитентом.\nИзменение номера договора запрещено.";
                  }
                }
              } 
            //sdid2390
            $parentspecid = 0;
            $extra_specid = 0;
            if(isset($ik['parentspecid'])){$parentspecid=$ik['parentspecid'];}
            if(isset($ik['f_is_extra_spec'])){if((boolean)json_decode(strtolower($ik['f_is_extra_spec'])) === true){$extra_specid=1;}}
            if(($parentspecid>0)&&($extra_specid>0)&&(($row1['f_parentspecid']==0)||($row1['f_is_extra_spec']==0)))
              {
              $buh_sql = "select f_buhid from ".DBPref."specs where f_id=:parentspecid";
              $buh_res = $dbh->prepare($buh_sql);
              $buh_res->bindParam(':parentspecid',$parentspecid,PDO::PARAM_INT);
              $buh_res->execute();
              if($buh_row = $buh_res->fetch(PDO::FETCH_ASSOC))
                {
                $ik['buhid'] = $buh_row['f_buhid'];
                }
              }
            //~sdid2390
            }
          //~sdid 1803
          // ~sdid 1448
          //sdid 2637
          $fsubtype = 0;
          $ftypez = 0;
          // Нужно запретить возможность создавать Заявки/Спецификации с типом "Спецификация" и подвидом "Сертификация".
          if(isset($ik['typez'])) {$ftypez = $ik['typez'];}
          elseif(isset($ik['f_typez'])) {$ftypez = $ik['f_typez'];}
          if(isset($ik['subtype'])) {$fsubtype = $ik['subtype'];}
          elseif(isset($ik['f_subtype'])) {$fsubtype = $ik['f_subtype'];}
          if($fsubtype==2&&$ftypez==2)
            {
            $kr = -3; $errmsg = $errmsg."\nДля типа \"Спецификация\" недопустимо использовать подвид \nСертификация\n";
            }
          //~sdid 2637
          }
        elseif($curtbl==17)//Счета
          {
          $cinvtype = 0;
          if(isset($ik['type'])){$cinvtype=$ik['type'];}
          if($cinvtype==0){if(isset($ik['f_type'])){$cinvtype=$ik['f_type'];}}
          if($cinvtype==1)//Счета покупателю
            {
            if($ik['curidx']>0)
              {
              if((strlen($ik['f_kod1c'])==0)&&(strlen($ik['f_num'])>0))
                {
                $sql  = "select f_kod1c 
                         from ".DBPref."schets where f_id=".$ik['curidx'];
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {if(strlen($row1['f_kod1c'])>0){$ik['f_kod1c']=$row1['f_kod1c'];}}
                }
              }
            }
          //sdid1444
          $id = $ik['curidx'];
          $sql_check = "SELECT f_type, f_ismaininv, f_maininv FROM ".DBPref."schets WHERE f_id=".$id;
          $conn_check = $dbh->query($sql_check);
          if($row_check = $conn_check->fetch(PDO::FETCH_ASSOC))
            {
            if(intval($row_check['f_type'])===1 && intval($row_check['f_ismaininv'])===1)
              {
              $sql = "SELECT SUM(f_sum) total FROM ".DBPref."schets WHERE f_ismaininv=0 AND f_maininv=".$id;
              $conn = $dbh->query($sql);
              if($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                $total = $row['total'];
                if(isset($ik['f_sum']))
                  {
                  if(abs(doubleval($ik['f_sum'])-doubleval($total))>0.0001)
                    {
                    $retval = "[false, \"Сумма не соответствует сумме счетов, включенных в агрегирующий счет\"]";
                    return $retval;
                    }
                  }
                }
              }
            }
          //~sdid 1444
          // sdid 1314
          if(isset($ik['f_operid']))
            {
            $f_operid = $ik['f_operid'];
            if(strlen($f_operid) > 0)
              {
              $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $f_operid;
              $conn = $dbh->query($sql);
              if($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                if($row['ctgs'] > 0)
                  {
                  $kr = -3;
                  $errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ)";
                  }
                }
              }
            }
          // ~ sdid 1314
          //sdid 3309
          //error_log("\n\nik = ".var_export($ik,true)."\n\n",0);
          if(isset($ik['status']))
            {
            if($ik['status']==5) //Оплата согласована
              {
              $sql = "
                     select
                       case when s.f_maininv>0 and s.f_ismaininv=0
	                      then 
                                (select round(cast(ifnull(sum(ahd.f_clssum/ahd.f_curs),0) AS DECIMAL(15,2)),2)
                                   from veda_schets ss, veda_acchist_docs ahd
                                  where ss.f_id=s.f_maininv and ahd.f_docid=ss.f_id and ahd.f_doctype=1
                                )
                            when s.f_ismaininv=1
                              then
                                (select round(cast(ifnull(sum(ahd.f_clssum/ahd.f_curs),0) AS DECIMAL(15,2)),2)  #select sum(ahd.f_clssum/ahd.f_curs)
                                   from veda_acchist_docs ahd
                                  where ahd.f_docid=s.f_id and ahd.f_doctype=1
                                )
                            else
                              (select round(cast(ifnull(sum(ahd.f_clssum/ahd.f_curs),0) AS DECIMAL(15,2)),2)  #sum(ahd.f_clssum/ahd.f_curs)
                                 from veda_acchist_docs ahd
                                where ahd.f_docid=s.f_id and ahd.f_doctype=1
                                )
                       end sumppdocs,
	               s.f_status
                     from veda_schets s
                     where s.f_id=$id
                     ";
              $conn = $dbh->query($sql);
              if($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                if($ik['status'] != $row['f_status']) // если статус изменен
                  {
                  if(isset($ik['f_sum']))
                    {
                    if(abs(doubleval($ik['f_sum'])-doubleval($row['sumppdocs']))<0.0001) // считаем что сумма счета и сумма ПП равны
                      {
                      $kr = -3;
                      if(strlen($errmsg)>0)
                        {$errmsg .= "Счет полностью оплачен. Его больше нельзя оплачивать";}
                      else 
                        {$errmsg = "Счет полностью оплачен. Его больше нельзя оплачивать";}

                      }
                    }
                  }
                }
              }
            }
          //~sdid 3309
          }
        elseif($curtbl==61)//ДТ
          {
          if(isset($ik['cominv_sum'])){unset($ik['cominv_sum']);}//sdid2725
          if(isset($ik['status']))
            {
            if($ik['status']==17)//статус ДТ выпущена
              {
              $ansmes = "";
              if(isset($ik['f_TDdt']))
                {
                if(strcmp($ik['f_TDdt'],"")==0)
                  {$ansmes = $ansmes." Должна быть заполнена Дата выпуска ДТ (растаможки).";}
                }
              if(isset($ik['f_deklgetdt']))
                {
                if(strcmp($ik['f_deklgetdt'],"")==0)
                  {$ansmes = $ansmes." Должна быть заполнена Дата принятия ДТ.";}
                }
              if(strlen($ansmes)>0)
                {
                $kr     = -3;
                $errmsg = "При статусе ДТ выпущена: ".$ansmes;
                }
              }
            }
          if((isset($ik['f_TDdt']))&&(isset($ik['f_deklgetdt'])))
            {
            if((strlen($ik['f_TDdt'])>0)&&(strlen($ik['f_deklgetdt'])>0))
              {
              if(isset($ik['status']))
                {
                if(strcmp($ik['status'],"17")!=0)
                  {
                  //sdid2299
                  //$kr     = -3;
                  //$errmsg = "При заполненных Дате выпуска ДТ (растаможки) и Дате принятия ДТ, статус должен быть ДТ выпущена";
                  //~sdid2299
                  }
                }
              }
            }
          }
        //sdid3236
        elseif(($curtbl==121))//Перед добавлением - Группы пользователей
          {if(isset($ik['f_dopprstr'])) //делаем автонумерацию для доверенностей
            {if(strlen($ik['f_dopprstr'])>0)
              {if(isset($ik['f_namedop']))
                {if(!filter_var($ik['f_namedop'],FILTER_VALIDATE_EMAIL))
                  {$kr    = -3;
                  $errmsg = "Использовать в Дефекты РП можно только с указанной корректной почтой!!!";}
                }
              else
                {$kr    = -3;
                $errmsg = "Использовать в Дефекты РП можно только с указанной почтой!!!";}
              }
            }
          }
        //~sdid3236
        //sdid 2987
        elseif($curtbl==333)// Сборные поставки
          {
          if(isset($ik['f_name']))
            {
            if(strlen($ik['f_name'])==0)
              {
              $kr     = -3;
              $errmsg = "Поле \"Наименование\" не должно быть пустым и должно содержать уникальное значение.";
              }
            }
          }
        //~sdid 2987
        //sdid1583
        elseif(strcmp($tblname,DBPref."acchist_docs")==0)//Перед изменением в ПП-документы
          {
          $sql = "select * from $tblname ahd where ahd.f_id=$curidx";
          $res2 = $dbh->query($sql);
          if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
            {
            //sdid2462 - запрешаем редактирование связки с документом, если выписка в закрытом периоде
            $lcando = 1;
            //sdid2685
            $canaddupddelacchistl = 0;
            if(isset($ik['canaddupddelacchistl'])){$canaddupddelacchistl=$ik['canaddupddelacchistl'];unset($ik['canaddupddelacchistl']);}
            if($canaddupddelacchistl==0)
              {
              $dtbuhclserp = "";
              $sql_dtbuhcls = "SELECT MAX(f_dtbuhcls) dtbuhcls FROM ".DBPref."settings where f_settype=1";
              $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
              if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                {$dtbuhclserp = strtotime($row_dtbuhcls["dtbuhcls"]);}
              if(strlen($dtbuhclserp)>0)
                {
                $sql_dtbuhcls = "SELECT f_dt1C dt1c FROM ".DBPref."acchist where f_id=".$row2['f_acchistid'];
                $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
                if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                  {
                  $ldt1c = strtotime($row_dtbuhcls["dt1c"]);
                  if($ldt1c < $dtbuhclserp)
                    {
                    $lcando = 0;$kr = -3;$errmsg = $errmsg."Выписка в закрытом бух.периоде, изменение связанных документов запрещено<br>";
                    }
                  }
                }
              }
            //~sdid2685
            if($lcando>0)
              {
            //~sdid2462 - запрешаем редактирование связки с документом, если выписка в закрытом периоде
              if($row2['f_doctype']==3)
                {
                $oclssum = $row2['f_clssum'];
                $sql = "select si.f_id
                        from ".DBPref."spec_invoices i,".DBPref."spec_invoices si
                        where i.f_id=".$row2['f_docid']." and i.f_idoper=387 and si.f_parentid=i.f_id";
                //echo "$sql|";
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {$candel=0;$errmsg = "По операции создана связанная операция, изменение запрещено<br>";}
                $sql = "select i.f_id
                          ,ifnull((select count(*) from ".DBPref."agentreps_opers where f_operid=i.f_id),0) acnt
                          ,ifnull((select count(*) from ".DBPref."corrects_opers where f_correctoperid=i.f_id),0) ccnt
                        from ".DBPref."spec_invoices i
                        where i.f_id=".$row2['f_docid']." and i.f_idoper=387";
                //echo "$sql|";
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row1['acnt']>0){$kr = -3;$errmsg = $errmsg."Операция включена в отчет агента, изменение запрещено<br>";}
                  }
                $sql = "select ds.f_id,d.f_id did,dd.f_id ddid, length(ifnull(d.f_kod1c,'')) len 
                        from ".DBPref."difrate d,".DBPref."spec_invoices ds,".DBPref."difrate_docs dd
                        where ds.f_parentid=".$row2['f_docid']." and ds.f_id=dd.f_operid and d.f_id=dd.f_difrateid and ds.f_idoper=388";
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row1['len']>0){$kr = -3;$errmsg = $errmsg."По операции создана курсовая разница, выгруженная в 1С, изменение запрещено<br>";}
                  }
                }  
              }
            }
          }
        //~sdid1583
        //sdid 694
        //elseif(($curtbl==13)||($curtbl==27)||($curtbl==55)||($curtbl==60))
        elseif(strcmp($tblname,DBPref."clients")==0) //При изменении в Клиенты/Контрагенты проверяем ИНН и КПП
          {
          if(isset($ik['f_inn'])) // Если у изменяемого Клиента ЮЛ в форму внесен ИНН и номер больше либо равно 10, проверяем наличие этого ИНН в базе клиентов/контрагентов
            {
              if(strlen($ik['f_inn'])>=10)
                { // если ещё КПП ввели, ищем по ИНН и КПП
                  $kppstr = "";
                  if(isset($ik['f_kpp'])) //Если заполнен и КПП в форме ввода                   
                    { if(strlen($ik['f_kpp'])>=9) //Если заполнен и КПП в форме ввода и кол-во символов не меньше 9                  
                        {$kppstr = " and c.f_kpp=".$ik['f_kpp'];}  
                    }
                $sql  = "select c.f_cname cname, c.f_inn innnum, ifnull(c.f_kpp,'') kppnum from ".DBPref."clients c where LENGTH(c.f_inn)>=10 and c.f_inn=".$ik['f_inn'].$kppstr." and c.f_id<>$curidx";
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                    $ansmes = "";   
                    if(isset($ik['f_kpp'])) //Если заполнен и КПП в форме ввода                   
                      {if(strlen($ik['f_kpp'])>0) //Если заполнен и КПП в форме ввода                   
                        {
                          if((strcmp($row1['kppnum'], $ik['f_kpp'])==0) && (strcmp($row1['innnum'], $ik['f_inn'])==0)) // Если и ИНН и КПП совпадают, не даём сохранить Клиента/Контрагента
                            {
                              $kr     = -3;
                              $ansmes = "В системе уже присутствует клиент '".$row1['cname']."' с такими ИНН ".$row1['innnum']." и КПП ".$row1['kppnum'];                              
                            }
                        }
                      else //Если заполнен только ИНН
                        {
                          if(strcmp($row1['innnum'], $ik['f_inn']) == 0) // Если ИНН совпадают, просим уточнить КПП
                            {
                              $kr     = -3;
                              $ansmes = $ansmes."В системе присутствует клиент '".$row1['cname']."' с таким ИНН, уточните КПП. ИНН ".$row1['innnum'];
                            }
                        }
                      }
                    else //Если заполнен только ИНН, и нет isset f_kpp
                      {
                          if(strcmp($row1['innnum'], $ik['f_inn']) == 0) // Если ИНН совпадают, просим уточнить КПП
                            {
                              $kr     = -3;  
                              $ansmes = $ansmes."В системе присутствует клиент '".$row1['cname']."' с таким ИНН, уточните КПП. ИНН ".$row1['innnum'];
                            }
                      }
                    if(strlen($ansmes)>0)
                      {
                        $kr     = -3;
                        $errmsg = $ansmes;
                      }
                  }
              }
            }
          //sdid 1727
          // 2. При вводе нового контрагента с признаком "Наша организация" для видов операций с подвидом 0 реализовать добавление категории НДС по умолчанию 
          //    для новой организации в соответствии с выбранной СНО
          //if($curtbl==26)
          //  { // найдем типы операций с subtype=0 и id=контора, к которым не привязана категория НДС
          //  if($ik['f_isourorg']==true) //если "Наша организация" 
          //    {
          //    $sql = "select t.f_id tfid,ifnull(ca.f_id,0) cfid
          //              from 
          //                veda_spr sp86,
          //                veda_spr sp85,
          //                veda_typeopers t
          //              left join veda_categs ca on 
          //                ca.f_ctgtype=7                # Вид категории - НДС 
          //               and ca.f_valint=".$curidx ."
          //               and ca.f_objecttype=4          # veda_spr.f_type=68 f_num=4 (тип объекта - Вид операции)
          //               and ca.f_objectid=t.f_id
          //             where 
          //               t.f_id>0 and t.f_bdrarticle>0 
          //               and sp86.f_type=86 and sp86.f_num=t.f_bdrarticle 
          //               and sp85.f_type=85 and sp85.f_num=sp86.f_uslint
          //               and sp85.f_dopprint=1";
          //    $stavka = 0;
          //    if($ik['f_sno']==2) {$stavka=0;}
          //    else {$stavka=2;}
          //    $res1 = $dbh->query($sql);
          //    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          //      { // к найденым операциям прикручиваем категорию НДС
          //      if($row1['cfid']==0) 
          //        {
          //        $ki = Array('curtbl'=>211,
          //                    //'curidx'=>$row['f_id'],
          //                    'f_ctgtype'   =>7,
          //                    'f_objectid'  =>$row1['tfid'],
          //                    'f_valstr'    =>$stavka,
          //                    'f_valint'    =>$curidx,
          //                    'f_objecttype'=>4,
          //                    'f_objdtype'  =>0 
          //                   );
          //        addRowTbl($ki);
          //        }
          //      else
          //        {
          //        $ki = Array('curtbl'      =>211,
          //                    'curidx'      =>$row1['cfid'],//$row1['tfid'],
          //                    'f_valstr'    =>$stavka,
          //                   );
          //        editRowTbl($ki);
          //        }   
          //      }
          //    }
          //  }
          //~sdid 1727 
          //sdid 2669
          if(isset($ik['f_nalognumber'])) // уникальность "Налоговый номер ин.контрагента", страна не Russia //sdid 2669
            {
            if(isset($ik['country']))
              {
              if(strlen($ik['country'])!=0)
                {
                if($ik['country']!=1)
                  {
                  if(strlen($ik['f_nalognumber'])>0)
                    {
                    $sql = "select f_id from veda_clients where f_nalognumber like '".$ik['f_nalognumber']."' and f_id<>$curidx";
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC)) 
                      {$kr=-3; 
                       $errmsg = "\nЭтот уникальный Налоговый номер иностранного контрагента уже связан с другим контрагентом в системе. ".$ik['f_nalognumber'];
                      }
                    }
                  else 
                    {
                    $kr=-3; 
                    $errmsg = "\nНеобходимо ввести Налоговый номер иностранного контрагента";
                    }
                  }
                }
              }
            }
          //~sdid 2669
          }
        //~sdid 694
        //sdid1776
        elseif(strcmp($tblname, DBPref."dogs") === 0)
          {
          //sdid 2373
          //$sql_dog = "select f_typecode from ".DBPref."client_codes where f_typecode in (1,2) and f_contrid=".$ik['orgid'];
          $sql_dog = "select cc.f_typecode from ".DBPref."client_codes cc, ".DBPref."clients c 
                       where cc.f_typecode in (1,2) and cc.f_contrid=".$ik['orgid']." and c.f_noexp1c=0 and c.f_id=".$ik['contrid'];
          //~sdid 2373
          $conn_dog = $dbh->query($sql_dog);
          if($row_dog = $conn_dog->fetch(PDO::FETCH_ASSOC))
            {
            $contrid = getClntInf(2, 2, $ik['contrid'], 0, $ik['orgid'], 2);
            $contrlen = 0;
            if(is_string($contrid))
              {$contrlen = strlen($contrid);}
            if($contrlen === 0)
              {
              $bd1c = intval($row_dog['f_typecode']);
              $bd1c_name = "";
              if ($bd1c > 0)
                {
                $sql_bdname = "SELECT f_name FROM " . DBPref . "spr WHERE f_type=110 AND f_num='" . $bd1c . "'";
                $conn_bdname = $dbh->query($sql_bdname);
                if ($res_bdname = $conn_bdname->fetch(PDO::FETCH_ASSOC)) 
                  {$bd1c_name = $res_bdname['f_name'];}
                }
              $export_result = json_decode(export_client_to_1c($dbh, $bd1c, $ik['contrid']), true);
              if ($export_result[0] === true)
                {$ans = $ans . $export_result[1];} 
              else
                {
                $retval = "[false,\"Ошибка экспортирования в базу 1С - " . $bd1c_name . "<br>" . $export_result[1] . "Сохранение договора запрещено<br>\"]";
                return $retval;
                }
              }
            }
          }
        //~sdid1776
        elseif($curtbl==146)//Пользователи-объекты
          {
          if(isset($ik['idobj'])&&isset($ik['typerole']))
            {
            if(($ik['idobj']>0)&&($ik['typerole']>0))
              {
              $sql  = "select ifnull(sum(f_part),0) sp,(select f_name from veda_spr where f_type=75 and f_num=p.f_typerole) typerole 
                       from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=".$ik['idobj']." and p.f_typerole=".$ik['typerole']." and 
                         p.f_idsubj<>".$ik['idsubj']." and p.f_id<>".$ik['curidx'];
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if(($row1['sp']+$ik['f_part'])>1)
                  {
                  $kr     = -3;
                  $errmsg = "Сумма долей роли \"".$row1['typerole']."\" превышает 1 !!!";
                  }
                }
              }
            }
          }
        // sdid 1314
        elseif ($curtbl == 147) // ПП-документы
          {
          $doctype = 0;
          if(isset($ik['doctype'])){$doctype=$ik['doctype'];}
          elseif(isset($ik['f_doctype'])){$doctype=$ik['f_doctype'];}
          $docid = 0;
          if(isset($ik['docid'])){$docid=$ik['docid'];}
          elseif(isset($ik['f_docid'])){$docid=$ik['f_docid'];}
          if($doctype == 3 && $docid > 0)
            {
            $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $docid;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if($row['ctgs'] > 0)
                {
                $kr = -3;
                $errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ)";
                }
              }
            }
          }
        // ~ sdid 1314
        elseif($curtbl==246)//ЛКК. Клиенты
          {
          $sql  = "select f_login,f_resreg from $tblname where f_id=$curidx";
          $res1 = $dbh->query($sql);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            if((strcmp($ik['f_login'],$row1['f_login'])!=0)&&($row1['f_resreg']==1))
              {
              $kr     = -3;
              $errmsg = "Нельзя изменять логин зарегистрированного пользователя!!!";
              }
            }
          }
        elseif($curtbl==186)//ЭДО-спецификации
          {
          $ik['f_type']       = 1;
          $ik['f_objtype']    = 181;
          $ik['f_detailtype'] = 15;
          }
        elseif($curtbl==187)//Счет-спецификации
          {
          $ik['f_type']       = 2;
          $ik['f_objtype']    = 17;
          $ik['f_detailtype'] = 15;
          }
        elseif($curtbl==223)//АКТ-спецификации
          {
          $ik['f_type']       = 3;
          $ik['f_objtype']    = 83;
          $ik['f_detailtype'] = 15;
          }
        // sdid 1314
        elseif($curtbl==271)//Перед изменением записи - Категории - Учет расхода
          {
          $ovalstr = 0;
          if(isset($ik['valstr'])){$ovalstr = $ik['valstr'];}
          $sql  = "select f_valstr from $tblname where f_id=$curidx";
          $res1 = $dbh->query($sql);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {$ovalstr = (int)$row1['f_valstr'];}
          if(isset($ik['ctgtype']))
            {
            if($ik['ctgtype'] == 33 || strcmp($ik['ctgtype'], "33") == 0)
              {
              if(isset($ik['valstr']))
                {
                if($ik['valstr'] == 2 || strcmp($ik['valstr'], "2") == 0)  // Расход ОВЭД
                  {
                  $sql_bdr = "SELECT ctg.f_valstr 
                              FROM ".DBPref."typeopers t, ".DBPref."spec_invoices si, ".DBPref."categs ctg 
                              WHERE t.f_type=si.f_type_oper AND t.f_subtype=si.f_sub_type_oper 
                                AND ctg.f_objectid=t.f_id AND ctg.f_ctgtype=32 AND ctg.f_objecttype=4 AND si.f_id=".$ik['objectid'];
                  $res_bdr = $dbh->query($sql_bdr);
                  if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                    {
                    $bdr = $row_bdr['f_valstr'];
                    $add_bdr = ["curtbl" => 270,
                          "ctgtype" => 32,
                          "objectid" => $ik['objectid'],
                          "valstr" => $bdr,
                          "objecttype" => 5];
                    addRowTbl($add_bdr);
                    }
                  }
                else 
                  {
                  $sql_bdr = "SELECT ctg.f_id FROM ".DBPref."categs ctg WHERE f_ctgtype=32 AND f_objecttype=5 AND f_objectid=".$ik['objectid'];
                  $res_bdr = $dbh->query($sql_bdr);
                  if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                    {
                    $bdr_id = $row_bdr['f_id'];
                    $bdr_delete = ["curtbl" => 270,"curidx" => $bdr_id];
                    delRowTbl($bdr_delete);
                    }
                  }
                }
              }
            }
          // sdid 1314 20.09.2023
          $sql =    "SELECT COUNT(s.f_id) paid_invoices 
                     FROM veda_schets s, veda_categs ctg  
                     WHERE s.f_operid = ctg.f_objectid AND ctg.f_ctgtype = 33 AND ctg.f_objecttype = 5 AND ctg.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['paid_invoices'] > 0)
              {$kr = -3;$errmsg = "Запрещена смена учета расхода, если по операции уже выставлен счет";}
            }
          $sql = "SELECT COUNT(s.f_id) paid_invoices 
                  FROM veda_akts s, veda_categs ctg  
                  WHERE s.f_operid = ctg.f_objectid AND ctg.f_ctgtype = 33 AND ctg.f_objecttype = 5 AND ctg.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if ($row['paid_invoices'] > 0)
              {$kr = -3;$errmsg = "Запрещена смена учета расхода, если по операции уже создан акт";}
            }
          $sql = "SELECT COUNT(ad.f_id) paid_acchist 
                  FROM veda_acchist_docs ad, veda_categs ctg  
                  WHERE ad.f_doctype = 3 AND ad.f_docid = ctg.f_objectid AND ctg.f_ctgtype = 33 AND ctg.f_objecttype = 5 AND ctg.f_id = " . $curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['paid_acchist'] > 0)
              {$kr = -3;$errmsg = "Запрещена смена учета расхода, если по операции уже произведена оплата";}
            }
          // ~ sdid 1314 20.09.2023
          }
        // ~ sdid 1314
        elseif(strcmp($tblname,DBPref."spec_invoices")==0)//перед обновлением операции и сбором строки апдейта
          {
          //sdid2100
          if(isset($ik['uved']) && isset($ik['uvedtyp']) && isset($ik['adr']))
            {
            if(strcmp($ik['uved'], "true") === 0)
              {
              if($ik['uvedtyp'] == 0 || $ik['adr'] == 0)
                {
                $kr = -3;
                $errmsg = "Если установлен признак 'Пр.увед', должны быть заполнены 'Тип адресата' и 'Адресат'";
                }
              }
            }
          //~sdid2100
          //sdid 1315
          $sql  = "select * from ".$tblname." where f_id=:curidx";
          $res1 = $dbh->prepare($sql);
          $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
          $res1->execute();
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            //if(($row1['f_checked1']>0)||($row1['f_checked2']>0)||($row1['f_checked3']>0))//если проставлялись признаки проверки
            //  {
            //  $nuncheck = 0;
            //  if(isset($ik['itemcalcrp']))
            //    {
            //    if(strcmp($ik['itemcalcrp'],$row1['f_itemcalcrp'])==0){}
            //    else
            //      {
            //      $ischeck  = 0;
            //      $sql = "select f_id from $tblname where f_itemcalcrp=:itemcalcrp and f_parenttype=:parenttype and f_specid=:specid and 
            //                (f_checked1>:ischeck or f_checked2>:ischeck or f_checked3>:ischeck) and f_id<>:curidx";
            //      $res2 = $dbh->prepare($sql);
            //      $res2->bindParam(':itemcalcrp',$row1['f_itemcalcrp'],PDO::PARAM_INT);
            //      $res2->bindParam(':parenttype',$row1['f_parenttype'],PDO::PARAM_INT);
            //      $res2->bindParam(':specid'    ,$row1['f_specid'],PDO::PARAM_INT);
            //      $res2->bindParam(':ischeck'   ,$ischeck,PDO::PARAM_INT);
            //      $res2->bindParam(':curidx'    ,$curidx,PDO::PARAM_INT);
            //      $res2->execute();
            //      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
            //        {
            //        $lkai = Array('curtbl'=>35,'curidx'=>$row2['f_id'],'f_checked1'=>0,'f_checked2'=>0,'f_checked3'=>0);
            //        editRowTbl($lkai);
            //        }
            //      $nuncheck = 1;
            //      }
            //    }
            //  if($nuncheck==0){if(isset($ik['f_num_oper'])){if($ik['f_num_oper']!=$row1['f_num_oper']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_type_oper'])){if($ik['f_type_oper']!=$row1['f_type_oper']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_sub_type_oper'])){if($ik['f_sub_type_oper']!=$row1['f_sub_type_oper']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['fordoc'])){if($ik['fordoc']!=$row1['f_fordoc']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_sum'])){if($ik['f_sum']!=$row1['f_sum']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['val'])){if($ik['val']!=$row1['f_val']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['nds'])){if($ik['nds']!=$row1['f_nds']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['isvozm'])){if($ik['isvozm']!=$row1['f_isvozm']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['addnds'])){if($ik['addnds']!=$row1['f_addnds']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['wloans'])){if($ik['wloans']!=$row1['f_wloans']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['nodoccalc'])){if($ik['nodoccalc']!=$row1['f_nodoccalc']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['status'])){if($ik['status']!=$row1['f_status']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['winsures'])){if($ik['winsures']!=$row1['f_winsures']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_com'])){if(strcmp($ik['f_com'],$row1['f_com'])!=0){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_invcom'])){if(strcmp($ik['f_invcom'],$row1['f_invcom'])!=0){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['idoper'])){if($ik['idoper']!=$row1['f_idoper']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['bdrarticle'])){if($ik['bdrarticle']!=$row1['f_bdrarticle']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['invid'])){if($ik['invid']!=$row1['f_invid']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['parentid'])){if($ik['parentid']!=$row1['f_parentid']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['invoiceid'])){if($ik['invoiceid']!=$row1['f_invoiceid']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_cursoper'])){if($ik['f_cursoper']!=$row1['f_cursoper']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['contrid'])){if($ik['contrid']!=$row1['f_contrid']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['dogid'])){if($ik['dogid']!=$row1['f_dogid']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['f_repnum'])){if($ik['f_repnum']!=$row1['f_repnum']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['outbuhperiod'])){if($ik['outbuhperiod']!=$row1['f_outbuhperiod']){$nuncheck = 1;}}}
            //  if($nuncheck==0){if(isset($ik['levelrp'])){if($ik['levelrp']!=$row1['f_levelrp']){$nuncheck = 1;}}}
            //  if($nuncheck==1)
            //    {
            //    $ik['f_checked1']="0";
            //    $ik['f_checked2']="0";
            //    $ik['f_checked3']="0";
            //    }
            //  }
            // sdid 2168
            // f_idoper: f_sumnds 80, f_sumip 78, f_sumst 76, f_sumsp 99
            $oidoper = $row1['f_idoper'];
            //sdid2232
            $osum    = $row1['f_sum'];
            $oinvcom = $row1['f_invcom']; // sdid 2504
            $oval    = $row1['f_val'];    // sdid 2504
            $owloans = $row1['f_wloans']; // sdid3213
            //sdid 3010
            $odttmcr     = $row1['f_dttmcr'];
            $oisvozm     = $row1['f_isvozm'];  //$row1['']
            $oorgid      = $row1['f_orgid'];
            $ocontrid    = $row1['f_contrid'];
            $obdrarticle = $row1['f_bdrarticle'];
            $oparenttype = $row1['f_parenttype'];
            $ospecid     = $row1['f_specid'];
            $ohnds       = $row1['f_hnds'];
            $onds        = $row1['f_nds'];
            //error_log("\n\nonds0=$onds|newnds0=".$row1['f_nds']."\n\n",0);
            //error_log("\n\nik = ".var_export($ik,true)."\n\n",0);
            if(isset($ik['orgid']))
              {
              if((strlen($ik['orgid'])==0) || ($ik['orgid']==0)) 
                {$kr = -3;if(strlen($errmsg)>0){$errmsg .= "; ";} $errmsg .= "В карточке операции не указана организация. Сохранение запрещено";}
              }
            if(isset($ik['contrid']))
              {
              if((strlen($ik['contrid'])==0) || ($ik['contrid']==0)) 
                {$kr = -3;if(strlen($errmsg)>0){$errmsg .= "; ";} $errmsg .= "В карточке операции не указан контрагент. Сохранение запрещено";}
              }

            if(isset($ik['idoper']) && isset($ik['bdrarticle']) && isset($ik['isvozm']))
              {
              if($ik['idoper']>0 && ($ik['bdrarticle']==0 || $ik['isvozm']==0))
                {
                $sql = "select f_c1doctype from veda_typeopers where f_id=".$ik['idoper'];
                $res2 = $dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($row2['f_c1doctype']==4)||($row2['f_c1doctype']==18))
                    {
                    $kr = -3;if(strlen($errmsg)>0){$errmsg .= "; ";} $errmsg .= "В карточке операции не указаны бюджет (БДР) либо возмещаемость. Сохранение запрещено";
                    }
                  }
                }
              }
            // 5. Наложить доп.ограничения на карточки. Запретить сохранять:
            // 5.1.В заявках/спецификациях, в которых parent_id >0 (??? f_parentspecid???):
            // 5.1.1.Операции с БДР «Расходы» и видом операции с признаком f_isdt=0 и contr_id наше лицо.
            if(isset($ik['contrid']) && isset($ik['bdrarticle']) && isset($ik['idoper']))
              {
              $lbdrarticle = $ik['bdrarticle'];
              $lcontrid = $ik['contrid'];
              $sql = "
                     select s.f_id
                       from veda_specs s, veda_spec_invoices si, veda_clients c, veda_typeopers t
                      where s.f_id=si.f_specid 
                        and si.f_id=$curidx
                        and c.f_id=$lcontrid
                        and c.f_isourorg=1
                        and t.f_id=si.f_idoper
                        and t.f_isdt=0
                        and s.f_parentspecid>0
                        and (select s84.f_num
                               from veda_spr s84, veda_spr s85, veda_spr s86
                              where s84.f_type=84 and s85.f_type=85 and s86.f_type=86 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and s86.f_num=$lbdrarticle)=2
                     ";
              //$res2 = $dbh->query($sql);
              //if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
              //  {
              //  $kr = -3;if(strlen($errmsg)>0){$errmsg .= "; ";} $errmsg .= "Связанная заявка/спец. является дочерней, операция с признаками: расходного бюджета/не ДТ/'Нашей' организацией в качестве контрагента. Сохранение запрещено.";
              //  }
              }


            //~sdid 3010
            if(isset($ik['f_sum']))//в изменении присутствует сумма
              {
              $f_sum   = $ik['f_sum'];
              if(abs($osum - $f_sum) > PHP_FLOAT_EPSILON)//хотим измененить сумму
                {
                $sql2  = "select count(*) cnt from ".DBPref."maspayfe_opers where f_operid=:curidx";
                $res2 = $dbh->prepare($sql2);
                $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                $res2->execute();
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row2['cnt']>0)//если входит в обощенный платеж
                    {
                    $kr = -3;
                    if(strlen($errmsg) > 0){$errmsg .= "; ";}
                    $errmsg .= "Операция входит в обобщенный платеж, изменение суммы запрещено";
                    }
                  }
                }
              }
            if($kr==0)
              {
              if(($oidoper == 76)||($oidoper == 78)||($oidoper == 80)||($oidoper == 99))
                {
                $odtid   = $row1['f_dtid'];
                $f_sum   = 0;
                if(isset($ik['f_sum'])){$f_sum   = $ik['f_sum'];}
                elseif(isset($ik['sum'])){$f_sum = $ik['sum'];}
                if($odtid>0)
                  {
                  $field = "";
                  if($oidoper == 80){$field = " dt.f_sumnds ";}
                  if($oidoper == 78){$field = " dt.f_sumip ";}
                  if($oidoper == 76){$field = " dt.f_sumst ";}
                  if($oidoper == 99){$field = " dt.f_sumsp ";}
                  if(strlen($field) > 0)
                    {
                    if(abs($osum - $f_sum) > PHP_FLOAT_EPSILON)
                      {
                      $sqls = "SELECT SUM(si.f_sum) invsum, ifnull($field,0) dtsum 
                               FROM ".DBPref."spec_invoices si, ".DBPref."dt dt 
                               WHERE si.f_idoper=$oidoper AND si.f_dtid=$odtid AND dt.f_id=si.f_dtid";
                      $conn = $dbh->query($sqls);
                      if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                        {
                        $invsum = $rows['invsum'];
                        $dtsum = $rows['dtsum'];
                        if(abs($invsum - $dtsum) < PHP_FLOAT_EPSILON)
                        //if($invsum!=$dtsum)
                          {
                          $kr = -3;
                          if(strlen($errmsg) > 0)
                            {$errmsg .= "; ";}
                          $errmsg .= "Сумма связанных операций Поручение на исполнение ЕЛС ($invsum) и сумма, указанная в Информации о ТО ($dtsum) различаются";
                          }
                        }
                      }
                    }
                  }
                }
              // ~ sdid 2168
              }
            //~sdid2232
            }
          //~sdid 1315
          }
// sdid 1314
        elseif(strcmp($tblname, DBPref."akts") === 0)
          {
          $operid = 0;
          if(isset($ik['operid'])){$operid=$ik['operid'];}
          elseif(isset($ik['f_operid'])){$operid=$ik['f_operid'];}
          if($operid > 0)
            {
            $sql = "SELECT COUNT(*) ctgs FROM veda_categs WHERE f_ctgtype = 33 AND f_objecttype = 5 AND f_valstr IN (1,2) AND f_objectid = " . $operid;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if($row['ctgs'] > 0)
                {$kr = -3;$errmsg = "Запрещено привязывать и создавать финансовые документы по операциям, у которых (Учет расхода) равен (Учесть как расход ОВЭД) или (Учесть как расход ОЛ)";}
              }
            }
          }
        // ~ sdid 1314
        // sdid 2549
        elseif(strcmp($tblname,DBPref."maspayfe_opers")===0)
          {
          if(isset($ik['f_sum']))
            {
            $sql = "SELECT mo.f_sum, mo.f_maspayfeid, m.f_acchistid 
                    FROM ".DBPref."maspayfe_opers mo, ".DBPref."maspayfe m 
                    WHERE m.f_id = mo.f_maspayfeid AND m.f_type = 4 AND mo.f_id = ".$curidx;
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              $osum = $row['f_sum'];
              if(abs($row['f_sum'] - $ik['f_sum']) > PHP_FLOAT_EPSILON)
                {
                if($row['f_acchistid'] > 0)
                  {
                  $kr = -3;
                  $errmsg = "К записи обобщенного платежа привязана выписка. Изменение суммы запрещено.";
                  }
                }
              }
            }
          }
        // ~ sdid 2549
        $menomes=0;
        if(isset($ik['menomes']))
          {if($ik['menomes']==1){$menomes=1;}}
        //echo $sql."<br>";
        foreach((Array)$ik as $key => $value)
          { 
          //$retval=$retval.$key." = ".$value." | "; 
          if(($key!="id")&&($key!="oper")&&($key!="curidx")&&($key!="curtbl")&&($key!="menomes"))
            {
            if(strlen($strvalues)>0)
              {$strvalues=$strvalues.",";}
            if(strcmp(substr($key,0,2),"f_")!=0)
              {$strvalues=$strvalues."f_";}
            $strvalues=$strvalues.$key."=";
            $value = str_replace("\\","&bsol;",$value);
            if((strcmp($value,"true")==0)||(strcmp($value,"Yes")==0)||(strcmp($value,"on")==0))
              {$strvalues=$strvalues."1";}
            else
              {
              if((strcmp($value,"false")==0)||(strcmp($value,"No")==0)||(strcmp($value,"off")==0))
                {$strvalues=$strvalues."0";}
              else
                {
                if(is_string($value))
                  {
                  //$strvalues=$strvalues."'".$value."'";
                  if(strcmp(substr($value,0,1),"'")==0){$strvalues=$strvalues.$value;}
                  else{$strvalues=$strvalues."'".str_replace("'","\'",$value)."'";}
                  }
                else
                  {$strvalues=$strvalues.$value;}
                }
              }
            }
          }
        //echo $strvalues."<br>";
        if(strlen($strvalues)>0)
          {
          if(isTblDttmcrUserid($tblname)==1)
            {
            if(!isset($_SESSION['loginid'])){$_SESSION['loginid']=2;}
            //$strvalues = $strvalues.",f_dttmcr=NOW(),f_userid=".$_SESSION['loginid'];
            $strvalues = $strvalues.",f_dttmupd=NOW(),f_useridupd=".$_SESSION['loginid'];
            }
          $oldktkid = 0;
          $nstatus = 0;
          $ostatus = 0;
          $sql1="";
          //sdid 1214
          //$oroutecom = "";
          //~sdid 1214
          //sdid 1361
          if(strcmp($tblname,DBPref."akts")==0)//Закрыв-е документы
            {
            if((isset($ik['dogid'])||isset($ik['f_dogid']))&&(isset($ik['type'])||isset($ik['f_type']))&&(isset($ik['dogtype'])||isset($ik['f_dogtype'])))
              {
              $typdoc = 0;
              $dogid = 0;
              $dogtype = 0;
              if(isset($ik['type'])){$typdoc = $ik['type'];}
              elseif(isset($ik['f_type'])){$typdoc = $ik['f_type'];}
              if($typdoc==7) //Поступления (акты, накладные)
                {
                if(isset($ik['dogid'])){$dogid = $ik['dogid'];}
                elseif(isset($ik['f_dogid'])){$dogid = $ik['f_dogid'];}
                if(isset($ik['dogtype'])){$dogtype = $ik['dogtype'];}
                elseif(isset($ik['f_dogtype'])){$dogtype = $ik['f_dogtype'];}

                if($dogid==0 || $dogtype==0)
                  {
                  $kr     = -3;
                  $errmsg = "Необходимо заполнить поля \"Договор\" и \"Тип договора\"";
                  }
                } 
              }
            }
          //~sdid 1361
          elseif(strcmp($tblname,DBPref."cash")==0)//касса
            {
            $sql1 = "select f_idoper from ".$tblname." where f_id=".$curidx;
            //echo $sql1;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$oidoper = $row1['f_idoper'];}
            
            //sdid - 384
            if(strcmp($ik['ispod'], "true") == 0 && $ik['f_outsum'] > 0 && strlen($ik['podid'])>0)
              { // касса, делаем подотчет при установки галочки "подотчёт"
              $cash_details = array();
              $cash_details['curtbl'] = 205;
              $cash_details['f_cashid'] = $curidx; // кассовый документ
              $cash_details['f_insum'] = $ik['f_outsum']; // приход подотчет - расход касса,
              $cash_details['f_outsum'] = $ik['f_insum']; // и наоборот
              $cash_details['f_userid'] = $ik['podid']; // ответственное лицо
              $cash_details['f_bdr'] = $ik['bdr']; // статья БДР
              $cash_details['f_dt'] = $ik['f_dt']; // дата
              $cash_details['f_val'] = $ik['val']; // валюта
              $cash_details['f_com'] = $ik['f_operdesc']; // описание
              $cash_details['f_ispod'] = "1";
              addRowTbl($cash_details);
              }
            elseif(strcmp($ik['ispod'], "false") == 0){
              $cash_sql = "SELECT f_id FROM veda_cash_details WHERE f_cashid=$curidx";
              $cash_res = $dbh->query($cash_sql);
              if($cash_row = $cash_res->fetch(PDO::FETCH_ASSOC)){
                $cash_details_to_delete = array();
                $cash_details_to_delete['curtbl'] = 205;
                $cash_details_to_delete['curidx'] = $cash_row['f_id'];
                delRowTbl($cash_details_to_delete);
                }
            }
            //~sdid - 384
            }
          elseif(strcmp($tblname,DBPref."dt")==0)//обрабатываем обновление информации о ДТ
            {
            // sdid 1553 - добавлен f_operto
            // sdid 2168
            //$sql1 = "select f_broker,f_status,f_oprtpreq,f_statusdop,f_uvedins,f_upels,f_zopltposreq,f_zopltpreq,f_TDdt,f_operto  
            //         from " . $tblname . " where f_id=" . $curidx;
            $sql1 = "select f_broker,f_status,f_oprtpreq,f_statusdop,f_uvedins,f_upels,f_zopltposreq,f_zopltpreq,f_TDdt,f_operto,f_sumst,f_sumip,f_sumsp,f_sumnds  
                     from " . $tblname . " where f_id=" . $curidx;
            // ~ sdid 2168
            //echo $sql1;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus      = $row1['f_status'];
              $ooprtpreq    = $row1['f_oprtpreq'];
              $ostatusdop   = $row1['f_statusdop'];
              $ouvedins     = $row1['f_uvedins'];
              $oupels       = $row1['f_upels'];
              $ozopltposreq = $row1['f_zopltposreq'];
              $ozopltpreq   = $row1['f_zopltpreq'];
              $obroker      = $row1['f_broker'];
              $otddt        = $row1['f_TDdt'];
              $ooperto = $row1['f_operto']; // sdid 1553
              // sdid 2168
              $osumst       = $row1['f_sumst'];
              $osumip       = $row1['f_sumip'];
              $osumsp       = $row1['f_sumsp'];
              $osumnds      = $row1['f_sumnds'];
              // 2.1. Если установлен признак "Пополнение ЕЛС\Пополнить ЕЛС" и были изменения любой из сумм "Сумма сборов", "Сумма пошлины", "Сумма спец пошлин", "Сумма НДС"
              $upels = 'false';
              if(isset($ik['upels'])){$upels=$ik['upels'];}
              elseif(isset($ik['f_upels'])){$upels=$ik['f_upels'];}
              if((boolean)json_decode(strtolower($upels)) === true)
                {
                // f_idoper: f_sumnds 80, f_sumip 78, f_sumst 76, f_sumsp 99; f_dtid - curidx
                $sumst_diff = $osumst - $ik['f_sumst'];
                $sumip_diff = $osumip - $ik['f_sumip'];
                $sumsp_diff = $osumsp - $ik['f_sumsp'];
                $sumnds_diff = $osumnds - $ik['f_sumnds'];
                $old_sumst_inv = 0;
                $old_sumip_inv = 0;
                $old_sumsp_inv = 0;
                $old_sumnds_inv = 0;
                if($row1['f_sumst']>=0)
                  {
                  if(abs($sumst_diff) > PHP_FLOAT_EPSILON)
                    {
                    $sqls = "SELECT SUM(f_sum) s FROM ".DBPref."spec_invoices WHERE f_idoper=76 AND f_dtid=$curidx";
                    $conn = $dbh->query($sqls);
                    if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                      {
                      $old_sumst_inv = $rows['s'];
                      }
                    $sums_diff = $old_sumst_inv - $ik['f_sumst'];
                    if($old_sumst_inv > 0)
                      {
                      if($sumst_diff > 0)
                        {
                        // 2.1.1. Если какая-то из сумм стала меньше чем была и при этом по ней были созданы соответствующие операция на большую сумму - запрещать изменение с выводом сообщения, без закрытия формы
                        if($sums_diff > 0)
                          {
                          $kr = -3;
                          if(strlen($errmsg) > 0){$errmsg .= "; ";}
                          $errmsg .= "Сумма сборов стала меньше. По этой сумме уже созданы операции на большую сумму";
                          }
                        }
                      }
                    }
                  elseif($oupels==0)//если просто галочку поставили
                    {
                    if($old_sumst_inv>$ik['f_sumst'])
                      {
                      $kr = -3;
                      if(strlen($errmsg) > 0){$errmsg .= "; ";}
                      $errmsg .= "Сумма пошлины меньше, по этой сумме уже созданы операции на большую сумму";
                      }
                    }
                  }
                if($row1['f_sumip']>=0)
                  {
                  $sqls = "SELECT SUM(f_sum) s FROM ".DBPref."spec_invoices WHERE f_idoper=78 AND f_dtid=$curidx";
                  $conn = $dbh->query($sqls);
                  if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $old_sumip_inv = $rows['s'];
                    }
                  if(abs($sumip_diff) > PHP_FLOAT_EPSILON)
                    {
                    $sums_diff = $old_sumip_inv - $ik['f_sumip'];
                    if($old_sumip_inv > 0)
                      {
                      if($sumip_diff > 0)
                        {
                        // 2.1.1. Если какая-то из сумм стала меньше чем была и при этом по ней были созданы соответствующие операция на большую сумму - запрещать изменение с выводом сообщения, без закрытия формы
                        if($sums_diff > 0)
                          {
                          $kr = -3;
                          if(strlen($errmsg) > 0){$errmsg .= "; ";}
                          $errmsg .= "Сумма пошлины стала меньше. По этой сумме уже созданы операции на большую сумму";
                          }
                        }
                      }
                    }
                  elseif($oupels==0)//если просто галочку поставили
                    {
                    if($old_sumip_inv>$ik['f_sumip'])
                      {
                      $kr = -3;
                      if(strlen($errmsg) > 0){$errmsg .= "; ";}
                      $errmsg .= "Сумма пошлины меньше, по этой сумме уже созданы операции на большую сумму";
                      }
                    }
                  }
                if($row1['f_sumsp']>=0)
                  {
                  $sqls = "SELECT SUM(f_sum) s FROM ".DBPref."spec_invoices WHERE f_idoper=99 AND f_dtid=$curidx";
                  $conn = $dbh->query($sqls);
                  if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $old_sumsp_inv = $rows['s'];
                    }
                  if(abs($sumsp_diff) > PHP_FLOAT_EPSILON)
                    {
                    $sums_diff = $old_sumsp_inv - $ik['f_sumsp'];
                    if($old_sumsp_inv > 0)
                      {
                      if($sumsp_diff > 0)
                        {
                        // 2.1.1. Если какая-то из сумм стала меньше чем была и при этом по ней были созданы соответствующие операция на большую сумму - запрещать изменение с выводом сообщения, без закрытия формы
                        if($sums_diff > 0)
                          {
                          $kr = -3;
                          if(strlen($errmsg) > 0){$errmsg .= "; ";}
                          $errmsg .= "Сумма спец пошлин стала меньше. По этой сумме уже созданы операции на большую сумму";
                          }
                        }
                      }
                    }
                  elseif($oupels==0)//если просто галочку поставили
                    {
                    if($old_sumsp_inv>$ik['f_sumsp'])
                      {
                      $kr = -3;
                      if(strlen($errmsg) > 0){$errmsg .= "; ";}
                      $errmsg .= "Сумма спец пошлин меньше, по этой сумме уже созданы операции на большую сумму";
                      }
                    }
                  }
                if($ik['f_sumnds']>=0)
                  {
                  $sqls = "SELECT SUM(f_sum) s FROM ".DBPref."spec_invoices WHERE f_idoper=80 AND f_dtid=$curidx";
                  $conn = $dbh->query($sqls);
                  if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $old_sumnds_inv = $rows['s'];
                    }
                  if(abs($sumnds_diff) > PHP_FLOAT_EPSILON)//если сумма изменилась
                    {
                    $sums_diff = $old_sumnds_inv - $ik['f_sumnds'];
                    if($old_sumnds_inv > 0)
                      {
                      if($sumnds_diff > 0)
                        {
                        // 2.1.1. Если какая-то из сумм стала меньше чем была и при этом по ней были созданы соответствующие операция на большую сумму - запрещать изменение с выводом сообщения, без закрытия формы
                        if($sums_diff > 0)
                          {
                          $kr = -3;
                          if(strlen($errmsg) > 0){$errmsg .= "; ";}
                          $errmsg .= "Сумма НДС стала меньше. По этой сумме уже созданы операции на большую сумму";
                          }
                        }
                      }
                    }
                  elseif($oupels==0)//если просто галочку поставили
                    {
                    if($old_sumnds_inv>$ik['f_sumnds'])
                      {
                      $kr = -3;
                      if(strlen($errmsg) > 0){$errmsg .= "; ";}
                      $errmsg .= "Сумма НДС меньше, по этой сумме уже созданы операции на большую сумму";
                      }
                    }
                  }
                //$kr = -3;
                //if(strlen($errmsg) > 0){$errmsg .= "; ";}
                //$errmsg .= "$osumnds|".$ik['f_sumnds'];
                }
              //~sdid 2168
              }
            }
          elseif(strcmp($tblname,DBPref."ensures")==0)//обрабатываем обновление информации о страховке
            {
          //sdid 1388
            /*$sql1 = "select f_status,f_enssump,f_enssumpval from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$ostatus = $row1['f_status'];$oenssump=$row1['f_enssump'];$oenssumpval=$row1['f_enssumpval'];}*/
            //$sql1 = "select f_status,f_enssump,f_enssumpval,f_dogid from ".$tblname." where f_id=".$curidx;
            $sql1 = "select f_status,f_sumens,f_enssump,f_enssumpval,f_dogid from " . $tblname . " where f_id=" . $curidx; 
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $row1['f_status'];
              $oenssump=$row1['f_enssump'];
              $oenssumpval=$row1['f_enssumpval'];
              $oinsdogid=$row1['f_dogid'];
              $osumens = $row1['f_sumens'];}
          //~sdid 1388
            // sdid 1482
            /*
1. При установке "Статус страховки" в "Страхование\Страхование перевозок" значение "Аннулирована".
1.1. Если связанная спецификация закрыта, то запрещать аннулировать.
1.2. Если есть операция оплаты и к ней привязан закрывающий документ, выгруженный в 1С и дата закрывающего документа в закрытом бух. периоде - запрещаем аннулировать.
1.3. Если пункты 1.1. и 1.2. не выполнены, то меняем статус страховки, закрывающий документ, если он выгружен в 1С - аннулируем в 1С.
            */
            if(isset($ik['status'])||isset($ik['f_status']))
              {
              if(isset($ik['status'])){$cstatus=$ik['status'];}else{$cstatus=$ik['f_status'];}
              if($cstatus!=$ostatus) // max 2023-07-26
//              if($ik['f_status']!=$ostatus) // max 2023-07-26
                {
                if($cstatus == 5) // При установке "Статус страховки" в "Страхование\Страхование перевозок" значение "Аннулирована" // max 2023-07-26
//                if($ik['f_status'] == 5) // При установке "Статус страховки" в "Страхование\Страхование перевозок" значение "Аннулирована"
                  {$sql_specs = "SELECT s.f_id, s.f_status FROM " . DBPref . "specs s, " . DBPref . "ensures e WHERE s.f_id=e.f_objid AND e.f_objtype=2 AND e.f_id=" . $curidx;
                  $res_specs = $dbh->query($sql_specs);
                  if($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                    {if(isset($row_specs["f_status"])) 
                      {if($row_specs["f_status"] == 6) // Если связанная спецификация закрыта, то запрещать аннулировать.
                        {$retval = "[false,\"Запрещено аннулировать данную страховку: связанная спецификация (ИД " . $row_specs["f_id"] . ") закрыта\"]";
                        return $retval;}
                      if($row_specs["f_status"] == 8)
                        {$retval = "[false,\"Запрещено аннулировать данную страховку: РП согласован по связанной спецификации (ИД " . $row_specs["f_id"] . ")\"]";
                        return $retval;}}
                    $dtbuhcls = ""; // Дата последнего закрытого периода
                    $sql_dtbuhcls = "SELECT MAX(f_dtbuhcls) dtbuhcls FROM " . DBPref . "settings";
                    $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
                    if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                      {$dtbuhcls = strtotime($row_dtbuhcls["dtbuhcls"]);}
                    if(isset($row_specs["f_id"]))
                      {$sql_invoices = "SELECT i.f_id FROM " . DBPref . "spec_invoices i, " . DBPref . "typeopers t WHERE i.f_idoper=t.f_id AND t.f_name LIKE \"%Страхов%\" AND i.f_specid=" . $row_specs["f_id"];
                      $res_invoices = $dbh->query($sql_invoices);
                      while ($row_invoices = $res_invoices->fetch(PDO::FETCH_ASSOC))
                        {$sql_akt = "SELECT a.f_id, a.f_dt1c, a.f_kod1c FROM " . DBPref . "akts a WHERE a.f_operid=" . $row_invoices["f_id"];
                        $res_akt = $dbh->query($sql_akt);
                        while($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                          {if(isset($row_akt["f_kod1c"]))
                            {if(strlen($row_akt["f_kod1c"]) > 0)
                              {if(isset($row_akt["f_dt1c"]))
                                {if(strcmp($row_akt["f_dt1c"], "0000-00-00") != 0)
                                  {$dt1c = strtotime($row_akt["f_dt1c"]);
                                  if($dt1c < $dtbuhcls) // 1.2. Если есть операция оплаты и к ней привязан закрывающий документ, выгруженный в 1С и дата закрывающего документа в закрытом бух. периоде - запрещаем аннулировать.
                                    {$retval = "[false,\"Запрещено аннулировать данную страховку: дата закрывающего документа (ИД " . $row_akt["f_id"] . ") в закрытом бух. периоде \"]";
                                    return $retval;}}}}}}}}}}
                }
              }
            if(isset($ik['f_sumens']))
              {
              if($ik['f_sumens']!=$osumens) // 2. При изменении суммы страховой премии
                {$sql_specs = "SELECT s.f_id, s.f_status FROM " . DBPref . "specs s, " . DBPref . "ensures e WHERE s.f_id=e.f_objid AND e.f_objtype=2 AND e.f_id=" . $curidx;
                $res_specs = $dbh->query($sql_specs);
                if($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                  {if($row_specs["f_status"] == 6)
                    {$retval = "[false,\"Запрещено изменять сумму страховой премии: связанная спецификация (ИД " . $row_specs["f_id"] . ") закрыта\"]";
                    return $retval;}
                  if($row_specs["f_status"] == 8)
                    {$retval = "[false,\"Запрещено изменять сумму страховой премии: РП согласован по связанной спецификации (ИД " . $row_specs["f_id"] . ")\"]";
                    return $retval;}
                  $dtbuhcls = ""; // Дата последнего закрытого периода
                  $sql_dtbuhcls = "SELECT MAX(f_dtbuhcls) dtbuhcls FROM " . DBPref . "settings";
                  $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
                  if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                    {$dtbuhcls = strtotime($row_dtbuhcls["dtbuhcls"]);}
                  if(isset($row_specs["f_id"]))
                    {$sql_invoices = "SELECT i.f_id FROM " . DBPref . "spec_invoices i, " . DBPref . "typeopers t WHERE i.f_idoper=t.f_id AND t.f_name LIKE \"%Страхов%\" AND i.f_specid=" . $row_specs["f_id"];
                    $res_invoices = $dbh->query($sql_invoices);
                    while ($row_invoices = $res_invoices->fetch(PDO::FETCH_ASSOC))
                      {$sql_akt = "SELECT a.f_id, a.f_dt1c, a.f_kod1c FROM " . DBPref . "akts a WHERE a.f_operid=" . $row_invoices["f_id"];
                      $res_akt = $dbh->query($sql_akt);
                      while ($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                        {if(isset($row_akt["f_kod1c"]))
                          {if(strlen($row_akt["f_kod1c"]) > 0)
                            {if(isset($row_akt["f_dt1c"]))
                              {if(strcmp($row_akt["f_dt1c"], "0000-00-00") != 0)
                                {$dt1c = strtotime($row_akt["f_dt1c"]);
                                if($dt1c < $dtbuhcls) // 2. При изменении суммы страховки, если закрывающий документ, связанный с операций оплаты выгружен в 1С и в закрытом бух. периоде, то запрещаем изменять сумму.
                                  {$retval = "[false,\"Запрещено изменять сумму страховой премии: дата закрывающего документа (ИД " . $row_akt["f_id"] . ") в закрытом бух. периоде \"]";
                                  return $retval;}}}}}}}}}
                }
              }
            // ~sdid 1482
            //sdid 3550
            $nensdt = "";
            if(isset($ik['f_ensdt']))
              {
              $nensdt = $ik['f_ensdt'];
              }
            //~sdid 3550
            }
          //sdid 1257
          elseif(strcmp($tblname,DBPref."insuredeclare")==0)//обрабатываем обновление Страхование\Декларации
            {
            $sql1 = "select f_status from ".$tblname." where f_id=:curidx";
            $res1 = $dbh->prepare($sql1);
            $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
            $res1->execute();
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$ostatus = $row1['f_status'];}
            //echo $sql1."|".$ostatus;
            }
          //!sdid 1257
          elseif(strcmp($tblname,DBPref."prjparts")==0)
            {
            $sql1 = "select f_typeobj,f_zam,f_typerole from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              //отрправляем уведомление сотруднику бухгалтерии, которого назначали замещающим бухгалтером по спецификации/заявке
              if(($row1['f_typerole']==2)&&($row1['f_typeobj']==1))
                {$ozam = $row1['f_zam'];}
              }
            }
/*          elseif(strcmp($tblname,DBPref."routes")==0)//обновляем запись в маршруте
            {
            $sql1 = "select f_status,f_p1dtp,f_p2dtp,f_p2dt from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$ostatus = $row1['f_status'];
               $odt1=$row1['f_p1dtp'];
               $odt2=$row1['f_p2dtp'];
               $odtf2=$row1['f_p2dt'];}
            }
*/
//sdid 962
          elseif(strcmp($tblname,DBPref."routes")==0)//обновляем запись в маршруте
            {
            //sdid 1214
            $oroutecom = "";
            $odt1      = "0000-00-00";//sdid2860
            $odt2      = "0000-00-00 00:00:00";//sdid2860
            //$sql1 = "select f_status,f_p1dtp,f_p2dtp,f_p2dt,f_p1location,f_p2location from ".$tblname." where f_id=".$curidx;
//            $sql1 = "select f_status,f_p1dtp,f_p2dtp,f_p2dt,f_p1location,f_p2location,f_com from ".$tblname." where f_id=".$curidx;
//            //~sdid 1214
//            $res1 = $dbh->query($sql1);
//            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
//              {$ostatus     = $row1['f_status'];
//               $odt1        = $row1['f_p1dtp'];
//               $odt2        = $row1['f_p2dtp'];
//               $odtf2       = $row1['f_p2dt'];
//               $op1location = $row1['f_p1location'];
//               $op2location = $row1['f_p2location'];
//               //sdid 1214
//               $oroutecom   = $row1['f_com'];
//               //~sdid 1214
//               }

            // sdid 1429
            //sdid 2950
            //$sql1 = "select f_status,ifnull(f_p1dt,'0000-00-00') f_p1dt,ifnull(f_p2dt,'0000-00-00 00:00:00') f_p2dt,f_p1location,f_p2location,f_com from " . $tblname . " where f_id=" . $curidx;//sdid2860
            $sql1 = "
                    select r.f_status,ifnull(r.f_p1dt,'0000-00-00') f_p1dt,ifnull(r.f_p2dt,'0000-00-00 00:00:00') f_p2dt,
                            r.f_p1location,r.f_p2location,r.f_com,r.f_parentid,
                            r.f_p1dtppsdt, #sdid 3131
                            case when r.f_parentid>0 then
                                   (select f_postid from veda_routes where f_id=r.f_parentid)
                                 else 0
                            end parentpostid
                    from veda_routes r where f_id=$curidx;
                    ";
            //~sdid 2950
            //~sdid 1214
            $res1 = $dbh->query($sql1);
            if ($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $row1['f_status'];
              $odt1 = $row1['f_p1dt'];
              $odt2 = $row1['f_p2dt'];
              $odtf2 = $row1['f_p2dt'];
              $op1location = $row1['f_p1location'];
              $op2location = $row1['f_p2location'];
              //sdid 1214
              $oroutecom = $row1['f_com'];
              //~sdid 1214
              $op1dtppsdt = $row1['f_p1dtppsdt']; //sdid 3131
              //sdid 2950
              // Если у маршрута есть "предок", проверяем соответствие ИД поставки у предка и текущего маршрута. Если отличаются, запрещаем сохранение изменений
              if($row1['f_parentid']>0)
                {
                $curpostid = "";
                if(isset($ik['postid'])) 
                  {
                  $curpostid = $ik['postid'];
                  if(strcmp($curpostid,$row1['parentpostid'])!=0)
                    {
                    $kr = -3;
                    if(strlen($errmsg)>0){$errmsg .= "; ";}
                    $errmsg .= "Выбранный номер Доставки $curpostid не совпадает с номером Доставки ".$row1['parentpostid']." предка маршрута";
                    }
                  }
                }
              //~sdid 2950
              }
            // ~sdid 1429
            }
//~sdid 962
          elseif(strcmp($tblname,DBPref."routes_ktk")==0)//обновляем запись в маршруте+ktk
            {
            $sql1 = "select f_ktkid,f_status from ".$tblname." where f_id=".$curidx;
            //echo $sql1."|<br>";
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $oldktkid = $row1['f_ktkid'];
              $ostatus  = $row1['f_status'];
              }
            }
          elseif(strcmp($tblname,DBPref."specs")==0)//обрабатываем обновление спецификации
            {
            //sdid 2084 tz2 2
            /*$sql1 = "select f_operid,f_reqoto,f_reqol,f_reqoss,f_status,f_comisprep,f_wens,f_wsert,f_wto,f_wpost,f_postid,f_dttoclnt,f_dtclose,f_levelrp, 
                       f_uved,f_uvedins,f_uvedto,f_uvedcrt,f_buhid,f_tpdog 
                     from ".$tblname." where f_id=".$curidx;*/
            $ouvedto = 0;
            //sdid 2224
            /*$sql1 = "select f_operid,f_reqoto,f_reqol,f_reqoss,f_status,f_comisprep,f_wens,f_wsert,f_wto,f_wpost,f_postid,f_dttoclnt,f_dtclose,f_levelrp, 
                       f_uved,f_uvedins,f_uvedto,f_uvedcrt,f_buhid,f_tpdog,f_todog,ifnull(f_dtarrivalto,'') f_dtarrivalto,f_chkvvoz,f_chktnved,f_chkmark,f_com_to
                     from ".$tblname." where f_id=".$curidx; */
            //sdid 3131
            /*$sql1 = "select f_operid,f_reqoto,f_reqol,f_reqoss,f_status,f_comisprep,f_wens,f_wsert,f_wto,f_wpost,f_postid,f_dttoclnt,f_dtclose,f_levelrp, 
                       f_dtsenddoctoclnt,f_parentspecid,f_is_extra_spec,#sdid2390
                       f_uved,f_uvedins,f_uvedto,f_uvedcrt,f_buhid,f_tpdog,f_todog,ifnull(f_dtarrivalto,'') f_dtarrivalto,f_chkvvoz,f_chktnved,f_chkmark,f_com_to,f_certdog 
                     from ".$tblname." where f_id=".$curidx; */
            $sql1 = "select s.f_operid,s.f_reqoto,s.f_reqol,s.f_reqoss,s.f_status,s.f_comisprep,s.f_wens,s.f_wsert,s.f_wto,s.f_wpost,s.f_postid,s.f_dttoclnt,s.f_dtclose,s.f_levelrp, 
                       s.f_dtsenddoctoclnt,s.f_parentspecid,s.f_is_extra_spec,#sdid2390
                       s.f_uved,s.f_uvedins,s.f_uvedto,s.f_uvedcrt,s.f_buhid,s.f_tpdog,s.f_todog,ifnull(s.f_dtarrivalto,'') f_dtarrivalto,s.f_chkvvoz,s.f_chktnved,s.f_chkmark,
                       s.f_com_to,s.f_certdog,
                       ((select count(*) from veda_routes r where r.f_postid=s.f_postid and r.f_p1dtppsdt=1) +
                        (select count(*) from veda_routes r, veda_routes_spec rs where rs.f_specid=s.f_id and rs.f_routeid=r.f_id and r.f_p1dtppsdt=1)) isp1dtppsdt
                     from ".$tblname." s where s.f_id=".$curidx; 
            //~sdid 3131
            //~sdid 2224
            //~sdid 2084 tz2 2
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus    = $row1['f_status'];
              $ocomisprep = $row1['f_comisprep'];
              $ouved      = $row1['f_uved'];
              $ouvedins   = $row1['f_uvedins'];
              $ouvedto    = $row1['f_uvedto'];
              $ouvedcrt   = $row1['f_uvedcrt'];
              $owens      = $row1['f_wens'];
              $owsert     = $row1['f_wsert'];
              $owto       = $row1['f_wto'];
              //sdid - 391
              $otpdog     = $row1['f_tpdog'];
              //~sdid - 391
              //sdid 2084
              $otodog     = $row1['f_todog'];
              //~sdid 2084
              //sdid 2084 tz2 2
              $odtarrivalto = $row1['f_dtarrivalto'];
              //~sdid 2084 tz2 2
              //sdid 2224 
              $ocertdog   = $row1['f_certdog'];
              //~sdid 2224
              $owpost     = $row1['f_wpost'];
              $opostid    = $row1['f_postid'];
              $oreqoto    = $row1['f_reqoto'];
              $oreqol     = $row1['f_reqol'];
              $oreqoss    = $row1['f_reqoss'];
              $obuhid     = $row1['f_buhid'];
              //sdid2390
              $odtsenddoctoclnt = $row1['f_dtsenddoctoclnt'];
              $oparentspecid    = $row1['f_parentspecid'];
              $ois_extra_spec   = $row1['f_is_extra_spec'];
              //~sdid2390
              //sdid - 277
              $ooperid    = $row1['f_operid'];
              $odttoclnt  = $row1['f_dttoclnt'];
              $dtclose    = $row1['f_dtclose'];
              $levelrp    = $row1['f_levelrp'];
              //~sdid - 277
              }
              //sdid - 254
              //Запрос на изменение даты отбора для спецификации в таблице veda_repitogsumdoh
              //$update_query = "UPDATE veda_repitogsumdoh report 
              //                  SET report.f_sort_dt = '".$ik['f_dtclose']."'
              //                  WHERE report.f_spec LIKE CONCAT('%(ИД ', $curidx, ')%') AND report.f_razdel_id in (1, 2, 3, 4) AND ".strlen($ik['f_dtclose']).">0";
              //$response = $dbh->query($update_query);
              //~sdid - 254
            //sdid 1315
            if((isset($ik['status']))||(isset($ik['f_status'])))
              {
              $cstatus = -1;
              if(isset($ik['status'])){$cstatus = $ik['status'];}
              elseif(isset($ik['f_status'])){$cstatus = $ik['f_status'];}
              if($cstatus>=0)
                {
                if($cstatus!=$ostatus)//если будет изменяться статус
                  {
                  if(($cstatus==6)||($cstatus==8)||($cstatus==17))//проверяем можно ли установить статусы "Спец. Закрыта" или "Спец. РП. Согласован."
                    {
                    //$stnum = $ik['status'];
                    $stnum = $cstatus;
                    $sql = "select 
                              (select f_name from ".DBPref."spr where f_type=6 and f_num=:stnum) stat,
                              (select count(*) cnt from ".DBPref."spec_invoices si where si.f_parenttype=2 and si.f_specid=:curidx and si.f_bdrarticle>0 and si.f_checked1=0) ch1,
                              (select count(*) cnt from ".DBPref."spec_invoices si where si.f_parenttype=2 and si.f_specid=:curidx and si.f_bdrarticle>0 and si.f_checked2=0) ch2,
                              (select count(*) cnt from ".DBPref."spec_invoices si where si.f_parenttype=2 and si.f_specid=:curidx and si.f_bdrarticle>0 and si.f_checked3=0) ch3";
                    $res1 = $dbh->prepare($sql);
                    $res1->bindParam(':stnum',$stnum,PDO::PARAM_INT);
                    $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                    $res1->execute();
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if(($row1['ch1']>0)||($row1['ch2']>0)||($row1['ch3']>0))
                        {
                        //$kr     = -3;
                        //$errmsg = "Запрещено устанавливать статус \"".$row1['stat']."\", не проверены операции:<br>";
                        //if($row1['ch1']>0){$errmsg=$errmsg."логистами <br>";}
                        //if($row1['ch2']>0){$errmsg=$errmsg."бухгалтерией <br>";}
                        //if($row1['ch3']>0){$errmsg=$errmsg."менеджерами <br>";}
                        }
                      }
                    }
                  }
                }
              }
            //~sdid 1315
          //sdid 2084 tz2 2
          // Если выставлена галка Уведомить специалиста ТО
          if(isset($ik['uvedto']))
            {
            if($ik['uvedto']=="true")
              {
              if(isset($ik['todog']))
                {
                if($ik['todog']!=10 && $ik['todog']!=0) // если НЕ вариант Нет ТО и не вариант Не выбрано
                  {
                  $isdtarrivalto = 0;
                  $ischkvvoz     = 0;
                  $ischktnved    = 0;
                  $ischkmark     = 0;
                  $iscomto       = 0;
                  if(isset($ik['f_dtarrivalto'])) 
                    {
                    if(strlen($ik['f_dtarrivalto'])>0 && strcmp($ik['f_dtarrivalto'],"0000-00-00 00:00:00")!=0 && strcmp($ik['f_dtarrivalto'],"0000-00-00")!=0)
                      {
                      $isdtarrivalto = 1;
                      }
                    }
                  if(isset($ik['f_chkvvoz']))  {if($ik['f_chkvvoz']=="true") {$ischkvvoz = 1;}}
                  if(isset($ik['f_chktnved'])) {if($ik['f_chktnved']=="true") {$ischktnved = 1;}}
                  if(isset($ik['f_chkmark']))  {if($ik['f_chkmark']=="true") {$ischkmark = 1;}}
                  if(isset($ik['f_com_to']))   {if(strlen($ik['f_com_to'])>0) {$iscomto = 1;}}
                  if(($isdtarrivalto==1) || (($ischkvvoz+$ischktnved+$ischkmark+$iscomto)>0) )
                    {
                    /*
                    5.2. ЕСЛИ требования 5.1.1. соблюдены - отправляем уведомление
                    5.2.1. ДОБАВИТЬ в шаблон уведомления (и/ИЛИ) создать новый шаблон для этого уведомления:
                           - дату прибытия в пункт ТО
                           - полные названия по установленным галочкам и задаче из пункта 3.3. (если они есть)
                    */
                    }
                  else
                    {
                    if($ouvedto==0)
                      {$kr = -3; $errmsg = $errmsg."\nВыставлена галочка Уведомить специалиста ТО.\nУкажите ЛИБО плановую дату прибытия в пункт ТО, ЛИБО задачу для отдела таможни";} 
                    }
                  }
                else // если вариант Нет ТО, галка уведомить специалиста ТО бессмысленна, делаем отлуп
                  {
                  $kr = -3; $errmsg = $errmsg."\nВыставлена галочка Уведомить специалиста ТО, но не выбран вариант договора ТО.\nВыберите вариант договора ТО"; 
                  }
                }
              }
            }
            //~sdid 2084 tz2 2
            ///////////////////////////////////////////////////////////////////////////////////
            //sdid 2084
            if(isset($ik['todog']))
              {
              if(($ik['todog'] == 0)) // Если  поле "Договор ТО" == 0 (значение не выбрано)
                {
                  $kr = -3; 
                  $errmsg = $errmsg."\nНеобходимо выбрать одно из значений поля Договор ТО: Нет ТО, Текущая спецификация, либо выбрать Договор ВА-ТП"; 
                }
              elseif(($ik['todog'] != $otodog)) // Если изменилось поле "Договор ТО"
                {
                $strtodog = (string) $ik['todog'];
                if(($ik['todog']==0)) // Если выбрано значение "Не задано", формируем ошибку, не даем сохранить спецификацию
                  {
                  $kr = -3; 
                  $errmsg = $errmsg."Необходимо выбрать одно из значений поля Договор ТО: Нет ТО, Текущий договор по спецификации, либо выбрать Договор ВА-ТП"; 
                  }
                elseif((substr($strtodog,0,1)==1))// Если выбрано значение "Нет ТО"
                  {
                  $derr=0;
                  //Ищем связанные записи "Информация о ТО"
                  $sql = "select f_id from veda_dt where f_specid=:curidx";
                  $res1 = $dbh->prepare($sql);
                  $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                  $res1->execute();
                  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    $ki = Array('curtbl'=>61,'curidx'=>$row1['f_id']); //удаляем связанную "Информация о ТО"
                    $rvd = json_decode(delRowTbl($ki),true);
                    if($rvd[0]==false) 
                      { $kr = -3; $errmsg = $errmsg."Ошибка удаления записи Информация о ТО (ID=".$row1['f_id']."): \n".$rvd[1]; $derr++; continue; }
                    elseif(strcmp($rvd[3],"00000")!=0)
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления записи Информация о ТО (ID=".$row1['f_id']."): \n".$rvd[5]; $derr++; continue;
                      }
                    }
                  if($derr==0)
                    {
                    //Ищем связанные с текущей спецификацией заявки ВА-ТП и наличие связанных операций
                    $sql = "select s.f_id
                              ,(select count(*) cnt from veda_spec_invoices where f_parenttype=2 and f_specid=s.f_id) sicnt
                              ,(select count(*) from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=s.f_id) cntpp 
                            from veda_specs s, veda_dogs d, veda_dogs d2
                            where d.f_id=s.f_dogid and s.f_subtype=3 and d.f_subtype=3 
                              and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                              and s.f_parentspecid=:curidx and d2.f_id=:curdogid";
                    $res2 = $dbh->prepare($sql);
                    $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                    $res2->bindParam(':curdogid',$ik['dogid'],PDO::PARAM_INT);
                    $res2->execute();
                    $derr2=0;
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      $derr2=0;
                      if($row2['sicnt']>0)
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id'].") По заявке есть связанные операции"; $derr2++; continue;
                        }
                      elseif($row2['cntpp']>0)
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id'].") По заявке внесены данные о менеджерах\\бухгалтерах"; $derr2++; continue;
                        }
                      //elseif($row2['sicnt']==0) //sdid 2290 2024-03-13
                      if(($row2['sicnt']==0) && ($row2['cntpp']==0) && ($derr2==0)) //sdid 2290 2024-03-13
                        {
                        //Ищем связанные записи "Информация о ТО", связанные с заявкой ВА-ТП 
                        $sql = "select f_id from veda_dt where f_specid=:specid";
                        $res3 = $dbh->prepare($sql);
                        $res3->bindParam(':specid',$row2['f_id'],PDO::PARAM_INT);
                        $res3->execute();
                        while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>61,'curidx'=>$row3['f_id']); //удаляем связанную "Информация о ТО"
                          $rvd = json_decode(delRowTbl($ki),true);
                          if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления записи Информация о ТО (ID=".$row3['f_id']."): \n".$rvd[1]; $derr2++; continue; }
                          elseif(strcmp($rvd[3],"00000")!=0)
                            {
                            $kr = -3; $errmsg = $errmsg."Ошибка удаления записи Информация о ТО (ID=".$row3['f_id']."): \n".$rvd[5]; $derr2++; continue;
                            }
                          }
                        }
                      if($derr2==0)
                        {
                        // Удаляем заявку ВА-ТП
                        $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); 
                        $rvd = json_decode(delRowTbl($ki),true);
                        if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id']."): \n".$rvd[1]; continue; }
                        elseif(strcmp($rvd[3],"00000")!=0)
                          {
                          $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id']."): \n".$rvd[5]; continue;
                          }
                        }
                      //sdid 2290 2024-03-13
                      else {$kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id']."): \n".$rvd[5]; continue;}
                      //~sdid 2290 2024-03-13
                      }
                    }
                  }
                elseif(substr($strtodog,0,1)==2) // Если выбрана текущая спецификация
                  {
                  $naddspecto=0;
                  //Ищем связанные с текущей спецификацией заявки ВА-ТП и наличие связанных операций
                  $sql = "select s.f_id, 
                            (select count(*) cnt from veda_spec_invoices where f_parenttype=2 and f_specid=s.f_id) sicnt
                            ,(select count(*) from ".DBPref."prjparts d where d.f_typeobj=1 and d.f_idobj=s.f_id) cntpp
                          from veda_specs s, veda_dogs d, veda_dogs d2
                          where d.f_id=s.f_dogid and s.f_subtype=3 and d.f_subtype=3 
                            and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                            and s.f_parentspecid=:curidx and d2.f_id=:curdogid";
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                  $res2->bindParam(':curdogid',$ik['dogid'],PDO::PARAM_INT);
                  $res2->execute();
                  $derr2=0;
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $derr2=0;
                    if($row2['sicnt']>0)
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id'].") По заявке есть связанные операции"; $derr2++; continue;
                      }
                    elseif($row2['cntpp']>0)
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id'].") По заявке внесены данные о менеджерах\\бухгалтерах"; $derr2++; continue;
                      }
//                    elseif($row2['sicnt']==0) 
                    //elseif($row2['sicnt']==0) //sdid 2290 2024-03-13
                    if(($row2['sicnt']==0) && ($row2['cntpp']==0) && ($derr2==0)) //sdid 2290 2024-03-13
                      {
                      //Ищем связанные записи "Информация о ТО", связанные с заявкой ВА-ТП 
                      $sql = "select f_id from veda_dt where f_specid=:specid";
                      $res3 = $dbh->prepare($sql);
                      $res3->bindParam(':specid',$row2['f_id'],PDO::PARAM_INT);
                      $res3->execute();
                      while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                        {
                        // Привязываем запись "Информация о ТО" к текущей спецификации
                        $ki = Array('curtbl'=>61,'curidx'=>$row3['f_id'], 'f_specid'=>$curidx);
                        $dtres = json_decode(editRowTbl($ki));
                        if($dtres[2]>0) {$naddspecto++;} 
                        else {$derr2++;}
                        }
                      }
                    if($derr2==0)
                      {
                      // Удаляем заявку ВА-ТП
                      $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); 
                      $rvd = json_decode(delRowTbl($ki),true);
                      if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id']."): \n".$rvd[1]; $derr2++;continue; }
                      elseif(strcmp($rvd[3],"00000")!=0)
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки ВА-ТП (ID=".$row2['f_id']."): \n".$rvd[5]; $derr2++;continue;
                        }
                      }
                    }
                  // Создаем запись "Информация о ТО" по текущей спецификации
                  if(($naddspecto==0)&&($derr2==0))
                    {
                    $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                    $typez = 0;
                    if(isset($ik['typez'])) {$typez = $ik['typez'];}
                    elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                    if($typez==1)
                    {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                    $cst = 0;
                    if($iskp==1){$cst = 23;}
                    $ki = Array('curtbl'=>61,'f_specid'=>$curidx,'f_status'=>$cst);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      if($row['f_uvedto']==1) //sdid 2290
                        {                     //sdid 2290
                        if($menomes==0)
                          {
                          $subject = "Для ".$iskps." создана запись информации о ДТ";
                          $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                          $sbsh = 0;
                          if($menomes==0) 
                            { 
                            $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                            if($sbsh == 0) {mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$ar[2]);}
                            }                     
                          }
                        }                     //sdid 2290
                      }
                    }
                  }
                elseif(substr($strtodog,0,1)==3) // Если выбран договор ВА-ТП
                  {
                  //Создаем Заявку "Поручение ТП"
                  $tpdogparm = substr($strtodog,1);
                  $sql = "select count(*) cnt from ".DBPref."specs where f_dogid=:tpdog and f_parentspecid=:parentspecid and f_subtype=3";
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':tpdog',$tpdogparm,PDO::PARAM_INT);
                  $res2->bindParam(':parentspecid',$curidx,PDO::PARAM_INT);
                  $res2->execute();
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нет ни одной связанной заявки "Поручение ТП"
                    {
                    if($row2['cnt']==0)
                      {
                      //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                      $obj = new ArrayObject($ik);
                      $ki1 = $obj->getArrayCopy();
                      $ki1['curtbl']       = 15;
                      if(isset($ki1['f_parentspecid'])) {$ki1['f_parentspecid']=$curidx;}
                      elseif(isset($ki1['parentspecid'])) {$ki1['parentspecid']=$curidx;}
                      if(isset($ki1['f_dogid'])) {$ki1['f_dogid']=$tpdogparm;}
                      elseif(isset($ki1['dogid'])) {$ki1['dogid']=$tpdogparm;}
                      else{$ki1['f_dogid']=$tpdogparm;}
                      if(isset($ki1['f_subtype'])) {$ki1['f_subtype']=3;}
                      elseif(isset($ki1['subtype'])) {$ki1['subtype']=3;}
                      else{$ki1['f_subtype']=3;}
                      if(isset($ki1['f_dt'])) {$ki1['f_dt']=date("Y-m-d");}
                      elseif(isset($ki1['dt'])) {$ki1['dt']=date("Y-m-d");}
                      if(isset($ki1['f_typez'])) {$ki1['f_typez']=3;}
                      elseif(isset($ki1['typez'])) {$ki1['typez']=3;}
                      else{$ki1['f_typez']=3;}
                      if(isset($ki1['f_kod1cb'])) {$ki1['f_kod1cb']="";}
                      elseif(isset($ki1['kod1cb'])) {$ki1['kod1cb']="";}
                      if(isset($ki1['f_kod1cp'])) {$ki1['f_kod1cp']="";}
                      elseif(isset($ki1['kod1cp'])) {$ki1['kod1cp']="";}
                      if(isset($ki1['f_wens'])) {$ki1['f_wens']=false;}
                      elseif(isset($ki1['wens'])) {$ki1['wens']=false;}
                      if(isset($ki1['f_wsert'])) {$ki1['f_wsert']=false;}
                      elseif(isset($ki1['wsert'])) {$ki1['wsert']=false;}
                      if(isset($ki1['f_wpost'])) {$ki1['f_wpost']=false;}
                      elseif(isset($ki1['wpost'])) {$ki1['wpost']=false;}
                      if(isset($ki1['f_sbor'])) {$ki1['f_sbor']=false;}
                      elseif(isset($ki1['sbor'])) {$ki1['sbor']=false;}
                      if(isset($ki1['f_uvedins'])) {$ki1['f_uvedins']=false;}
                      elseif(isset($ki1['uvedins'])) {$ki1['uvedins']=false;}
                      if(isset($ki1['f_uvedto'])) {$ki1['f_uvedto']=false;}
                      elseif(isset($ki1['uvedto'])) {$ki1['uvedto']=false;}
                      if(isset($ki1['f_uvedcrt'])) {$ki1['f_uvedcrt']=false;}
                      elseif(isset($ki1['uvedcrt'])) {$ki1['uvedcrt']=false;}
                      if(isset($ki1['f_postid'])) {$ki1['f_postid']=0;}
                      elseif(isset($ki1['postid'])) {$ki1['postid']=0;}
                      else{$ki1['f_postid']=0;}
                      if(isset($ki1['f_todog'])) {$ki1['f_todog']=20;} //sdid 2296 2024-03-12
                      elseif(isset($ki1['todog'])) {$ki1['todog']=20;} //sdid 2296 2024-03-12
                      else{$ki1['f_todog']=20;}
                      if(isset($ki1['f_certdog'])) {$ki1['f_certdog']=0;} //sdid 2224 2024-03-24
                      elseif(isset($ki1['certdog'])) {$ki1['certdog']=0;} //sdid 2224 2024-03-24
                      else{$ki1['f_certdog']=0;}
                      if(isset($ki1['id'])) {unset($ki1['id']);}
                      if(isset($ki1['curidx'])) {unset($ki1['curidx']);}
                      if(isset($ki1['f_num'])) {unset($ki1['f_num']);}
                      if(isset($ki1['num'])) {unset($ki1['num']);}
                      if(isset($ki1['f_tpdog'])) {unset($ki1['f_tpdog']);}
                      if(isset($ki1['tpdog'])) {unset($ki1['tpdog']);}
                      $ar = json_decode(addRowTbl($ki1), true);
                      if($ar[0]=="true")
                        {
                        $wdopmes = 1;
                        $ansn = $ansn."Добавили связанную заявку - Поручение ТП";
                        // Ищем связанную с текущей спецификацией запись "Информация о ТО"
                        // Ищем связанную с текущей спецификацией запись "Информация о ТО" //sdid 2290
                        // Ищем связанную с текущей либо новой заявкой спецификацией запись "Информация о ТО" //sdid 2290
                        $derr=0;
                        //$sql = "select f_id from veda_dt where f_specid=:curidx"; //sdid 2290
                        $sql = "select f_id from veda_dt where f_specid=:curidx or f_specid=:newid"; //sdid 2290
                        $res3 = $dbh->prepare($sql);
                        $res3->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                        $res3->bindParam(':newid',$ar[2],PDO::PARAM_INT); //sdid 2290
                        $res3->execute();
                        if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                          {
                          // Если нашли, привязываем запись "Информация о ТО" к новой заявке "Поручение ТП"
                          $ki = Array('curtbl'=>61,'curidx'=>$row3['f_id'],'f_specid'=>$ar[2]); 
                          editRowTbl($ki);
                          }
                        else //Если не нашли, создаем новую запись "Информация о ТО" и привязываем ее к новой заявке "Поручение ТП"
                          {
                          $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                          $typez = 0;
                          if(isset($ik['typez'])) {$typez = $ik['typez'];}
                          elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                          if($typez==1)
                            {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                          $cst = 0;
                          if($iskp==1){$cst = 23;}
                          $ki = Array('curtbl'=>61,'f_specid'=>$ar[2],'f_status'=>$cst);
                          $ar = json_decode(addRowTbl($ki), true);
                          if($ar[0]=="true")
                            {
                            $uvedto = 0;
                            if(isset($row['f_uvedto'])){$uvedto=$row['f_uvedto'];}
                            elseif(isset($row['uvedto'])){$uvedto=$row['uvedto'];}
                            if($uvedto==1) //sdid 2290
                              {                     //sdid 2290
                              if($menomes==0)
                                {
                                $subject = "Для ".$iskps." создана запись информации о ДТ";
                                $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                                $sbsh = 0;
                                if($menomes==0) 
                                  { 
                                  $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                                  if($sbsh == 0) {mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$ar[2]);}
                                  }                     
                                }
                              }                     //~sdid 2290
                            }
                          }
                        }
                      else
                        {
                        $wdopmes = 1;
                        $anns = $ansn."Ошибка добавления заявки - Поручение ТП";
                        }
                      }
                    }
                  }
                }
              }
            //~sdid 2084
            ///////////////////////////////////////////////////////////////////////////////////
            //sdid 2224
            $strcertdog = "";
            if(isset($ik['certdog'])) {$strcertdog = $ik['certdog'];}
            elseif(isset($ik['f_certdog'])) {$strcertdog = $ik['f_certdog'];}
            //sdid 2530
            if(strlen($strcertdog)>0)
              {
              $fsubtype = 0;
              if(isset($ik['subtype'])) {$fsubtype = $ik['subtype'];}
              elseif(isset($ik['f_subtype'])) {$fsubtype = $ik['f_subtype'];}
              if((substr($strcertdog,0,1)==1)&&($fsubtype!=2))
                {
                $kr = -3; $errmsg = $errmsg."Сертификация с типом \"Сертификат к этой заявке\" доступна только для Подвида заявки \"Сертификация\"";
                }
              elseif( (substr($strcertdog,0,1)==2) && ($fsubtype!=5) && ($fsubtype!=6) )
                {
                $kr = -3; $errmsg = $errmsg."Сертификация с типом \"Сертификат через доп.заявку\" доступен только для Подвида заявки \"Спецификация\" или \"Заявка\"";
                }
              }
            //if(strlen($strcertdog)>0)
            if((strlen($strcertdog)>0) && ($kr!=(-3)))
            //~sdid 2530
              {
              $ostrcertdog = (string) $ocertdog;
              if(strcmp($strcertdog,$ostrcertdog)!=0) // Если изменилось поле "Сертификация"
                {
                //$strcertdog = (string) $ik['certdog'];
                //$ostrcertdog = (string) $ocertdog;
                // Если поменяли на "Нет сертификации" и если ранее был выбран договор
                // либо
                // Если поменяли на "Текущая спецификация" и если ранее был выбран договор
                // либо
                // Если поменяли на "Договор" и если ранее был выбран другой договор
                //$derr2=0;
                /*if(((strcmp($strcertdog,'0')==0) || 
                    (substr($strcertdog,0,1)==1) || 
                    ((strcmp(substr($strcertdog,1),substr($ostrcertdog,1))!=0 ) && (substr($ostrcertdog,0,1)==2) && (substr($strcertdog,0,1)==2))
                   ) && (substr($ostrcertdog,0,1)==2))*/

                
                if(strcmp($strcertdog,'0')==0) // Если поменяли на "Нет сертификации" 
                  {
                  //Ищем записи в таблице сертификации, связанные с текущей спецификацией
                  $derr=0;
                  $sql = "select f_id from veda_certificates where f_specid=:specid";
                  $res1 = $dbh->prepare($sql);
                  $res1->bindParam(':specid',$curidx,PDO::PARAM_INT);
                  $res1->execute();
                  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    $ki = Array('curtbl'=>56,'curidx'=>$row1['f_id']); //удаляем связанную запись о сертификации
                    $rvd = json_decode(delRowTbl($ki),true);
                    if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной записи из Реестра сертификатов (ID=".$row1['f_id']."): \n".$rvd[1]; $derr++; continue; }
                    elseif(strcmp($rvd[3],"00000")!=0)
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления записи из Реестра сертификатов (ID=".$row1['f_id']."): \n".$rvd[5]; $derr++; continue;
                      }
                    }
                  if($derr==0)
                    {
                    //Ищем связанные с текущей спецификацией заявки сертификации, наличие связанных операций и наличие связанных записей пользователей в prjparts 
                    $ostrcertdogparm = substr($ostrcertdog,1);
                    $sql = "select s.f_id,
                              (select count(*) cnt from veda_spec_invoices where f_parenttype=2 and f_specid=s.f_id) sicnt,
                              (select count(*) from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=s.f_id) cntpp 
                            from veda_specs s, veda_dogs d, veda_dogs d2
                            where d.f_id=s.f_dogid and s.f_subtype=2 and d.f_dogtype=1 
                              and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                              and s.f_parentspecid=:curidx and d2.f_id=:curdogid";
                 
                    $res2 = $dbh->prepare($sql);
                    $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                    $res2->bindParam(':curdogid',$ostrcertdogparm,PDO::PARAM_INT);
                    $res2->execute();
                    $derr2=0;
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      $derr2=0;
                      if($row2['sicnt']>0) // если нашли связанные операции, выдаем ошибку
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке есть связанные операции"; $derr2++; continue;
                        }
                      elseif($row2['cntpp']>0) // если нашли связанные данные о менеджерах/бухгалтерах, выдаем ошибку
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке внесены данные о менеджерах\\бухгалтерах"; $derr2++; continue;
                        }
                      elseif($row2['sicnt']==0 && $row2['cntpp']==0) 
                        {
                        //Ищем связанные записи в таблице сертификации, связанные с заявкой Сертификации
                        $sql = "select f_id from veda_certificates where f_specid=:specid";
                        $res3 = $dbh->prepare($sql);
                        $res3->bindParam(':specid',$row2['f_id'],PDO::PARAM_INT);
                        $res3->execute();
                        while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>56,'curidx'=>$row3['f_id']); //удаляем связанную запись о сертификации
                          $rvd = json_decode(delRowTbl($ki),true);
                          if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной записи из Реестра сертификатов (ID=".$row3['f_id']."): \n".$rvd[1]; $derr2++; continue; }
                          elseif(strcmp($rvd[3],"00000")!=0)
                            {
                            $kr = -3; $errmsg = $errmsg."Ошибка удаления записи из Реестра сертификатов (ID=".$row3['f_id']."): \n".$rvd[5]; $derr2++; continue;
                            }
                          }
                        }
                      if($derr2==0)
                        {
                        // Удаляем заявку Сертификации
                        $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); 
                        $rvd = json_decode(delRowTbl($ki),true);
                        if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[1]; continue; }
                        elseif(strcmp($rvd[3],"00000")!=0)
                          {
                          $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[5]; continue;
                          }
                        //echo "|s|".json_encode($rvd)."|e|";
                        }
                      }
                    }
                  } //~ Если поменяли на "Нет сертификации" 
                elseif(substr($strcertdog,0,1)==1) // Если поменяли на "Текущая спецификация" 
                  {
                  $naddspeccert=0;
                  $ostrcertdogparm = substr($ostrcertdog,1);
                    //Ищем связанные с текущей спецификацией заявки сертификации, наличие связанных операций и наличие связанных записей пользователей в prjparts 
                  $sql = "select s.f_id,
                            (select count(*) cnt from veda_spec_invoices where f_parenttype=2 and f_specid=s.f_id) sicnt,
                            (select count(*) from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=s.f_id) cntpp 
                          from veda_specs s, veda_dogs d, veda_dogs d2
                          where d.f_id=s.f_dogid and s.f_subtype=2 and d.f_dogtype=1 
                            and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                            and s.f_parentspecid=:curidx and d2.f_id=:curdogid";
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                  $res2->bindParam(':curdogid',$ostrcertdogparm,PDO::PARAM_INT);
                  $res2->execute();
                  $derr2=0;
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $derr2=0;
                    if($row2['sicnt']>0) // если нашли связанные операции, выдаем ошибку
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке есть связанные операции"; $derr2++; continue;
                      }
                    elseif($row2['cntpp']>0) // если нашли связанные данные о менеджерах/бухгалтерах, выдаем ошибку
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке внесены данные о менеджерах\\бухгалтерах"; $derr2++; continue;
                      }
                    elseif($row2['sicnt']==0 && $row2['cntpp']==0) 
                      {
                      //Ищем связанные записи в таблице сертификации, связанные с заявкой Сертификации
                      $sql = "select f_id from veda_certificates where f_specid=:specid";
                      $res3 = $dbh->prepare($sql);
                      $res3->bindParam(':specid',$row2['f_id'],PDO::PARAM_INT);
                      $res3->execute();
                      while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                        {
                        // Привязываем запись Сертификации к текущей спецификации
                        $ki = Array('curtbl'=>56,'curidx'=>$row3['f_id'], 'f_specid'=>$curidx);
                        $dtres = json_decode(editRowTbl($ki));
                        if($dtres[2]>0) {$naddspeccert++;} 
                        else {$derr2++;}
                        }
                      }
                    if($derr2==0)
                      {
                      // Удаляем заявку Сертификации
                      $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); 
                      $rvd = json_decode(delRowTbl($ki),true);
                      if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[1]; $derr2++;continue; }
                      elseif(strcmp($rvd[3],"00000")!=0)
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[5]; $derr2++;continue;
                        }
                      }
                    }
                  // Создаем запись Сертификации по текущей спецификации
                  if(($naddspeccert==0)&&($derr2==0))
                    {
                    $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                    $typez = 0;
                    if(isset($ik['typez'])) {$typez = $ik['typez'];}
                    elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                    if($typez==1)
                    {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                   
                    $cst = 0;
                    if($iskp==1){$cst = 23;}
                    $ki = Array('curtbl'=>56,'f_specid'=>$curidx,'f_status'=>$cst);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      //sdid 2224 2024-03-24
                      if($row['f_uvedcrt']==1)
                        {
                      //~sdid 2224 2024-03-24
                        if($menomes==0)
                          {
                          $subject = "Для ".$iskps." создана запись о сертификации";
                          $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>";
                          $sbsh = 0;
                          if($menomes==0) 
                            { 
                            $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar[2], 151, 7, 13); 
                            if($sbsh == 0) 
                              { 
                              mSendMail($_SESSION['loginid'],9,0,$subject,$postbody,"",56,$ar[2]);
                              }
                            }                     
                          }
                      //sdid 2224 2024-03-24
                        }
                      //~sdid 2224 2024-03-24
                      }
                    }
                  } //~ Если поменяли на "Текущая спецификация" 
                //sdid 2224 --- 2024-03-04
                //elseif(substr($strcertdog,0,1)==2) // Если поменяли на "Договор с подвидом "Клиентский" 
                elseif((substr($strcertdog,0,1)==2) && (substr($ostrcertdog,0,1)!=2)) // Если поменяли на "Договор с подвидом "Клиентский", и старый вариант НЕ БЫЛ "Договор с подвидом "Клиентский"
                //~sdid 2224 --- 2024-03-04
                  {
                  //Создаем Заявку "Сертификация"
                  $certdogparm = substr($strcertdog,1);
                  //$sql = "select count(*) cnt from ".DBPref."specs where f_dogid=:certdog and f_parentspecid=:parentspecid and f_subtype=2";
                  $sql = "select count(*) cnt
                          from veda_specs s, veda_dogs d, veda_dogs d2
                          where d.f_id=s.f_dogid and s.f_subtype=2 and d.f_dogtype=1 
                            and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                            and s.f_parentspecid=:parentspecid and d2.f_id=:curdogid";

                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curdogid',$certdogparm,PDO::PARAM_INT);
                  $res2->bindParam(':parentspecid',$curidx,PDO::PARAM_INT);
                  $res2->execute();
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нет ни одной связанной заявки "Сертификация"
                    {
                    if($row2['cnt']==0)
                      {
                      //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                      $obj = new ArrayObject($ik);
                      $ki1 = $obj->getArrayCopy();
                      $ki1['curtbl'] = 15;
                      if(isset($ki1['f_parentspecid'])) {$ki1['f_parentspecid']=$curidx;}
                      elseif(isset($ki1['parentspecid'])) {$ki1['parentspecid']=$curidx;}
                      else{$ki1['f_parentspecid']=$curidx;}
                      if(isset($ki1['f_dogid'])) {$ki1['f_dogid']=$certdogparm;}
                      elseif(isset($ki1['dogid'])) {$ki1['dogid']=$certdogparm;}
                      else{$ki1['f_dogid']=$certdogparm;}
                      if(isset($ki1['f_subtype'])) {$ki1['f_subtype']=2;}
                      elseif(isset($ki1['subtype'])) {$ki1['subtype']=2;}
                      else{$ki1['f_subtype']=2;}
                      if(isset($ki1['f_dt'])) {$ki1['f_dt']=date("Y-m-d");}
                      elseif(isset($ki1['dt'])) {$ki1['dt']=date("Y-m-d");}
                      else{$ki1['f_dt']=date("Y-m-d");}
                      if(isset($ki1['f_typez'])) {$ki1['f_typez']=3;}
                      elseif(isset($ki1['typez'])) {$ki1['typez']=3;}
                      else{$ki1['typez']=3;}
                      if(isset($ki1['f_kod1cb'])) {$ki1['f_kod1cb']="";}
                      elseif(isset($ki1['kod1cb'])) {$ki1['kod1cb']="";}
                      if(isset($ki1['f_kod1cp'])) {$ki1['f_kod1cp']="";}
                      elseif(isset($ki1['kod1cp'])) {$ki1['kod1cp']="";}
                      if(isset($ki1['f_wens'])) {$ki1['f_wens']=false;}
                      elseif(isset($ki1['wens'])) {$ki1['wens']=false;}
                      if(isset($ki1['f_wsert'])) {$ki1['f_wsert']=true;}
                      elseif(isset($ki1['wsert'])) {$ki1['wsert']=true;}
                      if(isset($ki1['f_wpost'])) {$ki1['f_wpost']=false;}
                      elseif(isset($ki1['wpost'])) {$ki1['wpost']=false;}
                      if(isset($ki1['f_sbor'])) {$ki1['f_sbor']=false;}
                      elseif(isset($ki1['sbor'])) {$ki1['sbor']=false;}
                      if(isset($ki1['f_wto'])) {$ki1['f_wto']=false;}
                      elseif(isset($ki1['wto'])) {$ki1['wto']=false;}
                      if(isset($ki1['f_uvedins'])) {$ki1['f_uvedins']=false;}
                      elseif(isset($ki1['uvedins'])) {$ki1['uvedins']=false;}
                      if(isset($ki1['f_uvedto'])) {$ki1['f_uvedto']=false;}
                      elseif(isset($ki1['uvedto'])) {$ki1['uvedto']=false;}
                      if(isset($ki1['f_uvedcrt'])) {$ki1['f_uvedcrt']=true;} // !!!!!!
                      elseif(isset($ki1['uvedcrt'])) {$ki1['uvedcrt']=true;} // !!!!!!
                      if(isset($ki1['f_postid'])) {$ki1['f_postid']=false;}
                      elseif(isset($ki1['postid'])) {$ki1['postid']=false;}
                      if(isset($ki1['f_todog'])) {$ki1['f_todog']=10;}
                      elseif(isset($ki1['todog'])) {$ki1['todog']=10;}
                      //if(isset($ki1['f_certdog'])) {$ki1['f_certdog']=0;} //sdid 2224 2024-03-24
                      //elseif(isset($ki1['certdog'])) {$ki1['certdog']=0;} //sdid 2224 2024-03-24
                      if(isset($ki1['id'])) {unset($ki1['id']);}
                      if(isset($ki1['curidx'])) {unset($ki1['curidx']);}
                      if(isset($ki1['f_num'])) {unset($ki1['f_num']);}
                      if(isset($ki1['num'])) {unset($ki1['num']);}
                      if(isset($ki1['f_tpdog'])) {unset($ki1['f_tpdog']);}
                      if(isset($ki1['tpdog'])) {unset($ki1['tpdog']);}
                      $tar = addRowTbl($ki1);
                      //echo "tar = | ".$tar." | ";
                      $ar = json_decode($tar, true);
                      //$ar = json_decode(addRowTbl($ki1), true);
                      if($ar[0]=="true")
                        {
                        $wdopmes = 1;
                        //$ansn = $ansn."Добавили связанную заявку - Сертификация";
                        $ans = $ans."Добавили связанную заявку - Сертификация";

                        //// Ищем связанную с текущей спецификацией запись в таблице Сертификация //sdid 2224 2024-03-24
                        // Ищем связанную с текущей либо новой заявкой спецификацией запись в таблице Сертификация //sdid 2224 2024-03-24
                        $derr=0;
                        //$sql = "select f_id from veda_certificates where f_specid=:curidx"; //sdid 2224 2024-03-24
                        $sql = "select f_id from veda_certificates where f_specid=:curidx or f_specid=:newid"; //sdid 2224 2024-03-24
                        $res3 = $dbh->prepare($sql);
                        $res3->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                        $res3->bindParam(':newid',$ar[2],PDO::PARAM_INT); //sdid 2224 2024-03-24
                        $res3->execute();
                        if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                          {
                          // Если нашли, привязываем запись в таблице Сертификация к новой заявке "Сертификация"
                          //$ki = Array('curtbl'=>56,'curidx'=>$row3['f_id'],'f_specid'=>$ar[2]); //sdid 2224 2024-03-24
                          //sdid 2224 2024-03-24
                          if($ar[2] != $curidx) 
                            {
                            $ki = Array('curtbl'=>56,'curidx'=>$row3['f_id'],'f_specid'=>$ar[2]);
                            editRowTbl($ki);
                            } 
                          //~sdid 2224 2024-03-24
                          }
                        else //Если не нашли, создаем новую запись в таблице Сертификация и привязываем ее к новой заявке "Сертификация"
                          {
                          $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                          $typez = 0;
                          if(isset($ik['typez'])) {$typez = $ik['typez'];}
                          elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                          if($typez==1)
                          {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                         
                          $cst = 0;
                          if($iskp==1){$cst = 23;}
                          $ki = Array('curtbl'=>56,'f_specid'=>$ar[2],'f_status'=>$cst);
                          $ar = json_decode(addRowTbl($ki), true);
                          if($ar[0]=="true")
                            {
                            //sdid 2224 2024-03-24
                            if($row['f_uvedcrt']==1)
                              {
                            //~sdid 2224 2024-03-24
                              if($menomes==0)
                                {
                                $subject = "Для ".$iskps." создана запись о сертификации";
                                $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>";
                                $sbsh = 0;
                                if($menomes==0) 
                                  { 
                                  $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar[2], 151, 7, 13); 
                                  if($sbsh == 0) 
                                    { 
                                    mSendMail($_SESSION['loginid'],9,0,$subject,$postbody,"",56,$ar[2]);
                                    }
                                  }                     
                                }
                            //sdid 2224 2024-03-24
                              }
                            //~sdid 2224 2024-03-24
                            }
                          }
                        }
                      else
                        {
                        $wdopmes = 1;
                        //$anns = $ansn."Ошибка добавления заявки - Сертификация";
                        $ans = $ans."Ошибка добавления заявки - Сертификация";
                        }
                      }
                    }
                  } //~ Если поменяли на "договор с подвидом "Клиентский" 
                //sdid 2224 --- 2024-03-04
                elseif((substr($strcertdog,0,1)==2) && (substr($ostrcertdog,0,1)==2)) // Если поменяли на "Договор с подвидом "Клиентский", и старый вариант БЫЛ "Договор с подвидом "Клиентский"
                  {
                  $strcertdogparm = substr($strcertdog,1);
                  //Ищем связанные с текущей спецификацией заявки сертификации, наличие связанных операций и наличие связанных записей пользователей в prjparts 
                  $ostrcertdogparm = substr($ostrcertdog,1);
                  $sql = "select s.f_id,
                            (select count(*) cnt from veda_spec_invoices where f_parenttype=2 and f_specid=s.f_id) sicnt,
                            (select count(*) from ".DBPref."prjparts p where p.f_typeobj=1 and p.f_idobj=s.f_id) cntpp 
                          from veda_specs s, veda_dogs d, veda_dogs d2
                          where d.f_id=s.f_dogid and s.f_subtype=2 and d.f_dogtype=1 
                            and d.f_contrid=d2.f_contrid and d.f_orgid=d2.f_orgid
                            and s.f_parentspecid=:curidx and d2.f_id=:curdogid";
                 
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curidx',$curidx,PDO::PARAM_INT);
                  $res2->bindParam(':curdogid',$ostrcertdogparm,PDO::PARAM_INT);
                  $res2->execute();
                  $derr2=0;
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $derr2=0;
                    if($row2['sicnt']>0) // если нашли связанные операции, выдаем ошибку
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке есть связанные операции"; $derr2++; continue;
                      }
                    elseif($row2['cntpp']>0) // если нашли связанные данные о менеджерах/бухгалтерах, выдаем ошибку
                      {
                      $kr = -3; $errmsg = $errmsg."Ошибка удаления связанной заявки Сертификации (ID=".$row2['f_id'].") По заявке внесены данные о менеджерах\\бухгалтерах"; $derr2++; continue;
                      }
                    if($derr2==0)
                      {
                      //sdid 2224 2024-03-10
                      // Для того чтобы появилась возможность удалить старую Заявку, найдем Сертификации и обнулим связь со старой заявкой
                      $fspecid = $row2['f_id'];
                      $sql = "select f_id from veda_certificates where f_specid=:fspecid";
                      $res3 = $dbh->prepare($sql);
                      $res3->bindParam(':fspecid',$fspecid,PDO::PARAM_INT);
                      $res3->execute();
                      while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                        {
                        $ki_cert = Array('curtbl'=>56,'curidx'=>$row3['f_id'],'f_specid'=>999999); 
                        editRowTbl($ki_cert);
                        }

                      // Удаляем старую заявку Сертификации
                      $derr3=0;
                      $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); 
                      $rvd = json_decode(delRowTbl($ki),true);
                      if($rvd[0]==false) { $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[1]; $derr3++; } //continue; }
                      elseif(strcmp($rvd[3],"00000")!=0)
                        {
                        $kr = -3; $errmsg = $errmsg."Ошибка удаления заявки Сертификации (ID=".$row2['f_id']."): \n".$rvd[5]; $derr3++; //continue;
                        }
                      if($derr3==0) // Если успешно удалили старую заявку Сертификации
                        {
                        // Создаем новую заявку с новым договором, и привязываем к ней записи о сертификации
                        //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                        $obj = new ArrayObject($ik);
                        $ki1 = $obj->getArrayCopy();
                        $ki1['curtbl'] = 15;
                        if(isset($ki1['f_parentspecid'])) {$ki1['f_parentspecid']=$curidx;}
                        elseif(isset($ki1['parentspecid'])) {$ki1['parentspecid']=$curidx;}
                        else{$ki1['f_parentspecid']=$curidx;}
                        if(isset($ki1['f_dogid'])) {$ki1['f_dogid']=$strcertdogparm;}
                        elseif(isset($ki1['dogid'])) {$ki1['dogid']=$strcertdogparm;}
                        else{$ki1['f_dogid']=$strcertdogparm;}
                        if(isset($ki1['f_subtype'])) {$ki1['f_subtype']=2;}
                        elseif(isset($ki1['subtype'])) {$ki1['subtype']=2;}
                        else{$ki1['f_subtype']=2;}
                        if(isset($ki1['f_dt'])) {$ki1['f_dt']=date("Y-m-d");}
                        elseif(isset($ki1['dt'])) {$ki1['dt']=date("Y-m-d");}
                        else{$ki1['f_dt']=date("Y-m-d");}
                        if(isset($ki1['f_typez'])) {$ki1['f_typez']=3;}
                        elseif(isset($ki1['typez'])) {$ki1['typez']=3;}
                        else{$ki1['f_typez']=3;}
                        if(isset($ki1['f_kod1cb'])) {$ki1['f_kod1cb']="";}
                        elseif(isset($ki1['kod1cb'])) {$ki1['kod1cb']="";}
                        if(isset($ki1['f_kod1cp'])) {$ki1['f_kod1cp']="";}
                        elseif(isset($ki1['kod1cp'])) {$ki1['kod1cp']="";}
                        if(isset($ki1['f_wens'])) {$ki1['f_wens']=false;}
                        elseif(isset($ki1['wens'])) {$ki1['wens']=false;}
                        if(isset($ki1['f_wsert'])) {$ki1['f_wsert']=true;}
                        elseif(isset($ki1['wsert'])) {$ki1['wsert']=true;}
                        if(isset($ki1['f_wpost'])) {$ki1['f_wpost']=false;}
                        elseif(isset($ki1['wpost'])) {$ki1['wpost']=false;}
                        if(isset($ki1['f_sbor'])) {$ki1['f_sbor']=false;}
                        elseif(isset($ki1['sbor'])) {$ki1['sbor']=false;}
                        if(isset($ki1['f_wto'])) {$ki1['f_wto']=false;}
                        elseif(isset($ki1['wto'])) {$ki1['wto']=false;}
                        if(isset($ki1['f_uvedins'])) {$ki1['f_uvedins']=false;}
                        elseif(isset($ki1['uvedins'])) {$ki1['uvedins']=false;}
                        if(isset($ki1['f_uvedto'])) {$ki1['f_uvedto']=false;}
                        elseif(isset($ki1['uvedto'])) {$ki1['uvedto']=false;}
                        if(isset($ki1['f_uvedcrt'])) {$ki1['f_uvedcrt']=true;} // !!!!!!
                        elseif(isset($ki1['uvedcrt'])) {$ki1['uvedcrt']=true;} // !!!!!!
                        if(isset($ki1['f_postid'])) {$ki1['f_postid']=false;}
                        elseif(isset($ki1['postid'])) {$ki1['postid']=false;}
                        if(isset($ki1['f_todog'])) {$ki1['f_todog']=10;}
                        elseif(isset($ki1['todog'])) {$ki1['todog']=10;}
                        else{$ki1['f_todog']=10;}
                        if(isset($ki1['f_certdog'])) {$ki1['f_certdog']=0;} //sdid 2224 2024-03-24
                        elseif(isset($ki1['certdog'])) {$ki1['certdog']=0;} //sdid 2224 2024-03-24
                        else{$ki1['f_certdog']=0;}
                        if(isset($ki1['id'])) {unset($ki1['id']);}
                        if(isset($ki1['curidx'])) {unset($ki1['curidx']);}
                        if(isset($ki1['f_num'])) {unset($ki1['f_num']);}
                        if(isset($ki1['num'])) {unset($ki1['num']);}
                        if(isset($ki1['f_tpdog'])) {unset($ki1['f_tpdog']);}
                        if(isset($ki1['tpdog'])) {unset($ki1['tpdog']);}
                        $tar = addRowTbl($ki1);
                        //echo "tar = | ".$tar." | ";
                        $ar = json_decode($tar, true);
                        //$ar = json_decode(addRowTbl($ki1), true);
                        if($ar[0]=="true")
                          {
                          $wdopmes = 1;
                          //$ansn = $ansn."Добавили связанную заявку - Сертификация";
                          $ans = $ans."Добавили связанную заявку - Сертификация";
                          // Ищем связанную со старой заявкой запись в таблице Сертификация
                          $derr=0;
                          $ospecid=999999;
                          $sql = "select f_id from veda_certificates where f_specid=:ospecid";
                          $res3 = $dbh->prepare($sql);
                          $res3->bindParam(':ospecid',$ospecid,PDO::PARAM_INT);
                          $res3->execute();
                          if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                            {
                            // Если нашли, привязываем запись в таблице Сертификация к новой заявке "Сертификация"
                            $ki = Array('curtbl'=>56,'curidx'=>$row3['f_id'],'f_specid'=>$ar[2]); 
                            editRowTbl($ki);
                            }
                          else // Если не нашли, создаем новую запись в таблице Сертификация
                            {
                            $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                            $typez = 0;
                            if(isset($ik['typez'])) {$typez = $ik['typez'];}
                            elseif(isset($ik['f_typez'])) {$typez = $ik['typez'];}
                            if($typez==1)
                            {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                           
                            $cst = 0;
                            if($iskp==1){$cst = 23;}
                            $ki = Array('curtbl'=>56,'f_specid'=>$ar[2],'f_status'=>$cst);
                            $ar_crt = json_decode(addRowTbl($ki), true);
                            if($ar_crt[0]=="true")
                              {
                              //sdid 2224 2024-03-24
                              if($row['f_uvedcrt']==1)
                                {
                              //~sdid 2224 2024-03-24
                                if($menomes==0)
                                  {
                                  $subject = "Для ".$iskps." создана запись о сертификации";
                                  $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar_crt[2]."\">Сертификации</a>";
                                  $sbsh = 0;
                                  if($menomes==0) 
                                    { 
                                    $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar_crt[2], 151, 7, 13); 
                                    if($sbsh == 0) 
                                      { 
                                      mSendMail($_SESSION['loginid'],9,0,$subject,$postbody,"",56,$ar_crt[2]);
                                      }
                                    }                     
                                  }
                                //sdid 2224 2024-03-24
                                }
                                //~sdid 2224 2024-03-24
                              }
                            }
                          }
                        } //////////////////////
                      else // Если удаление старой Заявки неуспешно, возвращаем значения f_specid в сертификациях
                        {
                        $ospecid=999999;
                        $sql = "select f_id from veda_certificates where f_specid=:ospecid";
                        $res3 = $dbh->prepare($sql);
                        $res3->bindParam(':ospecid',$ospecid,PDO::PARAM_INT);
                        $res3->execute();
                        if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>56,'curidx'=>$row3['f_id'],'f_specid'=>$row2['f_id']); 
                          editRowTbl($ki);
                          }
                        }
                      //~sdid 2224 2024-03-10
                      }
                    } //~ while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  }
                //~sdid 2224 --- 2024-03-04
                } //~ Если изменилось поле "Сертификация"
              //sdid 3131
              if(isset($ik['f_perpravdt']))
                {
                if(isset($row1['isp1dtppsdt'])){if($row1['isp1dtppsdt']>0) {unset($ik['f_perpravdt']);}}
                }
              //~sdid 3131
              }
            //~sdid 2224
            }
          elseif(strcmp($tblname,DBPref."spec_invoices")==0)//перед обновлением операции
            {
            //sdid 2377
            //$sql  = "select f_status,f_uved,f_isvozm from ".$tblname." where f_id=:curidx";
            $sql  = "select f_parenttype,f_status,f_uved,f_isvozm,f_correctid from ".$tblname." where f_id=:curidx";
            //~sdid 2377
            $res1 = $dbh->prepare($sql);
            $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
            $res1->execute();
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $row1['f_status'];
              $ouved   = $row1['f_uved'];
              $oisvozm = $row1['f_isvozm'];
              //sdid 2377
              if($row1['f_parenttype']==2)
                {
                $ocorrerctid = $row1['f_correctid'];
                if(isset($ik['correctid']))
                  {
                  if(($ik['correctid'] > 0)) // Проверим, не выбрана ли закрытая корректировка
                    {
                    $ncorrectid = $ik['correctid'];
                    if(strcmp((string)$ik['correctid'],(string)$ocorrerctid)!=0)
                      {
                      $sql  = "select f_status from veda_corrects where f_id=:correctid";
                      $res2 = $dbh->prepare($sql);
                      $res2->bindParam(':correctid',$ncorrectid,PDO::PARAM_INT);
                      $res2->execute();
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row2['f_status']==3)
                          {
                          $kr = -3; 
                          $errmsg = $errmsg."\nВыбрана закрытая корректировка. Запрещено включать новые операции в закрытые корректировки."; 
                          }
                        }
                      }
                    }
                  }
                }
              else
                {
                $ocorrerctid = $row1['f_correctid'];
                if(isset($ik['correctid']))
                  {
                  if(strcmp((string)$ik['correctid'],(string)$ocorrerctid)!=0)
                    {$ik['correctid'] = $ocorrerctid;}
                  }
                }
              //~sdid 2377
              }
            }
          //sdid - 353
          elseif(strcmp($tblname,DBPref."akts")==0)//перед обновлением акта
            {
            $old_akt_nds    = 0;
            $old_akt_ndssum = 0;
//            $akts_query = "SELECT f_status,f_operid FROM $tblname WHERE f_id=$curidx"; // sdid 1865
            $akts_query = "SELECT f_status,f_operid,f_nds,f_ndssum FROM $tblname WHERE f_id=$curidx"; // sdid 1865
            $akts_response = $dbh->query($akts_query);
            if($akts_row = $akts_response->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $akts_row['f_status'];
              $old_operation_id = $akts_row['f_operid'];
              $old_akt_nds = $akts_row['f_nds']; // sdid 1865
              $old_akt_ndssum = $akts_row['f_ndssum']; // sdid 1865
              }
            }
          elseif(strcmp($tblname,DBPref."akts_details_opers")==0)//акты детализация
            {
            $akts_query = "SELECT f_operid FROM $tblname WHERE f_id=$curidx";
            $akts_response = $dbh->query($akts_query);
            if($akts_row = $akts_response->fetch(PDO::FETCH_ASSOC))
              {
              $old_operation_id = $akts_row['f_operid'];
              }
            }
          elseif(strcmp($tblname,DBPref."certificates")==0)//обрабатываем обновление информации о сертификатах
            {
            $sql1 = "select f_docdt,f_status from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $row1['f_status'];
              $odocdt  = $row1['f_docdt'];
              }
            }
          //~sdid - 353
          //sdid - 819
          elseif(strcmp($tblname,DBPref."schets")==0)//счета
            {
            $sql1 = "select f_status,f_sum from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = $row1['f_status'];
              $osum    = $row1['f_sum'];
              }
            }
          //~sdid - 819
          elseif(strcmp($tblname,DBPref."remote_clnt")==0)//перед ообновлением ЛКК. Клиенты
            {
            $sql1 = "select f_bpassword,f_isuse from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $obpassword = $row1['f_bpassword'];
              $oisuse     = $row1['f_isuse'];
              }
            }
          //sdid3342
          elseif(strcmp($tblname,DBPref."lns")==0)
            {
            $sql1 = "select f_status from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $ostatus = (int)$row1['f_status'];
              $lnstatus = -1;
              if(isset($ik['status'])){$lnstatus = (int)$ik['status'];}
              elseif(isset($ik['f_status'])){$lnstatus = (int)$ik['f_status'];}
              if(($lnstatus!=$ostatus)&&($lnstatus==2))
                {$ik['f_acceptdt'] = date('d.m.Y');}
              }
            }
          //~sdid3342
          elseif(
                 (strcmp($tblname,DBPref."shipments")==0)//||//обрабатываем обновление информации о поставке//sdid3342
                 //(strcmp($tblname,DBPref."ensures")==0)||//обрабатываем обновление информации о страховке
                 //(strcmp($tblname,DBPref."certificates")==0)||//обрабатываем обновление информации о сертификатах
                 //(strcmp($tblname,DBPref."lns")==0)//||//обновляем запись в финансировании //sdid3342
                 //(strcmp($tblname,DBPref."schets")==0)//||//счета
                 // (strcmp($tblname,DBPref."akts")==0)//||//акты
                 // (strcmp($tblname,DBPref."spec_invoices")==0)//операции
                )
            {
            $sql1 = "select f_status from ".$tblname." where f_id=".$curidx;
            $res1 = $dbh->query($sql1);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$ostatus = $row1['f_status'];}
            }
          //echo $sql."<br>";
          $nstatus = $ostatus;
          $isspecbuh = 0;
          $sql1 = "select f_specbuh from ".DBPref."users where f_id=".$_SESSION['loginid'];
          //echo $sql1."<br>";
          $res1 = $dbh->query($sql1);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {$isspecbuh = $row1['f_specbuh'];}

          $sql = "update ".$tblname." set ".$strvalues." where f_id=".$curidx;
          if(
             ((strcmp($tblname,DBPref."spec_invoices")==0)&&($ostatus==7)&&($isspecbuh==0))||
             ((strcmp($tblname,DBPref."specs")==0)&&($ostatus==6)&&($isspecbuh==0))
            )
            {$kr = -2;}
          if($kr==0)
            {$kr = $dbh->exec($sql);}
          //!!!!!!Действия после обновления
          if($kr>0)
            {
            $ans = "\",\"".$kr;
            //$ans = "\",\"".$kr."|".$tblname;
            if(strcmp($tblname,DBPref."specs")==0)//после обновления обрабатываем спецификации
              {
              //$sql = "select s.*,cl.f_ensdogid,cl.f_enstovar,cl.f_enscustom,cl.f_enslogist,cl.f_enslostprof ".
              //       "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl ".
              //       "where f_id=".$curidx;
              $sql = "select * ".
                     "from ".DBPref."specs ".
                     "where f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                //sdid - 277 - обновляем дополнительные спецификации
                $sql1 = "select f_id
                from ".$tblname." where f_is_extra_spec=1 and f_parentspecid=".$curidx;
                $res1 = $dbh->query($sql1);
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($ostatus!=$row['f_status'])||($obuhid!=$row['f_buhid'])||
                      ($ooperid!=$row['f_operid'])||($odttoclnt!=$row['f_dttoclnt'])||
                      ($dtclose!=$row['f_dtclose'])||($levelrp!=$row['f_levelrp'])
                      ||($odtsenddoctoclnt!=$row['f_dtsenddoctoclnt']))//sdid2390
                    {
                    $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id']);
                    if($ostatus!=$row['f_status']){$ki = array_merge($ki,array('f_status'=>$row['f_status']));}
                    //if($obuhid!=$row['f_buhid']){$ki = array_merge($ki,array('f_buhid'=>$row['f_buhid']));}
                    $ki = array_merge($ki,array('f_buhid'=>$row['f_buhid']));
                    if($ooperid!=$row['f_operid']){$ki = array_merge($ki,array('f_operid'=>$row['f_operid']));}
                    if($odttoclnt!=$row['f_dttoclnt']){$ki = array_merge($ki,array('f_dttoclnt'=>$row['f_dttoclnt']));}
                    if($dtclose!=$row['f_dtclose']){$ki = array_merge($ki,array('f_dtclose'=>$row['f_dtclose']));}
                    if($levelrp!=$row['f_levelrp']){$ki = array_merge($ki,array('f_levelrp'=>$row['f_levelrp']));}
                    if($odtsenddoctoclnt!=$row['f_dtsenddoctoclnt']){$ki = array_merge($ki,array('f_dtsenddoctoclnt'=>$row['f_dtsenddoctoclnt']));}//sdid2390
                    editRowTbl($ki);
                    }
                  }
                //~sdid - 277
                //sdid 2084 tz2 2 
                // 9. Копировать Менеджер и Бухгалтер из основной при сохранении. 
                //    Т.е. при сохранении любой спецификации - основной (ВА) или связанной (ВА-ТП) - копировать Мен и Бух из основной в ВА-ТП.
                // сначала ищем дочерние спец. ВА-ТП, обновляем в них менеджера и бухгалтера
                if($row['f_subtype']!=3)
                  {
                  //$sql1 = "select s.f_id, s.f_operid, s.f_buhid //sdid 2298
                  $sql1 = "select s.f_id, s.f_operid #sdid 2298
                             from veda_specs s, veda_dogs d 
                            where s.f_subtype=3 
                              and d.f_id=s.f_dogid
                              and d.f_orgid=(select dd.f_orgid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$curidx)
                              and d.f_contrid=(select dd.f_contrid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$curidx)
                              and d.f_subtype=3
                              and s.f_parentspecid=".$curidx;
                  $res1 = $dbh->query($sql1);
                  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    //if(($row1['f_buhid'] != $row['f_buhid']) || ($row1['f_operid'] != $row['f_operid']))  //sdid 2298
                    if($row1['f_operid'] != $row['f_operid']) //sdid 2298
                      {
                      $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id']);
                      //if($row1['f_buhid']  != $row['f_buhid'])  {$ki = array_merge($ki,array('f_buhid'=>$row['f_buhid']));}  //sdid 2298
                      if($row1['f_operid'] != $row['f_operid']) {$ki = array_merge($ki,array('f_operid'=>$row['f_operid']));}
                      editRowTbl($ki);
                      }
                    }
                  }
                // Если спецификация ВА-ТП и есть основная, обновляем в текущей менеджера и бухгалтера, вытянув актуальных из родительской 
                elseif(($row['f_subtype']==3) && ($row['f_parentspecid']>0))
                  {
                  //$sql1 = "select s.f_id, s.f_operid, s.f_buhid //sdid 2298 
                  $sql1 = "select s.f_id, s.f_operid #sdid 2298
                             from veda_specs s, veda_dogs d
                            where d.f_id=s.f_dogid
                              and d.f_orgid=(select dd.f_orgid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$curidx)
                              and d.f_contrid=(select dd.f_contrid from veda_dogs dd, veda_specs ss where dd.f_id=ss.f_dogid and ss.f_id=$curidx)
                              and s.f_id=".$row['f_parentspecid'];
                  $res1 = $dbh->query($sql1);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    //if(($row1['f_buhid'] != $row['f_buhid']) || ($row1['f_operid'] != $row['f_operid'])) //sdid 2298
                    if($row1['f_operid'] != $row['f_operid']) //sdid 2298
                      {
                      $ki = Array('curtbl'=>15,'curidx'=>$curidx);
                      //if($row['f_buhid']  != $row1['f_buhid'])  {$ki = array_merge($ki,array('f_buhid'=>$row1['f_buhid']));}  //sdid 2298
                      if($row['f_operid'] != $row1['f_operid']) {$ki = array_merge($ki,array('f_operid'=>$row1['f_operid']));}
                      editRowTbl($ki);
                      }
                    }
                  }
                //~sdid 2084 tz2 2 
                //echo $row['f_typez']."|";
                $iskp = 0;$iskps = "Спецификации";$iskpid=15;$kpt=2;
                if($row['f_typez']==1)
                  {$iskp = 1;$iskps = "КП";$iskpid=47;$kpt=1;}
                //echo $iskp."|";
                //echo $ouved."|".$row['f_uved']."|".$owens."|";
                if(($oreqoto==0)&&($row['f_reqoto']==1))
                  {mSendMail($_SESSION['loginid'],22,0,"По ".$iskps." поступил запрос","Для <a href=\"".$redirect_uri."?pgid=174&obid=".$curidx."\">".$iskps."</a> поступил запрос","",174,$row['f_id']);}
                if(($oreqol==0)&&($row['f_reqol']==1))
                  {mSendMail($_SESSION['loginid'],2,0,"По ".$iskps." поступил запрос","Для <a href=\"".$redirect_uri."?pgid=174&obid=".$curidx."\">".$iskps."</a> поступил запрос","",174,$row['f_id']);}
                if(($oreqoss==0)&&($row['f_reqoss']==1))
                  {mSendMail($_SESSION['loginid'],9,0,"По ".$iskps." поступил запрос","Для <a href=\"".$redirect_uri."?pgid=174&obid=".$curidx."\">".$iskps."</a> поступил запрос","",174,$row['f_id']);}
                if(($ouvedcrt!=$row['f_uvedcrt'])&&($row['f_uvedcrt']==1))//изменился признак уведомления сертификации
                  {
                  if(($owsert==$row['f_wsert'])&&($owsert==1))
                    {$sql1 = "select f_id from ".DBPref."certificates where f_specid=".$curidx;
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($menomes==0)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о сертификации";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$row1['f_id']."\">Сертификации</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $row1['f_id'], 151, 7, 13); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],9,0,$subject,$postbody,"",56,$row1['f_id']);
                            }
                          }                     
                        //if($menomes==0)
                        // {mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$row1['f_id']."\">Сертификации</a>","",56,$row1['f_id']);}
                        //~sdid 1546
                        //mSendMail($_SESSION['loginid'], 9, 0, "Для " . $iskps . " создана запись о сертификации", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=56&obid=" . $row1['f_id'] . "\">Сертификации</a>", "", 56, $row1['f_id']);
                        }
                      }
                    }
                  }
                //sdid 1780
                elseif(strcmp($row['f_dttoclnt'], $odttoclnt)!=0)
                  {  
                  $ki = Array('curtbl'=>15,'curidx'=>$row['f_id'],'f_dtuueol'=>$row['f_dttoclnt']);
                  editRowTbl($ki);
                  }
                //~sdid 1780
                if(($ouvedins!=$row['f_uvedins'])&&($row['f_uvedins']==1))//изменился признак уведомления страх
                  {if(($owens==$row['f_wens'])&&($owens==1))
                    {$sql1 = "select f_id from ".DBPref."ensures where f_objtype=".$kpt." and f_objid=".$curidx;
                    //echo $sql1."|";
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($menomes==0)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о страховании";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$row1['f_id']."\">Страховании</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 54, $row1['f_id'], 151, 4, 11); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],8,0,$subject,$postbody,"",54,$row1['f_id']);
                            }
                          }                     
                         //if($menomes==0)
                         // {mSendMail($_SESSION['loginid'],8,0,"Для ".$iskps." создана запись о страховании","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$row1['f_id']."\">Страховании</a>","",54,$row1['f_id']);}
                         //~sdid 1546
                         //mSendMail($_SESSION['loginid'], 8, 0, "Для " . $iskps . " создана запись о страховании", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=54&obid=" . $row1['f_id'] . "\">Страховании</a>", "", 54, $row1['f_id']);
                        }
                      }}
                  }
                //sdid 2084 tz2 2
                /*
                if(($ouvedto!=$row['f_uvedto'])&&($row['f_uvedto']==1))//изменился признак уведомления ОТО
                  {
                  if(($owto==$row['f_wto'])&&($owto==1))
                    {
                    $sql1 = "select f_id from ".DBPref."dt where f_specid=".$curidx;
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      //sdid2058
                      if(($menomes==0)&&($row['f_tpdog']==0))
                      //~sdid2058
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись информации о ДТ";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">информации о ТО</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $row1['f_id'], 151, 1, 12); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$row1['f_id']);
                            }
                          }  
                        //if($menomes==0)
                        //  {mSendMail($_SESSION['loginid'],22,0,"Для ".$iskps." создана запись информации о ДТ","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">информации о ТО</a>","",61,$row1['f_id']);}
                        //~sdid 1546
                        //mSendMail($_SESSION['loginid'], 22, 0, "Для " . $iskps . " создана запись информации о ДТ", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=61&obid=" . $row1['f_id'] . "\">информации о ТО</a>", "", 61, $row1['f_id']);
                        }
                      }
                    }
                  }
                */  
                if(($ouvedto!=$row['f_uvedto'])&&($row['f_uvedto']==1))//изменился признак уведомления ОТО
                  {
                  // Признак f_wto больше не используется, вместо него ориентируемся по полю f_todog 
                  // Если 1-й знак в f_todog==2, то ищем в veda_dt запись, связанную с текущей спецификацией, если ==3, то связанную с договором ТП по текущей спецификации
                  $tpto = 0;
                  $sql1 = "";
                  if(strlen($row['f_todog'])>1) //отсекаем 0 - Не задано
                    {
                    if(substr($row['f_todog'],0,1)==2) //если ТО привязано к текущей спецификации
                      {
                      $tpto = 2;
                      $sql1 = "select f_id from ".DBPref."dt where f_specid=".$curidx;
                      } 
                    elseif(substr($row['f_todog'],0,1)==3) //если ТО привязано к договору ТП по текущей спецификации
                      {
                      $tpto = 3;
                      $sql1 = "select dt.f_id 
                                 from ".DBPref."dt dt, ".DBPref."specs s, ".DBPref."dogs d 
                                where s.f_parentspecid=".$curidx." 
                                  and s.f_subtype=3 and dt.f_specid=s.f_id and d.f_id=s.f_dogid ";
                      } 
                    }
                  if($tpto==2 || $tpto==3)
                    {
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($menomes==0)
                        {
                        $subject = "Для ".$iskps." создана запись информации о ДТ";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">информации о ТО</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $row1['f_id'], 151, 1, 12); 
                          if($sbsh==0) {mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$row1['f_id']);}
                          }  
                        }
                      }
                    }
                    // sdid 1781
                    $sql_children = "SELECT f_id FROM ".DBPref."specs WHERE f_subtype = 3 AND f_parentspecid = ".$curidx;
                    $conn_children = $dbh->query($sql_children);
                    while ($row_child = $conn_children->fetch(PDO::FETCH_ASSOC))
                      {
                      $child_update = [
                                      "curtbl" => 15,
                                      "curidx" => $row_child['f_id'],
                                      "f_uvedto" => 1
                                      ];
                      editRowTbl($child_update);
                      }
                    // ~ sdid 1781
                  }
                //~sdid 2084 tz2 2 
                if(($ouved!=$row['f_uved'])&&($row['f_uved']==1))//изменился признак уведомления ОЛ
                  {if(($owpost==$row['f_wpost'])&&($owpost==1)&&($opostid>0))
                    {if($menomes==0)
                      {
                      //sdid 1546
                      $subject = "Для ".$iskps." создана запись о доставке";
                      $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$opostid."\">Доставке</a>";
                      $sbsh = 0;
                      if($menomes==0) 
                        { 
                        $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 102, $opostid, 151, 2, 14); 
                        if($sbsh == 0) 
                          { 
                          mSendMail($_SESSION['loginid'],2,0,$subject,$postbody,"",102,$opostid);
                          }
                        }                     
                      //if($menomes==0)
                      // {mSendMail($_SESSION['loginid'],2,0,"Для ".$iskps." создана запись о доставке","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$opostid."\">Доставке</a>","",102,$opostid);}
                      //~sdid 1546
                      //mSendMail($_SESSION['loginid'], 2, 0, "Для " . $iskps . " создана запись о доставке", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=102&obid=" . $opostid . "\">Доставке</a>", "", 102, $opostid);
                      }
                    }
                  }
                $nstatus = $row['f_status'];
                if($nstatus != $ostatus) 
                  { //изменился статус спецификации
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=6 and f_num=".$nstatus;
                  //sdid - 164
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=6 and f_num=".$nstatus;
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=6 and f_num=".$nstatus;
                  //~sdid 703
                  //~sdid - 164
                  //$ans=$ans.$sql;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    //sdid - 164
                    //sdid 703
                    // sdid 1566
                    if ($nstatus != 1)
                      {
                      // ~sdid 1566
                      if($row1['f_dopprstr1']>0)
                        {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 3, $curidx);}
                      else
                        {$subject = "По спецификации/КП изменен статус на ".$row1['f_name'];}
                      //~sdid 703
                      $postbody = "По <a href=\"".$redirect_uri."?pgid=15&obid=".$curidx."\">спецификации/КП</a> изменен статус на \"".$row1['f_name']."\"";
                      $buh_postbody = $postbody . " " . date("D M d, Y G:i");
                      
                      $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 3, $curidx);
                      $buh_postbody = $buh_postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 3, $curidx);
                      //~sdid - 164
                      if($row1['f_uslint']==1) 
                        {
                        if(strcmp($row1['f_uslstr'],"2")==0) 
                          {
                            $mgrp = $row1['f_namedop'];
                            $musr = 0;
                          } 
                        else 
                          {
                            $mgrp = 0;
                            $musr = $row1['f_namedop'];
                          }
                        if ($menomes==0) 
                          {
                          //sdid - 164
                          mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",15,$curidx);
                          //~sdid - 164
                          }
                        }
                      // sdid 1566
                      }
                    // ~ sdid 1566
                    // 2) основному и замещающему бухгалтерам при смене статуса спецификации/заявки
                    //$buh_sql = "SELECT f_idsubj FROM ".DBPref."prjparts WHERE f_typerole=2 AND f_idobj=".$curidx;
                    //$buh_query = $dbh->query($buh_sql);
                    //while ($buh_row = $buh_query->fetch(PDO::FETCH_ASSOC)) {
                    //    mSendMail($_SESSION['loginid'],0,$buh_row['f_idsubj'],"По спецификации/КП изменен статус на ".$row1['f_name'],
                    //        "По <a href=\"".$redirect_uri."?pgid=15&obid=".$curidx."\">спецификации/КП</a> изменен статус на \"".$row1['f_name']."\"","",15,$curidx);
                    //}
                    if((isset($_SESSION['loginid']))&&(isset($subject))&&(isset($buh_postbody)))
                      {
                    mSendMail(
                      $_SESSION['loginid'],
                      14,
                      0,
                      //sdid - 164
                      $subject,
                      $buh_postbody,
                      //~sdid - 164
                      "",
                      15,
                      $curidx
                    );}
                    }
                  if($nstatus==6) 
                    {//закрыли
                    //sdid - 254
                    //if(isset($row['f_dtclose'])){
//sdid 1028
/*                      if(strcmp($row['f_dtclose'],"0000-00-00")==0)
                        {$ki = Array('curtbl'=>15,'curidx'=>$row['f_id'],'f_dtclose'=>date('Y-m-d'));
                        editRowTbl($ki);}
*/
                      $specdtclose = $row['f_dtclose'];
                      if(strcmp($row['f_dtclose'],"0000-00-00")==0)
                        {
                          $ki = Array('curtbl'=>15,'curidx'=>$row['f_id'],'f_dtclose'=>date('Y-m-d'));
                          editRowTbl($ki);
                          $specdtclose = date('Y-m-d');
                        }
//~sdid 1028
                    //}
                    //~sdid - 254
                    //$sql = "select f_id from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_id']; sdid 2023
                    $sql = "select f_id, f_outbuhperiod from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_id']; // sdid 2023
                    //echo $sql."|";
                    $res1 = $dbh->query($sql);
$spec_invoices_0 = []; // sdid 2023
                    $spec_invoices_1 = []; // sdid 2023
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>35,'curidx'=>$row1['f_id'],'f_status'=>7);
                      editRowTbl($ki);
                      // sdid 2023
                      if($row1['f_outbuhperiod'] == 0)
                        {
                        $spec_invoices_0[] = $row1['f_id'];
                        }
                      else
                        {
                        $spec_invoices_1[] = $row1['f_id'];
                        }
                      // ~ sdid 2023
                      }
                      // sdid 2023
                      $corrects_class       = new Corrects("corrects", $dbh);
                      $corrects_opers_class = new CorrectsOpers("corrects_opers",$dbh);
                      $corr_is_first        = false;
                      //$sql_corr    = "SELECT MAX(f_id) id, f_status FROM ".DBPref."corrects WHERE f_specid=".$curidx; sdid 2109
                      $sql_corr    = "SELECT f_id id, f_status FROM ".DBPref."corrects WHERE f_id=(SELECT MAX(f_id) FROM ".DBPref."corrects WHERE f_specid=$curidx)"; // sdid 2109
                      $conn_corr   = $dbh->query($sql_corr);
                      $correct_id  = -1;
                      if($row_corr = $conn_corr->fetch(PDO::FETCH_ASSOC))
                        {
                        if(isset($row_corr['id']) && isset($row_corr['f_status']))
                          {
                          if($row_corr['f_status'] != 3)
                            {
                            $correct_id = $row_corr['id'];
                            $sql_corr_opers = "SELECT f_id FROM ".DBPref."corrects_opers WHERE f_correctid=".$correct_id;
                            $conn_corr_opers = $dbh->query($sql_corr_opers);
                            while ($row_corr_opers = $conn_corr_opers->fetch(PDO::FETCH_ASSOC))
                              {
                              $remove_corr_oper = ['curtbl'=>291, 'curidx'=>$row_corr_opers['f_id'], 'oper' => 'del'];
                              $corrects_opers_class->delRowTbl($dbh, $remove_corr_oper, "");
                              }
                            }
                          else
                            {
                            $add_correct = [
                                'curtbl' => 290,
                                'f_specid' => $curidx,
                                'oper' => 'add'
                                ];
                            // создается f_num=f_num+1 корректировка
                            $add_result = $corrects_class->addRowTbl($dbh, $add_correct, "");
                            if($add_result[0])
                              {
                              $correct_id = $add_result[2];
                              }
                            }
                          }
                          // sdid 2109
//                        else
//                          {
//                          $add_correct = [
//                              'curtbl' => 290,
//                              'f_specid' => $curidx,
//                              'oper' => 'add'
//                              ];
//                          // создается f_num=0 корректировка
//                          $add_result = $corrects_class->addRowTbl($dbh, $add_correct, "");
//                          if($add_result[0])
//                            {
//                            $correct_id = $add_result[2];
//                            $corr_is_first = true;
//                            }
//                          }
                        // ~ sdid 2109
                        }
                      // sdid 2109
                        else
                          {
                          if(isset($ik['f_dtclose']))
                            {
                            $add_correct = [
                                'curtbl' => 290,
                                'f_specid' => $curidx,
                                'f_status' => 3,
                                'f_dtclose' => $ik['f_dtclose'],
                                'oper' => 'add'
                                ];
                            // создается f_num=0 корректировка
                            $add_result = $corrects_class->addRowTbl($dbh, $add_correct, "");
                            if($add_result[0])
                              {
                              $correct_id = $add_result[2];
                              $corr_is_first = true;
                              }
                            }
                          }
                        // ~ sdid 2109
                      if($correct_id > 0)
                        {
                        if($corr_is_first)
                          {
                          foreach ($spec_invoices_0 as $spec_invoice)
                            {
                            $add_correct_oper = [
                                'curtbl' => 291,
                                'f_correctid' => $correct_id,
                                'f_correctoperid' => $spec_invoice,
                                'oper' => 'add'
                                ];
                            $corrects_opers_class->addRowTbl($dbh, $add_correct_oper, "");
                            }
                          $add_correct = [
                              'curtbl' => 290,
                              'f_specid' => $curidx,
                              'oper' => 'add'
                              ];
                          // создается f_num=1 корректировка
                          $add_result = $corrects_class->addRowTbl($dbh, $add_correct, "");
                          if($add_result[0])
                            {
                            $correct_id = $add_result[2];
                            foreach ($spec_invoices_1 as $spec_invoice)
                              {
                              $add_correct_oper = [
                                  'curtbl' => 291,
                                  'f_correctid' => $correct_id,
                                  'f_correctoperid' => $spec_invoice,
                                  'oper' => 'add'
                                  ];
                              $corrects_opers_class->addRowTbl($dbh, $add_correct_oper, "");
                              }
                            }
                          }
                        else
                          {
                          $spec_invoices = array_merge($spec_invoices_0, $spec_invoices_1);
                          foreach ($spec_invoices as $spec_invoice)
                            {
                            $add_correct_oper = [
                                'curtbl' => 291,
                                'f_correctid' => $correct_id,
                                'f_correctoperid' => $spec_invoice,
                                'oper' => 'add'
                                ];
                            $corrects_opers_class->addRowTbl($dbh, $add_correct_oper, "");
                            }
                          }
                        }
                      unset($corrects_class);
                      unset($corrects_opers_class);
                      // ~ sdid 2023
//sdid 1028
//sdid 1129
                    // Устанавливаем статус=Закрыта, дата=дата закрытия спецификации во всех операциях, 
                    // где Спецификация попадает в категорию Операции f_ctgtype=24 по f_valstr=$row['f_id'] 
                    // и вид операци, у которых установлен признак "Операция по другому договору" (veda_typeopers.f_otherdogs=1)
                    $sql = "select si.f_id
                            from veda_spec_invoices si, veda_categs ct, veda_typeopers t 
                            where t.f_id=si.f_idoper
                              and t.f_otherdogs=1	 
                              and si.f_id=ct.f_objectid 
                              and ct.f_ctgtype=24 
                              and ct.f_objecttype=5
                              and ct.f_valstr='".$row['f_id']."'";
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>35,'curidx'=>$row1['f_id'],'f_status'=>4,'f_dtclose'=>$specdtclose,'f_buh'=>$row['f_buhid'],'f_levelrp'=>4);
                      editRowTbl($ki);
                      }
//~sdid 1129
//~sdid 1028
                    }
                  //sdid 3339
                  elseif($nstatus==8) //Спец. РП. Согласован.
                    {
                    $agentrep = new AgentReps("agentreps", $dbh);
                    $agentrep->createAgentRepBySpec2($curidx);
                    }
                  //~sdid 3339
                  // sdid 2023
                  elseif(($ostatus==6)&&($nstatus!=6)&&($nstatus!=20)&&($nstatus!=21)&&($nstatus!=22))//если открыли закрытую спецификацию
                    {
                    $sql = "select f_id,f_status from ".DBPref."spec_invoices where f_outbuhperiod=0 AND f_parenttype=2 and f_specid=".$row['f_id'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row1['f_status']==7)
                        {
                        $ki = Array('curtbl'=>35,'curidx'=>$row1['f_id'],'f_status'=>0);
                        editRowTbl($ki);
                        }
                      }
                    }
                  // ~ sdid 2023
                  }
                if(($row['f_buhid']!=$obuhid)&&($row['f_buhid']>0))
                  {
                  $nadd = 1;
                  $sql1 = "select f_id from ".DBPref."prjparts where f_typeobj=1 and f_idobj=".$curidx." and f_typerole=2 and f_idsubj=".$row['f_buhid'];
                  $res1 = $dbh->query($sql1);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {$nadd = 0;}
                  if($nadd==1)
                    {
                    $ki = Array('curtbl'=>146,'f_typeobj'=>1,'f_idobj'=>$curidx,'f_typerole'=>2,'f_idsubj'=>$row['f_buhid'],'f_part'=>1,'f_ismain'=>1);
                    $ar = json_decode(addRowTbl($ki), true);
                    }
                  // sdid 1566
                  if ($nstatus == 16) // Спец. Доставлено
                    {
                    $old_buh = "";
                    $new_buh = "";
                    $sql_old_buh = "SELECT CONCAT(u.f_name1, ' ', u.f_name2) buh FROM ".DBPref."users u WHERE u.f_id=".$obuhid;
                    $sql_new_buh = "SELECT CONCAT(u.f_name1, ' ', u.f_name2) buh FROM ".DBPref."users u WHERE u.f_id=".$row['f_buhid'];
                    $res_old_buh = $dbh->query($sql_old_buh);
                    if ($row_old_buh = $res_old_buh->fetch(PDO::FETCH_ASSOC))
                      {
                      $old_buh = $row_old_buh['buh'];
                      }
                    $res_new_buh = $dbh->query($sql_new_buh);
                    if ($row_new_buh = $res_new_buh->fetch(PDO::FETCH_ASSOC))
                      {
                      $new_buh = $row_new_buh['buh'];
                      }
                    $subj_msg = "Уведомление о смене ответственного бухгалтера";
                    $body_msg = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> изменен ответственный бухгалтер:<br>".$old_buh." -> ".$new_buh;
                    mSendMail($_SESSION['loginid'],0,$obuhid,$subj_msg,$body_msg,"",15,$curidx);
                    mSendMail($_SESSION['loginid'],0,$row['f_buhid'],$subj_msg,$body_msg,"",15,$curidx);
                    mSendMail($_SESSION['loginid'],41,0,$subj_msg,$body_msg,"",15,$curidx);
                    }
                  // ~ sdid 1566
                  }
                if(($row['f_operid']!=$ooperid)&&($row['f_operid']>0))
                  {
                  $nadd = 1;
                  $sql1 = "select f_id from ".DBPref."prjparts where f_typeobj=1 and f_idobj=".$curidx." and f_typerole=1 and f_idsubj=".$ooperid;
                  $res1 = $dbh->query($sql1);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    $ki = Array('curtbl'=>146,'curidx'=>$row1['f_id'],'f_idsubj'=>$row['f_operid']);
                    editRowTbl($ki);
                    }
                  else
                    {
                    $sql1 = "select f_id from ".DBPref."prjparts where f_typeobj=1 and f_idobj=".$curidx." and f_typerole=1 and f_idsubj=".$row['f_operid'];
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {$nadd = 0;}
                    if($nadd==1)
                      {
                      $sql1 = "select (1-sum(f_part)) upart from ".DBPref."prjparts where f_typeobj=1 and f_idobj=".$curidx." and f_typerole=1";
                      $res1 = $dbh->query($sql1);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row1['upart']>0)
                          {
                          $ki = Array('curtbl'=>146,'f_typeobj'=>1,'f_idobj'=>$curidx,'f_typerole'=>1,'f_idsubj'=>$row['f_operid'],'f_part'=>$row1['upart'],'f_ismain'=>1);
                          $ar = json_decode(addRowTbl($ki), true);
                          }
                        }
                      }
                    }
                  }
                //sdid 2084 tz2 2
                if(strcmp($odtarrivalto,$row['f_dtarrivalto'])!=0) // Изменилось значение в поле "Дата прибытия в пункт ТО"
                  {
                  if($row['f_postid']>0)
                    {  
                    $sql1 = "select f_id, min(f_p2dt) f_p2dt from veda_routes where f_postid=".$row['f_postid']." and f_p2iscustom=1 ";
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if(strcmp($row['f_dtarrivalto'],$row1['f_p2dt'])!=0)
                        {
                        $ndtarrivalto = $row['f_dtarrivalto'];
                        if(strlen($row['f_dtarrivalto'])==0) {$ndtarrivalto = "0000-00-00 00:00:00";}
                        $ki = Array('curtbl'=>77,'curidx'=>$row1['f_id'],'f_p2dt'=>$ndtarrivalto);
                        editRowTbl($ki);
                        }
                      }
                    }
                  }
                //~sdid 2084 tz2 2
                if(($ocomisprep!=$row['f_comisprep'])&&($row['f_comisprep']==1)) { //установили признак - комментарий готов
                  //sdid 2273
                  $sql1 = "";
                  //Если "Договор ТО" - Текущая спецификация, ищем в Информация о ТО записи, связанные с текущей спецификацией
                  if($row['f_todog']==20)
                    {
                    $sql1 = "select f_id,f_operto from ".DBPref."dt where f_specid=".$curidx;
                    } 
                  else
                    {
                    $strtodog = (string) $row['f_todog'];
                    if(substr($strtodog,0,1)==3) // Если "Договор ТО" - Договор ВА-ТП, ищем в Информация о ТО записи, связанные заявками "Поручение ТП", связанные с текущей спецификацией
                      {
                      $strtodogparm = substr($strtodog,1);
                      $sql1 = "select dt.f_id, dt.f_operto
                                 from veda_specs s, veda_dogs d, veda_dogs d2, veda_dt dt
                                where d.f_id=s.f_dogid and s.f_subtype=3 and d.f_subtype=3 and dt.f_specid=s.f_id
                                  and d.f_contrid=d2.f_contrid #and d.f_orgid=d2.f_orgid
                                  and s.f_parentspecid=$curidx and d2.f_id=$strtodogparm";
                      }
                    }
                  if(strlen($sql1)>0)
                    {
                    $res1 = $dbh->query($sql1);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))                  
                      {
                      $ki = Array('curtbl'=>61,'curidx'=>$row1['f_id'],'f_status'=>24);
                      editRowTbl($ki);
                      if($row1['f_operto']>0)
                        {if($menomes==0){mSendMail($_SESSION['loginid'],0,$row1['f_operto'],"Для информации о ТО установлен статус - Комментарий готов","Для <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">Иформации о ТО</a> установлен статус - Комментарий готов","",61,$row1['f_id']);}}
                      else
                        {if($menomes==0){mSendMail($_SESSION['loginid'],22,0,"Для информации о ТО установлен статус - Комментарий готов","Для <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">Иформации о ТО</a> установлен статус - Комментарий готов","",61,$row1['f_id']);}}
                      }
                    }
                  //$sql1 = "select f_id,f_operto from ".DBPref."dt where f_specid=".$curidx;
                  //$ans=$ans."|".$sql1;
                  //sdid 1690
                  //$res1 = $dbh->query($sql1);
                  //while($row1 = $res1->fetch(PDO::FETCH_ASSOC))                  
                  //  {
                  //  $ki = Array('curtbl'=>61,'curidx'=>$row1['f_id'],'f_status'=>24);
                  //  editRowTbl($ki);
                  //  if($row1['f_operto']>0)
                  //    {if($menomes==0){mSendMail($_SESSION['loginid'],0,$row1['f_operto'],"Для информации о ТО установлен статус - Комментарий готов","Для <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">Иформации о ТО</a> установлен статус - Комментарий готов","",61,$row1['f_id']);}}
                  //  else
                  //    {if($menomes==0){mSendMail($_SESSION['loginid'],22,0,"Для информации о ТО установлен статус - Комментарий готов","Для <a href=\"".$redirect_uri."?pgid=61&obid=".$row1['f_id']."\">Иформации о ТО</a> установлен статус - Комментарий готов","",61,$row1['f_id']);}}
                  //  }
                  //~sdid 1690
                  //~sdid 2273
                  }
                if($row['f_wens']==1)//страховка
                  {
                  $sql1 = "select f_id from ".DBPref."ensures where f_objtype=".$kpt." and f_objid=".$curidx;
                  //echo $sql1."|";
                  $res1 = $dbh->query($sql1);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {$kkk=$row1['f_id'];}
                  else
                    {
                    $tsum = 0;
                    $sql2 = "select case when d.f_count>0 then ifnull(sum((d.f_count*d.f_price)),0) else ifnull(sum(d.f_price_all),0) end tsum ".
                            "from ".DBPref."spec_details d where d.f_specid=".$curidx;
                    //echo $sql2."|";
                    $res2 = $dbh->query($sql2);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC)){$tsum=$row2['tsum'];}
                    //echo "|".$curidx."|";
                    $cdtst = 0;
                    //if($iskp==0){$cdtst=1;}elseif($iskp==1){$cdtst=23;}
                    //$ki = Array('curtbl'=>54,'f_objtype'=>$kpt,'f_objid'=>$curidx,'f_status'=>$cdtst,'f_dogid'=>$row['f_ensdogid'],
                    //          'f_tovar'=>$row['f_enstovar'],'f_custom'=>$row['f_enscustom'],'f_logist'=>$row['f_enslogist'],
                    //          'f_lostprof'=>$row['f_enslostprof']);
                    $ki = Array('curtbl'=>54,'f_objtype'=>$kpt,'f_objid'=>$curidx,'f_status'=>$cdtst,'f_enssump'=>$tsum);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      if($row['f_uved']==1)
                        {
                        if($menomes==0)
                          {
                          //sdid 1546
                          $subject = "Для ".$iskps." создана запись о страховании";
                          $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$ar[2]."\">Страховании</a>";
                          $sbsh = 0;
                          if($menomes==0) 
                            { 
                            $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 54, $ar[2], 151, 4, 11); 
                            if($sbsh == 0) 
                              { 
                              mSendMail($_SESSION['loginid'],8,0,$subject,$postbody,"",54,$ar[2]);
                              }
                            }                     
                          //mSendMail($_SESSION['loginid'],8,0,"Для ".$iskps." создана запись о страховании","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=54&obid=".$ar[2]."\">Страховании</a>","",54,$ar[2]);
                          //~sdid 1546
                          //mSendMail($_SESSION['loginid'], 8, 0, "Для " . $iskps . " создана запись о страховании", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=54&obid=" . $ar[2] . "\">Страховании</a>", "", 54, $ar[2]);
                          }
                        }
                      }
                    }
                  }
                if($row['f_wsert']==1)//сертификация
                  {
                  $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
                  $dbh->exec('SET CHARACTER SET utf8');
                  $sql1 = "select ifnull(count(*),0) cnt from ".DBPref."certificates where f_specid=".$curidx;
                  $res1 = $dbh->query($sql1);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['cnt']==0)
                      {
                      //$ans=$row['f_wsert']."_".$sql1;
                      $ki = Array('curtbl'=>56,'f_specid'=>$curidx);
                      $ar = json_decode(addRowTbl($ki), true);
                      if($ar[0]=="true")
                        {
                        if($row['f_uved']==1)
                          {
                          if($menomes==0)
                            {
                            //sdid 1546
                            $subject = "Для ".$iskps." создана запись о сертификации";
                            $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>";
                            $sbsh = 0;
                            if($menomes==0) 
                              { 
                              $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 56, $ar[2], 151, 7, 13); 
                              if($sbsh == 0) 
                                { 
                                mSendMail($_SESSION['loginid'],9,0,$subject,$postbody,"",56,$ar[2]);
                                }
                              }                     
                            //mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$ar[2]."\">Сертификации</a>","",56,$ar[2]);
                            //~sdid 1546
                            //mSendMail($_SESSION['loginid'], 9, 0, "Для " . $iskps . " создана запись о сертификации", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=56&obid=" . $ar[2] . "\">Сертификации</a>", "", 56, $ar[2]);
                            }
                          }
                        }
                      //$sql1 = "insert into ".DBPref."certificates (f_userid,f_dttmcr,f_specid) values (".$_SESSION['loginid'].",NOW(),".$curidx.")";
                      //$kkk  = $dbh->exec($sql1);
                      //if($kkk>0)
                      //  {
                      //  $kkk  = $dbh->lastInsertId();
                      //  if($row['f_wpost']==1)
                      //    {mSendMail($_SESSION['loginid'],9,0,"Для ".$iskps." создана запись о сертификации","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kkk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=56&obid=".$kkk."\">Сертификации</a>","",0,0);}
                      //  }
                      }
                    }
                  }
                //sdid 2084 tz2 2 
                /*
                if($row['f_wto']==1)//таможенное оформление
                  {
		  // sdid = 391
		  // Если Таможенное оформление не было выбрано ранее, сейчас выбрано, и выбран договор Таможенного оформления, 
		  // то создаем заявку с подвидом "Поручение ТП", связываем ее с текущей спецификацией
		  if(
		     (
                      (($owto == 0)&&($row['f_tpdog']>0)) || 
                      (($otpdog == 0)&&($row['f_tpdog']>0))
                     )
                     &&($row['f_subtype']!=3))
		    {
                    //Создаем Заявку "Поручение ТП"
                    $sql = "select count(*) cnt from ".DBPref."specs where f_dogid=".$row['f_tpdog']." and f_parentspecid=".$row['f_id']." and f_subtype=3";
                    $res2 = $dbh->query($sql);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нет ни одной связанной заявки "Поручение ТП"
                      {
                      if($row2['cnt']==0)
                        {
                        //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                        $obj = new ArrayObject($ik);
                        $ki1 = $obj->getArrayCopy();
                        $ki1['curtbl']       = 15;
                        $ki1['parentspecid'] = $curidx;
                        $ki1['dogid']        = $row['f_tpdog'];
                        $ki1['subtype']      = 3;
                        $ki1['f_dt']         = date("Y-m-d");
                        $ki1['typez']        = 3; //2022.11.09
                        $ki1['f_kod1cb']     = "";
                        $ki1['f_kod1cp']     = "";

                        $ki1['wens']=false;
                        $ki1['wsert']=false;
                        $ki1['wpost']=false;
                        $ki1['sbor']=false;
                        //$ki1['wto']=false;
                        $ki1['uvedins']=false;
                        $ki1['uvedto']=false;
                        $ki1['uvedcrt']=false;
                        $ki1['f_postid']=0;
                      
                        unset($ki1['id']);
                        unset($ki1['curidx']);
                        unset($ki1['oper']);
                        unset($ki1['f_num']);
                        unset($ki1['tpdog']);
                      
                        $ar = json_decode(addRowTbl($ki1), true);
                        if($ar[0]=="true")
                          {
                          $wdopmes = 1;
                          $ansn = $ansn."Добавили связанную заявку - Поручение ТП";
                          //sdid2058
                          $ki2  = Array('curtbl'=>15,'curidx'=>$ar[2], 'f_uvedto'=>$row['f_uvedto']);//выставили аналогичный признак уведомления
                          editRowTbl($ki2);
                          //~sdid2058
                          }
                        else
                          {
                          $wdopmes = 1;
                          $anns = $ansn."Ошибка добавления заявку - Поручение ТП";
                          }
                        }
                      }
                    }
                  // ~sdid = 391
                  //sdid2058
                  elseif(($row['f_tpdog']>0) && ($otpdog==0) && ($row['f_subtype']!=3))
                    {
                    //удаляем информацию о ДТ, связанную со спецификацией
                    $derr = 0;
                    $sql  = "select f_id from ".DBPref."dt where f_specid=".$row['f_id'];
                    $res2 = $dbh->query($sql);
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нашли записи "Информация о ТО"
                      {
                      $ki  = Array('curtbl'=>61,'curidx'=>$row2['f_id']); //удаляем связанную "Информация о ТО"
                      $rvd = json_decode(delRowTbl($ki),true);
                      if($rvd[0]=="true")
                        {}
                      else{$derr++;}
                      }
                    //Клонируем входной массив, удалим лишние элементы, иниализируем нужные
                    $obj = new ArrayObject($ik);
                    $ki1 = $obj->getArrayCopy();
                    $ki1['curtbl']       = 15;
                    $ki1['parentspecid'] = $curidx;
                    $ki1['dogid']        = $row['f_tpdog'];
                    $ki1['subtype']      = 3;
                    $ki1['f_dt']         = date("Y-m-d");
                    $ki1['typez']        = 3; //2022.11.09
                    $ki1['f_kod1cb']     = "";
                    $ki1['f_kod1cp']     = "";

                    $ki1['wens']         = false;
                    $ki1['wsert']        = false;
                    $ki1['wpost']        = false;
                    $ki1['sbor']         = false;
                    $ki1['uvedins']      = false;
                    $ki1['uvedto']       = false;
                    $ki1['uvedcrt']      = false;
                    $ki1['f_postid']     = 0;
                    
                    unset($ki1['id']);
                    unset($ki1['curidx']);
                    unset($ki1['oper']);
                    unset($ki1['f_num']);
                    unset($ki1['tpdog']);
                    
                    $ar = json_decode(addRowTbl($ki1), true);
                    if($ar[0]=="true")
                      {
                      $wdopmes = 1;
                      $ansn = $ansn."Добавили связанную заявку - Поручение ТП";
                      $ki2  = Array('curtbl'=>15,'curidx'=>$ar[2], 'f_uvedto'=>$row['f_uvedto']);//выставили аналогичный признак уведомления
                      editRowTbl($ki2);
                      }
                    else
                      {
                      $wdopmes = 1;
                      $anns = $ansn."Ошибка добавления заявку - Поручение ТП";
                      }
                    //if($derr>0)
                    //  {
                    //  //Нужно сделать update f_tpdog=NULL в текущей строке ($row['f_tpdog'])
                    //  //$ki = Array('curtbl'=>15,'curidx'=>$row['f_id'], 'wto'=>1);
                    //  $ki = Array('curtbl'=>15,'curidx'=>$row['f_id'], 'tpdog'=>$row['f_tpdog'], 'wto'=>1);
                    //  editRowTbl($ki);
                    //  $ansn = $ansn."Не все связанные Информация о ТО удалены";
                    //  $wdopmes = 1;
                    //  }
                    //else{$ansn = $ansn."Удалены связанные - Информация о ТО";$wdopmes = 1;}
                    }
                  //~sdid2058
                  elseif($owto == 0)
                    {
                    $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
                    $dbh->exec('SET CHARACTER SET utf8');
                    $sql1 = "select f_id from ".DBPref."dt where f_specid=".$curidx;
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {$kkk=0;}
                    else
                      {
                      $cst = 0;
                      if($iskp==1){$cst = 23;}
                      $ki = Array('curtbl'=>61,'f_specid'=>$curidx,'f_status'=>$cst);
                      $ar = json_decode(addRowTbl($ki), true);
                      if($ar[0]=="true")
                        {
                        if($row['f_uved']==1)
                          {
                          if($menomes==0)
                            {
                            //sdid 1546
                            $subject = "Для ".$iskps." создана запись информации о ДТ";
                            $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>";
                            $sbsh = 0;
                            if($menomes==0) 
                              { 
                              $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $ar[2], 151, 1, 12); 
                              if($sbsh == 0) 
                                { 
                                mSendMail($_SESSION['loginid'],22,0,$subject,$postbody,"",61,$ar[2]);
                                }
                              }                     
                            //mSendMail($_SESSION['loginid'],22,0,"Для ".$iskps." создана запись информации о ДТ","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=61&obid=".$ar[2]."\">информации о ТО</a>","",61,$ar[2]);
                            //~sdid 1546                            
                            //mSendMail($_SESSION['loginid'], 22, 0, "Для " . $iskps . " создана запись информации о ДТ", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=61&obid=" . $ar[2] . "\">информации о ТО</a>", "", 61, $ar[2]);
                            }
                          }
                        }
                      }
                    }
                  }
                //sdid = 391
                // Если галку "ТО" сняли и ранее она была выставлена
                elseif(($row['f_wto']==0)&&($owto == 1))
                  {
                  if($otpdog>0)//условие 4 // если есть связанная заявка "Поручение ТП", ищем ее f_id
                    {
                    $derr = 0;
                    $sql  = "select f_id from ".DBPref."specs where f_dogid=".$otpdog." and f_parentspecid=".$row['f_id']." and f_subtype=3";
                    //$ans = $ans."|".$sql;
                    $res2 = $dbh->query($sql);
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нашли заявку "Поручение ТП", проверяем, есть ли связанные операции, и, если нет, удаляем заявку
                      {
                      $sql = "select count(*) cnt from ".DBPref."spec_invoices where f_specid=".$row2['f_id']." and f_parenttype=2";
                      //$ans = $ans."|".$sql;
                      $res3 = $dbh->query($sql);
                      if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row3['cnt']==0)
                          {
                          $ki  = Array('curtbl'=>15,'curidx'=>$row2['f_id']); //удаляем связанное "Поручение ТП"
                          //$rvd = delRowTbl($ki);
                          $rvd = json_decode(delRowTbl($ki),true);
                          //$ans = $ans."|rvd[0] = <".$rvd[0].">"."|rvd[1] = <".$rvd[1].">";
                          //$ans = $ans."|rvd[1] = <".$rvd[1].">";
                          if($rvd[0]=="true")
                            {}
                          else{$derr++;}
                          }
                        }
                      }
                    if($derr>0)
                      {
                      //Нужно сделать update f_tpdog=NULL в текущей строке ($row['f_tpdog'])
                      $ki = Array('curtbl'=>15,'curidx'=>$row['f_id'], 'tpdog'=>$row['f_tpdog'], 'wto'=>1);
                      editRowTbl($ki);
                      $ansn = $ansn." Не все связанные заявки - Поручение ТП удалены";
                      $wdopmes = 1;
                      }
                    else
                      {
                      $ansn = $ansn." Удалены связанные заявки - Поручение ТП";
                      $wdopmes = 1;
                      }
                    }
                  else//условие 5
                    {
                    $derr = 0;
                    $sql  = "select f_id from ".DBPref."dt where f_specid=".$row['f_id'];
                    $res2 = $dbh->query($sql);
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если нашли записи "Информация о ТО"
                      {
                      $ki  = Array('curtbl'=>61,'curidx'=>$row2['f_id']); //удаляем связанную "Информация о ТО"
                      //$rvd = delRowTbl($ki);
                      $rvd = json_decode(delRowTbl($ki),true);
                      if($rvd[0]=="true")
                        {}
                      else{$derr++;}
                      }
                    if($derr>0)
                      {
                      //Нужно сделать update f_tpdog=NULL в текущей строке ($row['f_tpdog'])
                      //$ki = Array('curtbl'=>15,'curidx'=>$row['f_id'], 'wto'=>1);
                      $ki = Array('curtbl'=>15,'curidx'=>$row['f_id'], 'tpdog'=>$row['f_tpdog'], 'wto'=>1);
                      editRowTbl($ki);
                      $ansn = $ansn."Не все связанные Информация о ТО удалены";
                      $wdopmes = 1;
                      }
                    else{$ansn = $ansn."Удалены связанные - Информация о ТО";$wdopmes = 1;}
                    }
                  }*/
                //~sdid 2084 tz2 2
                //~sdid = 391
                if(($row['f_wpost']==1)&&($row['f_postid']==0)&&($row['f_sbor']==0))
                  {
                  $ki = Array('curtbl'=>102,'f_contrrecid'=>0);
                  $ar = json_decode(addRowTbl($ki), true);
                  if($ar[0]=="true")
                    {
                    $ki = Array('curtbl'=>15,'curidx'=>$curidx,'f_postid'=>$ar[2]);
                    editRowTbl($ki);
                    if($row['f_uved']==1)
                      {
                      if($menomes==0)
                        {
                        //sdid 1546
                        $subject = "Для ".$iskps." создана запись о доставке";
                        $postbody = "Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$ar[2]."\">Доставке</a>";
                        $sbsh = 0;
                        if($menomes==0) 
                          { 
                          $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 102, $ar[2], 151, 2, 14); 
                          if($sbsh == 0) 
                            { 
                            mSendMail($_SESSION['loginid'],2,0,$subject,$postbody,"",102,$ar[2]);
                            }
                          }                     
                        //mSendMail($_SESSION['loginid'],2,0,"Для ".$iskps." создана запись о доставке","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$curidx."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=102&obid=".$ar[2]."\">Доставке</a>","",102,$ar[2]);
                        //~sdid 1546
                        //mSendMail($_SESSION['loginid'], 2, 0, "Для " . $iskps . " создана запись о доставке", "Для <a href=\"" . $redirect_uri . "?pgid=" . $iskpid . "&obid=" . $curidx . "\">" . $iskps . "</a> создана запись о <a href=\"" . $redirect_uri . "?pgid=102&obid=" . $ar[2] . "\">Доставке</a>", "", 102, $ar[2]);
                        }
                      }
                    }
                  //$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
                  //$dbh->exec('SET CHARACTER SET utf8');
                  //$sql1 = "insert into ".DBPref."shipments (f_userid,f_dttmcr) values (".$_SESSION['loginid'].",NOW())";
                  //$dbh->exec($sql1);
                  //$ki=$dbh->lastInsertId();
                  //if($ki>0)
                  //  {
                  //  $sql1 = "update ".DBPref."specs set f_postid=".$ki." where f_id=".$curidx;
                  //  $dbh->exec($sql1);
                  //  if($row['f_wpost']==1)
                  //    {mSendMail($_SESSION['loginid'],2,0,"Для ".$iskps." создана запись о поставке","Для <a href=\"".$redirect_uri."?pgid=".$iskpid."&obid=".$kkk."\">".$iskps."</a> создана запись о <a href=\"".$redirect_uri."?pgid=90&obid=".$ki."\">Поставке</a>","",0,0);}
                  //  }
                  }
                }
              }
            //!!!
            elseif(strcmp($tblname,DBPref."spec_invoices")==0)//обрабатываем обновление операции
              {
              //sdid - 203
              // if(isset($extra_data_hash['close_document_id']))
              //   {
              //   if($extra_data_hash['close_document_id']>0)
              //     {
              //     $subject = "Cумма маржи в РП была изменена";
              //     $postbody = "<a href=\"".$redirect_uri."?pgid=145&obid=".$curidx."\">Операция</a> исключенная из РП";
              //     $postbody .= "<br><a href=\"".$redirect_uri."?pgid=".$extra_data_hash['table_id']."&obid=".$extra_data_hash['close_document_id']."\">Закрывающий документ</a>";
              //     $postbody .= lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 6, $curidx);
              //     mSendMail(2,2,0,$subject,$postbody,"",35,$row['f_id']);
              //     }
              //   }
              //~sdid - 203

              //получаем инфу по операции после обновления
              $sql = "select * from ".DBPref."spec_invoices where f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($row['f_status']!=$ostatus)//изменился статус операции
                  {
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=61 and f_num=".$row['f_status'];
                  //sdid - 164
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=61 and f_num=".$row['f_status'];
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=61 and f_num=".$row['f_status'];
                  //~sdid 703
                  //~sdid - 164
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      $ans=$ans."|".$mgrp."_".$musr;
                      //echo $ans."|";
                      if($menomes==0)
                        {
                        //sdid - 164
                        //sdid 703
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 6, $curidx);}
                        else
                          {$subject = "У операции изменен статус на ".$row1['f_name'];}
                        //~sdid 703
                        //echo $subject."|";
                        $postbody = "У <a href=\"".$redirect_uri."?pgid=145&obid=".$curidx."\">операции</a> изменен статус на \"".$row1['f_name']."\"";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 6, $curidx);
                        //echo $postbody."|";
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",35,$row['f_id']);
                        //~sdid - 164
                        }
                      }
                    }
                  if(($row['f_parenttype']==2)&&($row['f_status']==4))//если установили статус "Закрыта" в операции по спецификации
                    {
                    $sql = "select f_status from ".DBPref."specs where f_id=".$row['f_specid'];
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row1['f_status']==6)//если по закрытой спецификации
                        {
                        $ki = Array('curtbl'=>35,'curidx'=>$row['f_id'],'f_dtclose'=>date("Y-m-d"));
                        editRowTbl($ki);
                        }
                      }
                    }
                  }
                if(($row['f_uved']!=$ouved)&&($row['f_uved']==1))//установили признак уведомления
                  {
                  //echo $row['f_uvedtyp']."_";
                  if($row['f_uvedtyp']==2)
                    {$mgrp=$row['f_adr'];$musr=0;}
                  else
                    {$mgrp=0;$musr=$row['f_adr'];}
                  $ans=$ans."|".$mgrp."_".$musr;
                  //echo $ans."|";
                  $tmpsh="";
                  if($row['f_template']>0)
                    {
                      //sdid - 164
                      $tmpsh = lGetPostShablon($row['f_template'],$row['f_dogid'],$row['f_val'],$row['f_sum'],$row['f_invid'], 6, $curidx);
                      //~sdid - 164
                    }
                  $postbody = "<a href=\"".$redirect_uri."?pgid=35&obid=".$row['f_id']."\">Ссылка на операцию</a><br>";
                  if(strlen($row['f_com'])>0)
                    {$postbody=$postbody."Комментарий:<br>".$row['f_com']."<br>";}
                  $postbody = $postbody."".$tmpsh;
                  if($menomes==0)
                    {
                    //echo $menomes."|".$_SESSION['loginid']."|".$mgrp."|".$musr."|".$postbody."|".$row['f_id']."|";
                    mSendMail($_SESSION['loginid'],$mgrp,$musr,"По операции установлен признак уведомления",$postbody,"",35,$row['f_id']);
                    }
                  }
                //sdid - 882
//                if(($row['f_isvozm']!=$oisvozm)&&($oisvozm==1))//изменилась возмещаемость операции
//                  {
//                  if(usenumdocreestr==1)//если используем реестр номеров документов
                if(($row['f_isvozm']!=$oisvozm))//изменилась возмещаемость операции
                  {
                  if((usenumdocreestr==1)&&($oisvozm==1))//если используем реестр номеров документов
//~sdid 246
                    {
                    $sql = "select ndrd.f_id,ndrd.f_numdocreestrid from ".DBPref."akts_details_opers ado,".DBPref."numdocreestr_docs ndrd 
                            where ndrd.f_docid=ado.f_id and ado.f_operid=$curidx";
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>257,'curidx'=>$row1['f_id']);//удаляем связку операции с детализацией по закрывающему документу
                      delRowTbl($ki);
                      $sql = "select ifnull(count(*),0) cnt from ".DBPref."numdocreestr_docs where f_numdocreestrid=".$row1['f_numdocreestrid'];
                      $res2 = $dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row2['cnt']==0)//если удалили связку с последним, то чистим реестр не снимая признак выгрузки
                          {
                          $ki = Array('curtbl'=>255,'curidx'=>$row1['f_numdocreestrid'],'f_docid'=>0,'f_doctype'=>0);
                          editRowTbl($ki);
                          }
                        }
                      }
                    }
//sdid 246
                  // Вытащим дату закрытия бух.периода
                  $dtCloseBP = "";
                  $sql = "select distinct max(se.f_dtbuhcls) from veda_settings se";
                  $res3 = $dbh->query($sql);
                  if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                    {
                    if(isset($row3['f_dtbuhcls'])) {$dtCloseBP = $row3['f_dtbuhcls'];}
                    }

                  // Если операция доходная, меняем возмещаемость у всех связанных расходных операций на ту же возмещаемость, что и у текущей
                  if(operVidDoh($row['f_bdrarticle'], $dbh) == 1)
                    {
                    //$sql="select f_id, f_bdrarticle from veda_spec_invoices where f_parentid=".$curidx;  
                    $sql="select si.f_id, si.f_bdrarticle, si.f_specid, t.f_name opername, s.f_status  
                            from veda_spec_invoices si, veda_specs s, veda_typeopers t 
                           where t.f_id=si.f_idoper and s.f_id=si.f_specid and si.f_parentid=".$curidx;  
                    $res2 = $dbh->query($sql);
                    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      if(operVidDoh($row2['f_bdrarticle'], $dbh) == 2) // Если операция расходная, в т.ч. выбрана статья бюджета, меняем возмещаемость операции
                        {
                        $ki = Array('curtbl'=>35,'curidx'=>$row2['f_id'],'f_isvozm'=>$row['f_isvozm']);
                        editRowTbl($ki);
                        }
                      }
                    }

                  // sdid 246 2023-04-23
                  // Вытащим необходимые связанные с операций параметры 
                  $operName = "";
                  $specStatus = 0;
                  $sql="select t.f_name opername, s.f_status  
                          from veda_spec_invoices si, veda_specs s, veda_typeopers t 
                        where t.f_id=si.f_idoper and s.f_id=si.f_specid and si.f_id=".$curidx;  
                  $res2 = $dbh->query($sql);
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    if(isset($row2['opername'])) {$operName = $row2['opername'];}
                    if(isset($row2['f_status'])) {$specStatus = $row2['f_status'];}
                    }
                  //~ sdid 246 2023-04-23
                  // 2. Если к измененным операциям были привязаны финансовые документы - уведомляем ответственного бухгалтера о смене "типа возмещаемости" 
                  //    операций следующего содержания: 
                  //   "Изменился тип возмещаемости по "Наименование операции", ссылка на Спецификацию с возможностью перехода в нее. 
                  if(operIsHaveFinDocs($row['f_id'], $dbh) == 1)
                    {
                    mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по операции '".$operName."'",
                    "Изменился тип возмещаемости по операции '".$operName."', <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_specid']."\">Ссылка на спецификацию</a><br>","",35,$row['f_id']);
                    //"Изменился тип возмещаемости по операции '".$row2['opername']."', <a href=\"".$redirect_uri."?pgid=15&obid=".$row2['f_specid']."\">Ссылка на спецификацию</a><br>","",35,$row2['f_id']);
                    }

                  // Обрабатываем фин.документы
                  // 1. Обрабатываем счета
                  $sql = "select s.f_id,s.f_maininv,s.f_ismaininv,s.f_num,s.f_dt,s.f_vozm
                            from veda_schets s
                           where s.f_type=1 and s.f_operid=".$row['f_id'];
                  $res3 = $dbh->query($sql);
                  while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                    {// 1.1. один счет - одна операция - f_maininv=0 и f_ismaininv=0 + счет относится к незакрытому периоду (пункт 5) + спецификация не закрыта
                    if(($row3['f_maininv'] == 0) && ($row3['f_ismaininv'] == 0) && (strtotime($row3['f_dt']) > strtotime($dtCloseBP)) && ($specStatus <> 6)) 
                    //if(($row3['f_maininv'] == 0) && ($row3['f_ismaininv'] == 0))  
                      {
                      $ki = Array('curtbl'=>17,'curidx'=>$row3['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в счете
                      editRowTbl($ki);
                      $retval = json_decode(expFinDocTo1C(1,$row3['f_id']),true); // Выгружаеем заново счет в 1С
                      $expmsg = ""; 
                      if($retval[0] == true) {$expmsg = "Попытка выгрузки счета в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                      elseif($retval[0] == false) {$expmsg = "Попытка выгрузки счета в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}

                      mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по счету № ".$row3['f_num']." от ".$row3['f_dt'],
                      " Изменился тип возмещаемости по счету № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=17&obid=".$row3['f_id']."\">Ссылка на счет</a><br>".$expmsg,"",35,$row['f_id']);
                      //" Изменился тип возмещаемости по счету № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=17&obid=".$row3['f_id']."\">Ссылка на счет</a><br>".$expmsg,"",17,$row3['f_id']);
                      }
                    elseif(($row3['f_maininv'] > 0) && (strtotime($row3['f_dt']) > strtotime($dtCloseBP)) && ($specStatus <> 6)) // 1.2. счет входит в состав агрегированного счета f_maininv>0  (пункт 4)
                      {
                      $cntSchets = 0;
                      $cntMaininv = 0;
                      $cntVozm = 0;
                      $sql = "select s.f_id,s.f_maininv,s.f_ismaininv,s.f_num,s.f_dt,s.f_vozm
                                from veda_schets s
                               where s.f_type=1 and s.f_maininv=".$row3['f_maininv'];
                      $res4 = $dbh->query($sql);
                      while($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                        {
                        $cntSchets++;
                        //if($row4['f_maininv'] == $row3['f_maininv']) {$cntMaininv++;}
                        if($row4['f_vozm'] == $row3['f_vozm']) {$cntVozm++;}
                        }
                      //if($cntMaininv == $cntSchets) //1.2.1. если все счета, у которых f_maininv такой же как и у счета, связанного с операцией имеют тот же признак возмещаемости 
                      if($cntVozm == $cntSchets) //1.2.1. если все счета, у которых f_maininv такой же как и у счета, связанного с операцией имеют тот же признак возмещаемости 
                        {
                        $ki = Array('curtbl'=>17,'curidx'=>$row3['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в счете, связанным с операцией
                        editRowTbl($ki);
                        // Ищем агрегированный счет s.f_id=".$row3['f_maininv']
                        $sql = "select s.f_id,s.f_maininv,s.f_ismaininv,s.f_num,s.f_dt
                                  from veda_schets s
                                 where s.f_type=1 and s.f_id=".$row3['f_maininv'];
                        $res5 = $dbh->query($sql);
                        if($row5 = $res5->fetch(PDO::FETCH_ASSOC))
                          {// 1.2.1.2. меняем агрегированный счет(id=f_maininv)
                          $ki = Array('curtbl'=>17,'curidx'=>$row5['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в агрегированном счете
                          editRowTbl($ki);
                          // 1.2.1.3. выгружаем агрегированный счет(id=f_maininv)
                          $retval = json_decode(expFinDocTo1C(1,$row5['f_id']),true); // Выгружаеем заново счет в 1С
                          $expmsg = ""; 
                          if($retval[0] == true) {$expmsg = "Попытка выгрузки счета в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                          elseif($retval[0] == false) {$expmsg = "Попытка выгрузки счета в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                          
                          mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по счету № ".$row5['f_num']." от ".$row5['f_dt'],
                          " Изменился тип возмещаемости по счету № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=17&obid=".$row5['f_id']."\">Ссылка на счет</a><br>".$expmsg,"",35,$row['f_id']);
                          //" Изменился тип возмещаемости по счету № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=17&obid=".$row5['f_id']."\">Ссылка на счет</a><br>".$expmsg,"",17,$row5['f_id']);
                          }
                        }
                      else // если не все счета, у которых f_maininv такой же как и у счета, связанного с операцией, имеют тот же признак возмещаемости
                        {  // 1.2.2.1. меняем счет, связанный с операцией аналогично п. 1.1.1.
                        $ki = Array('curtbl'=>17,'curidx'=>$row3['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в счете, связанным с операцией
                        editRowTbl($ki);
                        } 
                      }
                    }  
                  // 2. Обрабатываем закрывающие документы
                  //2.1. с типом 10 (Реализация (акты, накладные)) - он создается в системе. Связан с операцией аналогично Счетам п.1.1. и 1.2.
                  $sql = "select a.f_id,a.f_mainakt,a.f_ismainakt,a.f_num,a.f_dt,a.f_sum,a.f_vozm 
                            from veda_akts a
                          where a.f_type=10 and a.f_operid=".$row['f_id'];
                  $res3 = $dbh->query($sql);
                  while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                    { 
                    if(($row3['f_mainakt'] == 0) && ($row3['f_ismainakt'] == 0))// && ($row2['f_status'] <> 6)) 
                      {
                      if((strtotime($row3['f_dt']) < strtotime($dtCloseBP)) && ($specStatus <> 6)) // если период закрыт, но спецификация открыта, просто уведомляем
                        { 
                        mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по операции, к которой привязан закр. документ № ".$row3['f_num']." от ".$row3['f_dt'],
                        " Изменился тип возмещаемости по операции, к которой привязан закр. документ № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row3['f_id']."\">Ссылка на документ</a><br>".$expmsg,"",35,$row['f_id']);
                        //" Изменился тип возмещаемости по операции, к которой привязан закр. документ № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row3['f_id']."\">Ссылка на документ</a><br>".$expmsg,"",83,$row3['f_id']);
                        }
                      elseif((strtotime($row3['f_dt']) > strtotime($dtCloseBP)) && ($specStatus <> 6)) // если период открыт, спецификация открыта, меняем возмещаемость в акте и перевыгружаем в 1С
                        {
                        $ki = Array('curtbl'=>83,'curidx'=>$row3['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в закр. документе
                        editRowTbl($ki);
                        $retval = json_decode(expFinDocTo1C(10,$row3['f_id']),true); // Выгружаеем заново закр.документ в 1С
                        $expmsg = ""; 
                        if($retval[0] == true) {$expmsg = "Попытка выгрузки закр. документа в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                        elseif($retval[0] == false) {$expmsg = "Попытка выгрузки закр. документа в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                        mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по документу № ".$row3['f_num']." от ".$row3['f_dt'],
                        " Изменился тип возмещаемости по документу № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row3['f_id']."\">Ссылка на документ</a><br>".$expmsg,"",35,$row['f_id']);
                        //" Изменился тип возмещаемости по документу № ".$row3['f_num']." от ".$row3['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row3['f_id']."\">Ссылка на документ</a><br>".$expmsg,"",83,$row3['f_id']);
                        }
                      }
                    elseif(($row3['f_mainakt'] > 0) ) // Если акт входит в состав агрегированного, ищем все составные части агрегированного акта
                      {
                      if((strtotime($row3['f_dt']) > strtotime($dtCloseBP)) && ($specStatus <> 6)) // если период открыт, спецификация открыта
                        { 
                        $cntAkts = 0;
                        $cntMainakt = 0;
                        $cntVozm = 0;
                        $sql = "select a.f_id,a.f_mainakt,a.f_ismainakt,a.f_num,a.f_dt,a.f_vozm
                                  from veda_akts a
                                where a.f_type=10 and a.f_mainakt=".$row3['f_mainakt'];
                        $res4 = $dbh->query($sql);
                        while($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                          {
                          $cntAkts++;
                          //if($row4['f_mainakt'] == $row3['f_mainakt']) {$cntMainakt++;}
                          if($row4['f_vozm'] == $row3['f_vozm']) {$cntVozm++;}
                          }
                        // 2.1.2. п. 6.3. - если акт по операции единственный в агрегированном, то аналогично п. 1.2.1. и подпунктам
                        //if(($cntAkts == 1) && ($cntMainakt == 1)) 
                        if($cntAkts == 1) 
                          {
                          //1.2.1.1. меняем акт, связанный с операцией аналогично п. 1.1.1.
                          $ki = Array('curtbl'=>83,'curidx'=>$row3['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в закр. документе
                          editRowTbl($ki);
                          // Ищем агрегирующий акт
                          $sql = "select a.f_id,a.f_mainakt,a.f_ismainakt,a.f_num,a.f_dt
                                    from veda_akts a
                                  where a.f_type=10 and a.f_id=".$row3['f_mainakt'];
                          $res5 = $dbh->query($sql);
                          if($row5 = $res5->fetch(PDO::FETCH_ASSOC))
                            {//1.2.1.2. меняем агрегированный акт(id=f_mainakt), аналогично п.1.1.1.
                            $ki = Array('curtbl'=>83,'curidx'=>$row5['f_id'],'f_vozm'=>$row['f_isvozm']); // Меняем возмещаемость в агрегированном акте
                            editRowTbl($ki);
                            //1.2.1.3. выгружаем агрегированный акт(id=f_mainakt), аналогично п.1.1.2.
                            $retval = json_decode(expFinDocTo1C(10,$row5['f_id']),true); // Выгружаеем заново акт в 1С
                            $expmsg = ""; 
                            if($retval[0] == true) {$expmsg = "Выгрузка акта в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                            elseif($retval[0] == false) {$expmsg = "Выгрузка акта в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                            
                            mSendMail($_SESSION['loginid'],14,0,"Изменился тип возмещаемости по акту № ".$row5['f_num']." от ".$row5['f_dt'],
                            " Изменился тип возмещаемости по акту № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row5['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",35,$row['f_id']);
                            //" Изменился тип возмещаемости по акту № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row5['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",83,$row5['f_id']);
                            }
                          }
                        //elseif(($cntAkts <> $cntMainakt) && ($cntAkts > 1)) //2.1.3. п. 6.3. - если акт по операции, не единснтвенный в агрегированном
                        elseif($cntAkts > 1) //2.1.3. п. 6.3. - если акт по операции, не единснтвенный в агрегированном
                          {
                          //2.1.3.1. удаляем акт по операции
                          $ki  = Array('curtbl'=>83,'curidx'=>$row3['f_id']); 
                          $retval = json_decode(delRowTbl($ki),true);
                          //2.1.3.1. у агрегированного акта уменьшаем сумму
                          // Ищем агрегирующий акт
                          $sql = "select a.f_id,a.f_mainakt,a.f_ismainakt,a.f_num,a.f_dt,a.f_sum
                                    from veda_akts a
                                  where a.f_type=10 and a.f_id=".$row3['f_mainakt'];
                          $res5 = $dbh->query($sql);
                          if($row5 = $res5->fetch(PDO::FETCH_ASSOC))
                            {
                            $aktsum = floatval($row5['f_sum']) - floatval($row3['f_sum']);
                            $ki = Array('curtbl'=>83,'curidx'=>$row5['f_id'],'f_sum'=>$aktsum); // Уменьшаем сумму в агрегированном акте
                            editRowTbl($ki);
                            //2.1.3.2. перевыгружаем агрегирвоанный акт в 1С
                            $retval = json_decode(expFinDocTo1C(10,$row5['f_id']),true); // Выгружаеем заново акт в 1С
                            $expmsg = ""; 
                            if($retval[0] == true) {$expmsg = "Выгрузка акта в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                            elseif($retval[0] == false) {$expmsg = "Выгрузка акта в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                            mSendMail($_SESSION['loginid'],14,0,"Изменилась сумма по акту № ".$row5['f_num']." от ".$row5['f_dt'],
                            " Изменилась сумма по акту № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row5['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",35,$row['f_id']);
                            //" Изменилась сумма по акту № ".$row5['f_num']." от ".$row5['f_dt'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row5['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",83,$row5['f_id']);
                            }
                          //2.1.3.3. создаем акт по операции
                          // Вытащим все параметры по текущей операции
                          //$sql = "select * from veda_spec_invoices where f_id=".$row['f_id'];
                          //$res6 = $dbh->query($sql);
                          //if($row6 = $res6->fetch(PDO::FETCH_ASSOC))
                          //  {
                          makeOper($row['f_dogtype'],$row['f_specid'],"","",0,$row['f_sum'],$row['f_val'],$row['f_nds'],$row['f_isvozm'],"",
                                   "","",0,"",$row['f_id'],$row['f_type_oper'],$row['f_contrid'],$row['f_orgid'],$row['f_dogid'],0,"",
                                   0,1,10,$row['f_contraccid'],$row['f_orgaccid'],"","",$row['f_bdrarticle'],"",1,
                                   "","",2,"",0,$row['f_addnds'],0,0,0,"0000-00-00","0000-00-00","");

/*                          makeOper($row6['f_dogtype'],$row6['f_specid'],"","",0,$row6['f_sum'],$row6['f_val'],$row6['f_nds'],$row6['f_isvozm'],"",
                                   "","",0,"",$row6['f_id'],$row6['f_type_oper'],$row6['f_contrid'],$row6['f_orgid'],$row6['f_dogid'],0,"",
                                   0,1,10,$row6['f_contraccid'],$row6['f_orgaccid'],"","",$row6['f_bdrarticle'],"",1,
                                   "","",2,"",0,$row6['f_addnds'],0,0,0,"0000-00-00","0000-00-00","");
*/
                          //2.1.3.4. выгружаем новый созданный акт в 1С 
                          // Для этого найдем акт по связке с операцией
                          $sql = "select a.f_id,a.f_num,a.f_dt 
                                    from veda_akts a, veda_spec_invoices si 
                                   where a.f_operid=".$row['f_id'];  //$row2['f_id']
                          $res7 = $dbh->query($sql);
                          if($row7 = $res7->fetch(PDO::FETCH_ASSOC))
                            {
                            $retval = json_decode(expFinDocTo1C(10,$row7['f_id']),true); // Выгружаем новый акт в 1С
                            $expmsg = ""; 
                            if($retval[0] == true) {$expmsg = "Выгрузка акта в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                            elseif($retval[0] == false) {$expmsg = "Выгрузка акта в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                            mSendMail($_SESSION['loginid'],14,0,"Создан новый акт № ".$row7['f_num']." от ".$row7['f_dt']." по операции ".$row['f_id'],
                            " Создан новый акт № ".$row7['f_num']." от ".$row7['f_dt']." по операции ".$row['f_id'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row7['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",35,$row['f_id']);
                            //" Создан новый акт № ".$row7['f_num']." от ".$row7['f_dt']." по операции ".$row6['f_id'].", <a href=\"".$redirect_uri."?pgid=83&obid=".$row7['f_id']."\">Ссылка на акт</a><br>".$expmsg,"",83,$row7['f_id']);
                            }
                          }
                        }
                      } //~ elseif($row3['f_mainakt'] > 0) // Если акт входит в состав агрегированного, ищем все составные части агрегированного акта
                    }  
                  //~ 2.1. с типом 10 (Реализация (акты, накладные)) - он создается в системе. Связан с операцией аналогично Счетам п.1.1. и 1.2.
                  //2.2. с типом 7 (Поступления (акты, накладные)) - он приходит от поставщика, связан с операцией через 
                  // sdid 1566
                  //$sql = "select a.f_id,a.f_num,a.f_dt 
                  //          from veda_akts a, veda_akts_details ad, veda_akts_details_opers ado 
                  //        where a.f_type=7 and a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=".$row['f_id'];
                  $sql = "select a.f_id,a.f_num,a.f_dt,s.f_status 
                          from veda_akts a, veda_akts_details ad, veda_akts_details_opers ado, veda_spec_invoices si, veda_specs s 
                          where a.f_type=7 and a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and si.f_id=ado.f_operid and s.f_id=si.f_specid and 
                            ado.f_operid=" . $row['f_id'];
                  // ~ sdid 1566
                  $res3 = $dbh->query($sql);
                  while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                    { 
                    if((strtotime($row3['f_dt']) > strtotime($dtCloseBP)) && ($specStatus <> 6)) // если период открыт, спецификация открыта, перевыгружаем в 1С
                      {
                      //2.2.4. Если изменяется возмещаемость у операции 2.2.1., то повторно выгружаем в 1С закрывающий документ 2.2.3.                                   
                      $retval = json_decode(expFinDocTo1C(7,$row3['f_id']),true); // Выгружаеем заново акт в 1С
                      $expmsg = ""; 
                      if($retval[0] == true) {$expmsg = "Выгрузка закрывающего документа в 1С: удачно.<br>Сообщение системы: ".$retval[1];}
                      elseif($retval[0] == false) {$expmsg = "Выгрузка закрывающего документа в 1С: неудачно.<br>Сообщение системы: ".$retval[1];}
                      // sdid 1566
                      if ($row3['f_status']==6 || $row3['f_status']==8)
                        {
                        mSendMail($_SESSION['loginid'], 14, 0, "Выгрузка закрывающего документа № " . $row3['f_num'] . " от " . $row3['f_dt'] . " в 1С",
                            " Выгрузка закрывающего документа № " . $row3['f_num'] . " от " . $row3['f_dt'] . " в 1С" . ", <a href=\"" . $redirect_uri . "?pgid=83&obid=" . $row3['f_id'] . "\">Ссылка на закрывающий документ</a><br>" . $expmsg, "", 35, $row['f_id']);
                        }
                      // ~ sdid 1566
                      //" Выгрузка закрывающего документа № ".$row3['f_num']." от ".$row3['f_dt']." в 1С".", <a href=\"".$redirect_uri."?pgid=83&obid=".$row3['f_id']."\">Ссылка на закрывающий документ</a><br>".$expmsg,"",83,$row3['f_id']);
                      }
                    }
                  //~ 2.2. с типом 7 (Поступления (акты, накладные)) - он приходит от поставщика

                  //3. Банковские выписки. (пункт 8)
                  //3.2. Если изменяется возмещаемость у операции 3.1.1., то повторно выгружаем в 1С банковскую выписку 
                  $sql = "select s.f_id, s.f_kod1C, s.f_grnd, s.f_dt1C 
                            from veda_acchist s,veda_acchist_docs d 
                          where s.f_id=d.f_acchistid and d.f_doctype=3 and d.f_docid=".$row['f_id'];
                  $res3 = $dbh->query($sql);
                  while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                    { 
                    $retval = json_decode(expAccHist1DocIDTo1C($row3['f_id']),true); // Выгружаеем заново банковскую выписку в 1С
                    $expmsg = ""; 
                    if($retval[0] == true) {$expmsg = "Выгрузка банковской выписки в 1С: удачно.<br>Сообщение системы: <table>".$retval[1]."</table>";}
                    elseif($retval[0] == false) {$expmsg = "Выгрузка банковской выписки в 1С: неудачно.<br>Сообщение системы: <table>".$retval[1]."</table>";}
                    // sdid 1566
                    // mSendMail($_SESSION['loginid'], 14, 0, "Выгрузка банковской выписки № " . $row3['f_kod1C'] . " от " . $row3['f_dt1C'] . " в 1С",
                    mSendMail($_SESSION['loginid'], 52, 0, "Выгрузка банковской выписки № " . $row3['f_kod1C'] . " от " . $row3['f_dt1C'] . " в 1С",
                    " Выгрузка банковской выписки № ".$row3['f_kod1C']." от ".$row3['f_dt1C']." в 1С"."<br>Назначение: ".$row3['f_grnd'].",<br> <a href=\"".$redirect_uri."?pgid=84&obid=".$row3['f_id']."\">Ссылка на выписку</a><br>".$expmsg,"",35,$row['f_id']);
                    //" Выгрузка банковской выписки № ".$row3['f_kod1C']." от ".$row3['f_dt1C']." в 1С"."<br>Назначение: ".$row3['f_grnd'].",<br> <a href=\"".$redirect_uri."?pgid=84&obid=".$row3['f_id']."\">Ссылка на выписку</a><br>".$expmsg,"",84,$row3['f_id']);
                    }
                  //~ //3. Банковские выписки. (пункт 8)
//~sdid 246
                  }
                //~sdid - 882
                // sdid 2504
                if(count($no_further_update) > 0)
                  {
                  $to_ignore = implode(',', $no_further_update);
                  }
                $new_sum    = $row['f_sum'];
                $new_invcom = $row['f_invcom'];
                $new_val    = $row['f_val'];
                $new_wloans = $row['f_wloans'];//sdid3213
                $update_sum    = false;
                $update_invcom = false;
                $update_val    = false;
                $update_wloans = false;//sdid3213
                $to_update = ['curtbl' => 35];
                if(abs($osum - $new_sum) > PHP_FLOAT_EPSILON)
                  {
                  $update_sum = true;
                  $to_update['f_sum'] = $new_sum;
                  }
                if(strcmp($oinvcom, $new_invcom) !== 0)
                  {
                  $update_invcom = true;
                  $to_update['f_invcom'] = $new_invcom;
                  }
                if($oval!=$new_val)
                  {
                  $update_val = true;
                  $to_update['f_val'] = $new_val;
                  }
                //sdid3213
                if($owloans!=$new_wloans)
                  {
                  $update_wloans = true;
                  $to_update['f_wloans'] = $new_wloans;
                  $sqlwloanslinks = "select lsi.f_id from ".DBPref."spec_invoices lsi where lsi.f_parentid=".$row['f_id']." and (select count(*) from ".DBPref."specinv_links where f_specinv_id=lsi.f_id)>0";
                  //error_log("\n\n$sqlwloanslinks\n\n",0);
                  $conn = $dbh->query($sqlwloanslinks);
                  while($rowwloanslinks = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $to_update_linksi = ['curtbl' => 35,'curidx'=>$rowwloanslinks['f_id'],'f_wloans'=>$new_wloans];
                    editRowTbl($to_update_linksi);
                    }
                  $sqlwloanslinks = "select lsi.f_id from ".DBPref."spec_invoices lsi where lsi.f_parentid=".$row['f_id']." and (select count(*) from ".DBPref."specinv_links where f_specinv_link=".$row['f_id'].")>0";
                  //error_log("\n\n$sqlwloanslinks\n\n",0);
                  $conn = $dbh->query($sqlwloanslinks);
                  while($rowwloanslinks = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $to_update_linksi = ['curtbl' => 35,'curidx'=>$rowwloanslinks['f_id'],'f_wloans'=>$new_wloans];
                    editRowTbl($to_update_linksi);
                    }
                  }
                if($update_invcom || $update_sum || $update_val ||$update_wloans)
                //~sdid3213
                  {
                  $sqlSpecinvLinks = "SELECT DISTINCT f_specinv_link FROM ".DBPref."specinv_links WHERE f_specinv_link > 0 AND f_specinv_link NOT IN ($to_ignore) AND f_specinv_id=$curidx";
                  $conn = $dbh->query($sqlSpecinvLinks);
                  while ($rowSpecinvLinks = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $f_specinv_link = $rowSpecinvLinks['f_specinv_link'];
                    $no_further_update[] = $f_specinv_link;
                    $to_update['curidx'] = $f_specinv_link;
                    $to_update['no_further_update'] = $no_further_update;
                    editRowTbl($to_update);
                    }
                  $sqlSpecinvLinks = "SELECT DISTINCT f_specinv_id FROM ".DBPref."specinv_links WHERE f_specinv_id > 0 AND f_specinv_id NOT IN ($to_ignore) AND f_specinv_link=$curidx";
                  $conn = $dbh->query($sqlSpecinvLinks);
                  while ($rowSpecinvLinks = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $f_specinv_id = $rowSpecinvLinks['f_specinv_id'];
                    $no_further_update[] = $f_specinv_id;
                    $to_update['curidx'] = $f_specinv_id;
                    $to_update['no_further_update'] = $no_further_update;
                    editRowTbl($to_update);
                    }
                  }
                //~sdid2504
                }
              //sdid3342
              if(($obdrarticle!=$row['f_bdrarticle']))//изменился БДР
                {
                $sqlbdr = "select s84.f_num from ".DBPref."spr s84, ".DBPref."spr s85, ".DBPref."spr s86 
                           where s84.f_type=84 and s85.f_type=85 and s86.f_type=86 and s84.f_num=s85.f_dopprint and s85.f_num=s86.f_uslint and 
                             s86.f_num=:bdr";
                $resbdr = $dbh->prepare($sqlbdr);
                $resbdr->bindParam(':bdr',$row['f_bdrarticle'],PDO::PARAM_INT);
                $resbdr->execute();
                if($rowbdr = $resbdr->fetch(PDO::FETCH_ASSOC))
                  {
                  if($rowbdr['f_num']==1)
                    {
                    $sqlbdr = "select f_id from ".DBPref."spec_invoices_sources where f_operid=:id and f_sum>0";
                    $resbdr = $dbh->prepare($sqlbdr);
                    $resbdr->bindParam(':id',$curidx,PDO::PARAM_INT);
                    $resbdr->execute();
                    while($rowbdr = $resbdr->fetch(PDO::FETCH_ASSOC))
                      {
                      $bdrs_ar = array('curtbl'=>350, 'curidx'=>$rowbdr['f_id'], 'f_sum'=>0);
                      editRowTbl($bdrs_ar);
                      }
                    }
                  }

                }
              //~sdid3342
              //sdid 2377
              // Изменилось значение поля "Корректировка"
              if($ocorrerctid != $row['f_correctid'])
                {
                $co  = new Corrects("corrects", $dbh);            // корректировки
                $coo = new CorrectsOpers("corrects_opers", $dbh); // операции по корректировкам
                if($row['f_correctid'] == 0) // удаляем операцию из корректировки
                  { // ЧТО ЖЕ, ВЕЗУЛЬТАТЕ КОНСУЛЬТАЦИЙ  - ЭТОТ СЛУЧАЙ НЕВОЗМОЖЕН, блокируем
                  //$coo_ar = array('curtbl'=>291, 'curidx'=>$curidx);
                  //$coo_res = $coo->delRowTbl($dbh,$ar,"");            // ПОКА ТАК. БЕЗ СООБЩЕНИЙ
                  }
                elseif($row['f_correctid'] == (-1)) // создаем новую корректировку, включаем туда операцию
                  {
                  // проверим, включениа ли операция в какую-либо корректировку, а также статус корректировки
                  $curoperid = $curidx;
                  $sql = "select c.f_status, c.f_num, co.f_id correctoperid, c.f_id correctid
                            from veda_corrects_opers co, veda_corrects c 
                           where co.f_correctoperid=:curoperid and c.f_id=co.f_correctid";
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curoperid',$curoperid,PDO::PARAM_INT);
                  $res2->execute();
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC)) // если операция уже входит в какую-либо корректировку
                    {
                    // меняем id корректировки на новое значение, создав новую корректировку
                    $correctid = 0;
                    $co_ar = array('curtbl'=>290, 'f_specid'=>$row['f_specid']);
                    $co_res = $co->addRowTbl($dbh,$co_ar,"");
                    if($co_res[0]==true) {$correctid = $co_res[2];}
                    else {}
                    if($correctid>0) // если корректировка создана, включаем в нее текущую операцию (edit)
                      {
                      $coo_ar = array('curtbl'=>291, 'curidx'=>$row2['correctoperid'], 'f_correctid'=>$correctid);
                      $coo_res = $coo->editRowTbl($dbh,$coo_ar,"");
                      // обновляем значение в текущей операции в поле f_correctid и f_outbuhperiod
                      $outbuhperiod = 0;
                      if($row2['f_num']>0) {$outbuhperiod=1;}
                      $oper_ar = array('curtbl'=>35, 'curidx'=>$curidx, 'f_correctid'=>$correctid, 'f_outbuhperiod'=>$outbuhperiod);
                      $oper_res = editRowTbl($oper_ar);
                      }
                    }
                  else // иначе, если операция не входит в какую-либо корректировку, создаем новую корректировку и включаем в нее операцию
                    {
                    $correctid = 0;
                    $co_ar = array('curtbl'=>290, 'f_specid'=>$row['f_specid']);
                    $co_res = $co->addRowTbl($dbh,$co_ar,"");
                    if($co_res[0]==true) {$correctid = $co_res[2];}
                    if($correctid>0) // если корректировка создана, включаем в нее текущую операцию
                      {
                      $coo_ar = array('curtbl'=>291, 'f_correctid'=>$correctid, 'f_correctoperid'=>$curidx);
                      $coo_res = $coo->addRowTbl($dbh,$coo_ar,"");
                      // определим f_num новой корректировки
                      $sql  = "select f_num from veda_corrects where f_id=:correctid";
                      $res3 = $dbh->prepare($sql);
                      $res3->bindParam(':correctid',$correctid,PDO::PARAM_INT);
                      $res3->execute();
                      $outbuhperiod = 0;
                      if($row3 = $res3->fetch(PDO::FETCH_ASSOC)) {if($row3['f_num']>0) {$outbuhperiod=1;}}
                      // обновляем значение в текущей операции в поле f_correctid и f_outbuhperiod
                      $oper_ar = array('curtbl'=>35, 'curidx'=>$curidx, 'f_correctid'=>$correctid, 'f_outbuhperiod'=>$outbuhperiod);
                      $oper_res = editRowTbl($oper_ar);
                      }
                    }
                  }
                elseif($row['f_correctid'] > 0) // иначе, включаем операцию в выбранную корректировку
                  {
                  // проверим, включена ли операция в какую-либо корректировку, а также статус корректировки
                  $curoperid = $curidx;
                  $sql = "select c.f_status, c.f_num, co.f_id correctoperid, c.f_id correctid
                            from veda_corrects_opers co, veda_corrects c 
                           where co.f_correctoperid=:curoperid and c.f_id=co.f_correctid";
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':curoperid',$curoperid,PDO::PARAM_INT);
                  $res2->execute();
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC)) // если операция уже входит в какую-либо корректировку
                    {
                    // меняем id корректировки на новое значение
                    $coo_ar = array('curtbl'=>291, 'curidx'=>$row2['correctoperid'], 'f_correctid'=>$row['f_correctid']);
                    $coo_res = $coo->editRowTbl($dbh,$coo_ar,"");
                    $outbuhperiod = 0;
                    if($row2['f_num']>0) {$outbuhperiod=1;}
                    $oper_ar = array('curtbl'=>35, 'curidx'=>$curidx, 'f_outbuhperiod'=>$outbuhperiod);
                    $oper_res = editRowTbl($oper_ar);
                    }
                  else // иначе, включаем операцию в выбранную корректировку
                    {
                    $coo_ar = array('curtbl'=>291, 'f_correctid'=>$row['f_correctid'], 'f_correctoperid'=>$curidx);
                    $coo_res = $coo->addRowTbl($dbh,$coo_ar,"");
                    // определим f_num корректировки
                    $sql  = "select f_num from veda_corrects where f_id=:correctid";
                    $correctid = $row['f_correctid'];
                    $res3 = $dbh->prepare($sql);
                    $res3->bindParam(':correctid',$correctid,PDO::PARAM_INT);
                    $res3->execute();
                    $outbuhperiod = 0;
                    if($row3 = $res3->fetch(PDO::FETCH_ASSOC)) {if($row3['f_num']>0) {$outbuhperiod=1;}}
                    // обновляем значение в текущей операции в поле f_outbuhperiod
                    $oper_ar = array('curtbl'=>35, 'curidx'=>$curidx, 'f_outbuhperiod'=>$outbuhperiod);
                    $oper_res = editRowTbl($oper_ar);
                    }
                  }
                //sdid 3010
                // Обновляем ставку НДС при изменении параметров операции
                // Если сняли галку "Ручной НДС" либо если галка снята и изменился один из параметров
                if( (($ohnds!=$row['f_hnds']) && ($row['f_hnds']==0)) ||
                    ( ($row['f_hnds']==0) &&    
                      (($oidoper    !=$row['f_idoper'])         ||       //{$ar = array_merge($ar,array('idoper'=>$row['f_idoper']));}
                       (strcmp($odttmcr,$row['f_dttmcr'])!=0)   ||       //{$ar = array_merge($ar,array('dt'    =>$row['f_dttmcr']));}
                       ($oisvozm    !=$row['f_isvozm'])         ||       //{$ar = array_merge($ar,array('invozm'=>$row['f_isvozm']));}
                       ($oorgid     !=$row['f_orgid'])          ||
                       ($ocontrid   !=$row['f_contrid'])        ||
                       ($obdrarticle!=$row['f_bdrarticle'])     ||
                       ($oparenttype!=$row['f_parenttype'])     ||
                       ($ospecid    !=$row['f_specid']))
                    )
                  )
                  {
                  $ar = Array('specinvid'=>$curidx);
                  $newnds = getNDS($ar);
                  $newnds = $newnds[0];//sdid3342
                  if($newnds>(-1))
                    {
                    $oper_ar = array('curtbl'=>35,'curidx'=>$curidx,'f_nds'=>$newnds);
                    $oper_res = editRowTbl($oper_ar);
                    }
                  /*if(!=$row['']) {$ar = array_merge($ar,array(''=>$row['']));}
                  if(!=$row['']) {$ar = array_merge($ar,array(''=>$row['']));}
                  if(!=$row['']) {$ar = array_merge($ar,array(''=>$row['']));}
                  if(!=$row['']) {$ar = array_merge($ar,array(''=>$row['']));}
                  if(!=$row['']) {$ar = array_merge($ar,array(''=>$row['']));}*/
                  }
               
            /*$oidoper     = $row1['f_idoper'];
            $odttmcr     = $row1['f_dttmcr'];
            $oisvozm     = $row1['f_isvozm'];  //$row1['']
            $oorgid      = $row1['f_orgid'];
            $ocontrid    = $row1['f_contrid'];
            $obdrarticle = $row1['f_bdrarticle'];
            $oparenttype = $row1['f_parenttype'];
            $ospecid     = $row1['f_specid'];
            $ohnds       = $row1['f_hnds'];*/



                //~sdid 3010
                }
              //~sdid 2377
              //sdid 3010
              if($onds!=$row['f_nds'])//если изменился НДС, то меняем их в связанных операциях specinv_links
                {
                $sqlSpecinvLinks = "select
                                      sl.f_specinv_id siid,(select f_nds from ".DBPref."spec_invoices where f_id=sl.f_specinv_id) sinds,
                                      sl.f_specinv_link slid,(select f_nds from ".DBPref."spec_invoices where f_id=sl.f_specinv_link) slnds
                                    FROM ".DBPref."specinv_links sl
                                    WHERE sl.f_specinv_id=$curidx or sl.f_specinv_link=$curidx";
                
                $conn = $dbh->query($sqlSpecinvLinks);
                while($rowSpecinvLinks = $conn->fetch(PDO::FETCH_ASSOC))
                  {
                  if(($rowSpecinvLinks['siid']!=$curidx)&&($rowSpecinvLinks['sinds']!=$row['f_nds']))
                    {
                    $to_update = ['curtbl' => 35,'curidx'=>$rowSpecinvLinks['siid'],'f_nds'=>$row['f_nds']];
                    }
                  if(($rowSpecinvLinks['slid']!=$curidx)&&($rowSpecinvLinks['slnds']!=$row['f_nds']))
                    {
                    $to_update = ['curtbl' => 35,'curidx'=>$rowSpecinvLinks['slid'],'f_nds'=>$row['f_nds']];
                    }
                  editRowTbl($to_update);
                  }
                }
              //~sdid 3010
              }
            elseif(strcmp($tblname,DBPref."spec_details")==0)//обрабатываем обновление товара
              {
              $smt = 0;$sval=643;
              $sql = "select sum((f_count*f_price)) smt,f_val,sum(f_price_all) sall from ".DBPref."spec_details where f_specid=(select f_specid from ".DBPref."spec_details where f_id=".$curidx.") group by f_specid";
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($smt>0){ $smt = $row['smt'];}
                else {$smt = $row['sall'];}
                $sval=$row['f_val'];}
              //echo $smt."|".$sval;
              $sql = "select f_status,f_id from ".DBPref."ensures where f_objtype=2 and f_objid in (select f_specid from ".DBPref."spec_details where f_id=".$curidx.")";
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                //echo $row['f_status']."|";
                if($row['f_status']==0)
                  {
                  $ki = Array('curtbl'=>54,'curidx'=>$row['f_id'],'f_enssump'=>$smt,'f_enssumpval'=>$sval);
                  editRowTbl($ki);
                  }
                }
              }
            //sdid1583
            elseif(strcmp($tblname,DBPref."acchist_docs")==0)//После изменения ПП-докуменеты
              {
              $sql = "select h.f_sum hsum,h.f_dt1C hdt,h.f_val hval,d.f_doctype,d.f_docid,d.f_acchistid,d.f_curs,h.f_type,d.f_clssum 
                        ,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) ovaln 
                      from $tblname d,".DBPref."acchist h where h.f_id=d.f_acchistid and d.f_id=$curidx";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($row['f_doctype']==3)
                  {
                  if($oclssum!=$row['f_clssum'])//если изменилась закрывающая сумма 
                    {
                    $sql = "select s.f_id,s.f_parentid
                            from ".DBPref."spec_invoices s where s.f_id=".$row['f_docid'];
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row1['f_parentid']>0)
                        {
                        $sql = "select f_id,f_idoper from veda_spec_invoices where f_idoper=388 and f_parentid=".$row1['f_id'];
                        //echo "$sql";
                        $res5 = $dbh->query($sql);
                        if($row5 = $res5->fetch(PDO::FETCH_ASSOC))//операция курсовой разницы по переводу
                          {
                          $sql = "select f_id,f_idoper from ".DBPref."spec_invoices where f_idoper=387 and (f_id=".$row1['f_parentid']." or 
                                    f_id=(select f_parentid from ".DBPref."spec_invoices where f_id=".$row1['f_parentid']."))";
                          $res2 = $dbh->query($sql);
                          if($row2 = $res2->fetch(PDO::FETCH_ASSOC))//если изменили сумму связки документа выписки с операцией "Оплата товара. Поручение на валютный перевод", порожденной "Товар. В ДОЛГ. Оплата", то изменяем корректировку
                            {
                            if($row2['f_idoper']==387)
                              {
                              $sql = "select si.*, 
                                        case
                                          when (select max(f_dttmsrok) from ".DBPref."spec_invoices 
                                                where f_idoper=386 and f_parentid=si.f_id) IS NOT NULL then 
                                            (select max(f_dttmsrok) from ".DBPref."spec_invoices 
                                             where f_idoper=386 and f_parentid=si.f_id)
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' then s.f_perpravdt
                                          else si.f_dttmcr
                                        end dtfrom,
                                        case
                                          when (select max(f_dttmsrok) from ".DBPref."spec_invoices 
                                                where f_idoper=386 and f_parentid=si.f_id) IS NOT NULL then 
                                            'курс ЦБ на дату последней переоценки'
                                          when s.f_perpravdt is not null and s.f_perpravdt<>'0000-00-00' then 'курс ЦБ на дату ППС'
                                          else 'курс ЦБ на дату возникновения долга'
                                        end dttext
                                      from ".DBPref."spec_invoices si, ".DBPref."specs s 
                                      where s.f_id=si.f_specid and si.f_parenttype=2 and si.f_id=".$row2['f_id'];
                              //echo "$sql";
                              $res3 = $dbh->query($sql);
                              if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                                {
                                $sum_korr = round(round($row['f_clssum'],2)*(getcbrate($row['hval'],substr($row['hdt'],0,10)) - getcbrate($row['hval'], $row3['dtfrom'])),2);
                                $oprcomm = round($row['f_clssum'],2)." ".$row['ovaln']." курс ЦБ на дату перевода "
                                           .date("d.m.Y",strtotime(substr($row['hdt'],0,10)))." - ".getcbrate($row['hval'], substr($row['hdt'],0,10))." ".$row3['dttext']." "
                                           .date("d.m.Y",strtotime($row3['dtfrom']))." - ".getcbrate($row['hval'], $row3['dtfrom']).
                                           " Расчет курсовой разницы: ".round($row['f_clssum'],2)."*(".getcbrate($row['hval'], substr($row['hdt'],0,10))."-".getcbrate($row['hval'], $row3['dtfrom']).")=$sum_korr руб.";
                                //обновляем сумму связанной операции
                                $ki = Array('curtbl'=>35,'curidx'=>$row5['f_id'],'f_sum'=>$sum_korr,'f_com'=>$oprcomm);
                                $kr = json_decode(editRowTbl($ki));
                                if($kr[2]>0)
                                  {
                                  $sql = "select f_id,f_dtsum,f_ktsum from ".DBPref."difrate_docs where f_operid=".$row5['f_id'];
                                  //echo "$sql";
                                  $res4 = $dbh->query($sql);
                                  if($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                                    {
                                    if($sum_korr<0){$sum_korr=(-1)*$sum_korr;}
                                    $dksum="f_dtsum";
                                    if($row4['f_ktsum']>0){$dksum="f_ktsum";}
                                    $ki = Array('curtbl'=>191,'curidx'=>$row4['f_id'],$dksum=>$sum_korr,'f_grnd'=>$oprcomm);
                                    $kr = json_decode(editRowTbl($ki));
                                    }
                                  }
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            //~sdid1583
            elseif(strcmp($tblname,DBPref."bdr")==0)//Бюджет
              {
              $sql = "select f_bdryear from $tblname where f_id=$curidx";
              //echo $sql;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {recalcBDRPlanY($dbh,$row['f_bdryear'],"p");}//пересчитать плановые значения БДР при изменении записи
              }
            elseif(strcmp($tblname,DBPref."cash")==0)//касса
              {
              $dbh = dbconnect();
              $sql = "select f_idoper from ".DBPref."cash where f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($oidoper!=$row['f_idoper'])
                  {
                  $cspid  = 0;
                  $cptype = 0;
                  if($row['f_idoper']>0)
                    {
                    $sql = "select f_parenttype,f_specid from ".DBPref."spec_invoices where f_id=(select f_idoper from ".DBPref."cash where f_id=".$curidx.")";
                    $res = $dbh->query($sql);
                    if($row = $res->fetch(PDO::FETCH_ASSOC))
                      {
                      $cspid  = $row['f_specid'];
                      $cptype = $row['f_parenttype'];
                      }
                    }
                  $ki = Array('curtbl'=>67,'curidx'=>$curidx,'f_specid'=>$cspid,'f_dogtype'=>$cptype);
                  editRowTbl($ki);
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."dt")==0)//обрабатываем обновление информации о ДТ
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              //sdid 611
/*              $sql = "select dt.f_brokertype,dt.f_broker,dt.f_obespnopay,dt.f_upels,dt.f_uvedins,dt.f_statusdop,dt.f_dtcom,dt.f_oprtpreq,dt.f_id,dt.f_specid,
                        dt.f_status,
                        dt.f_sumst,dt.f_sumip,dt.f_sumsp,dt.f_sumnds,dt.f_sumndsval,dt.f_sumipval,dt.f_sumstval,dt.f_sumspval,".
                     "  dt.f_sumstf,dt.f_sumipf,dt.f_sumspf,dt.f_sumndsf,dt.f_sumndsfval,dt.f_sumipfval,dt.f_sumstfval,dt.f_sumspfval,".
                     "  dt.f_zopltposreq,dt.f_zopltpreq, ".
                     "  d.f_contrid,d.f_orgid, ".
                     "  dt.f_provsum,dt.f_dtval,dt.f_provsumr,dt.f_bglink,dt.f_sumipktsf,dt.f_sumipktsfval,dt.f_sumspktsf,dt.f_sumspktsfval,dt.f_sumndsktsf,dt.f_sumndsktsfval, ".
                     "  (select f_contrid from ".DBPref."dogs where f_id=dt.f_bglink) bgcntr ".
                     "from $tblname dt,".DBPref."specs s,".DBPref."dogs d where d.f_id=s.f_dogid and s.f_id=dt.f_specid and dt.f_id=".$curidx;
*/
              // sdid 1553 + dt.f_operto
              $sql = "select dt.f_operto,dt.f_brokertype,dt.f_broker,dt.f_obespnopay,dt.f_upels,dt.f_uvedins,dt.f_statusdop,dt.f_dtcom,dt.f_oprtpreq,dt.f_id,dt.f_specid,dt.f_ufid,
                        dt.f_status,f_TDdt,s.f_subtype,
                        (select f_name from ".DBPref."spr where f_type=34 and f_num=dt.f_status) statusname,
                        dt.f_sumst,dt.f_sumip,dt.f_sumsp,dt.f_sumnds,dt.f_sumndsval,dt.f_sumipval,dt.f_sumstval,dt.f_sumspval,".
                     "  dt.f_sumstf,dt.f_sumipf,dt.f_sumspf,dt.f_sumndsf,dt.f_sumndsfval,dt.f_sumipfval,dt.f_sumstfval,dt.f_sumspfval,".
                     "  dt.f_zopltposreq,dt.f_zopltpreq, ".
                     "  d.f_contrid,d.f_orgid, ".
                     "  dt.f_provsum,dt.f_dtval,dt.f_provsumr,dt.f_bglink,dt.f_sumipktsf,dt.f_sumipktsfval,dt.f_sumspktsf,dt.f_sumspktsfval,dt.f_sumndsktsf,dt.f_sumndsktsfval, ".
                     "  (select f_contrid from ".DBPref."dogs where f_id=dt.f_bglink) bgcntr, ".
                     "  ifnull((select f_isourorg from ".DBPref."clients where f_id=dt.f_ufid),0) isourorg ".
                     "from $tblname dt,".DBPref."specs s,".DBPref."dogs d where d.f_id=s.f_dogid and s.f_id=dt.f_specid and dt.f_id=".$curidx;
              //~sdid 611
              //$ans=$ans.$sql."_";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($ostatusdop!=$row['f_statusdop'])//изменился доп статус информации о ДТ
                  {
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=101 and f_num=".$row['f_statusdop'];
                  //sdid - 164
                  //sdid 703 
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=101 and f_num=".$row['f_statusdop'];
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=101 and f_num=".$row['f_statusdop'];
                  //~sdid 703 
                  //~sdid - 164
                  //$ans=$ans.$sql;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      //$ans=$ans."|".$mgrp."_".$musr;
                      if($menomes==0){
                        //sdid - 164
                        //sdid 703
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 1, $curidx);}
                        else
                          {$subject = "В информации о ДТ изменен доп статус на ".$row1['f_name'];}
                        //~sdid 703
                        $postbody = "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ</a> изменен доп статус на \"".$row1['f_name']."\"<br>";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 1, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",61,$row['f_id']);
                        //~sdid - 164
                      }
                      }
                    }
                  if($row['f_statusdop']==3)//Выпуск с обеспечением(доп статус ОТО)
                    {
                    if($row['obespnopay']==0)
                      {
                      if(($row['f_bglink']==0)&&($row['f_provsum']>0))
                        {
                        //Финансирование. Списание обеспечения
                        $rvk = makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],$row['f_dtval'],0,1,"",
                                 "","",0,"",0,81,$row['f_contrid'],$row['f_orgid'],0,0,"",
                                 0,0,0,0,-5,"","",185,"",0,
                                 "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      elseif(($row['f_bglink']>0)&&($row['f_provsum']>0))
                        {
                        //Финансирование. Списание обеспечения
                        $rvk = makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],$row['f_dtval'],0,1,"",
                                 "","",0,"",0,81,$row['f_contrid'],$row['f_orgid'],$row['f_bglink'],0,"",
                                 0,0,0,0,-5,"","",185,"",0,
                                 "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      }
                    }
                  elseif($row['f_statusdop']==5)//ТС принята (доп статус ОТО)
                    {
                    if($row['obespnopay']==0)
                      {
                    if(($row['f_bglink']==0)&&($row['f_provsum']>0))
                      {
                      //Финансирование. Возврат обеспечения без БГ
                      $rvk = makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],$row['f_dtval'],0,1,"",
                               "","",0,"",0,82,$row['f_contrid'],$row['f_orgid'],0,0,"",
                               0,0,0,0,-5,"","",185,"",0,
                               "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      //echo $rvk;
                      }
                    elseif(($row['f_bglink']>0)&&($row['f_provsum']>0))
                      {
                      //Финансирование. Возврат обеспечения с БГ
                      $rvk = makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],$row['f_dtval'],0,1,"",
                               "","",0,"",0,82,$row['f_contrid'],$row['f_orgid'],$row['f_bglink'],0,"",
                               0,0,0,0,-5,"","",185,"",0,
                               "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      //echo $rvk;
                      }
                      }
                    }
                  elseif($row['f_statusdop']==6)//Корректировка ТС (доп статус ОТО)
                    {
                    if(($row['f_bglink']==0)&&($row['f_provsum']>0))
                      {
                      if($row['obespnopay']==0)
                        {
                      //Финансирование. Возврат обеспечения без БГ на ЕЛС
                      $rvk = makeOper(2,$row['f_specid'],"","",0,($row['f_provsum']-$row['f_sumipktsf']-$row['f_sumspktsf']-$row['f_sumndsktsf']),$row['f_dtval'],0,1,"",
                               "","",0,"",0,82,$row['f_contrid'],$row['f_orgid'],0,0,"",
                               0,0,0,0,-5,"","",185,"",0,
                               "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                      //echo $rvk;
                        }
                      }
                    elseif(($row['f_bglink']>0)&&($row['f_provsum']>0))
                      {
                      if($row['obespnopay']==0)
                        {
                      //Финансирование. Возврат обеспечения с БГ
                      $rvk = makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],$row['f_dtval'],0,1,"",
                               "","",0,"",0,82,$row['f_contrid'],$row['f_orgid'],$row['f_bglink'],0,"",
                               0,0,0,0,-5,"","",185,"",0,
                               "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      if($row['f_sumipktsf']>0)
                        {
                        makeOper(2,$row['f_specid'],"","",0,$row['f_sumipktsf'],$row['f_sumipktsfval'],0,1,"",
                                 "","",0,"",0,77,$row['f_contrid'],$row['f_orgid'],0,0,"",
                                 0,0,0,0,-5,"","",185,"",0,
                                 "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      if($row['f_sumspktsf']>0)
                        {
                        makeOper(2,$row['f_specid'],"","",0,$row['f_sumspktsf'],$row['f_sumspktsfval'],0,1,"",
                                 "","",0,"",0,98,$row['f_contrid'],$row['f_orgid'],0,0,"",
                                 0,0,0,0,-5,"","",185,"",0,
                                 "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      if($row['f_sumndsktsf']>0)
                        {
                        makeOper(2,$row['f_specid'],"","",0,$row['f_sumndsktsf'],$row['f_sumndsktsfval'],0,1,"",
                                 "","",0,"",0,79,$row['f_contrid'],$row['f_orgid'],0,0,"",
                                 0,0,0,0,-5,"","",185,"",0,
                                 "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                        }
                      //echo $rvk;
                      }
                    }
                  }
                //$ans=$ans.$sql."_";
                $noprtpreq = $row['f_oprtpreq'];
                //echo $ooprtpreq."|".$noprtpreq."|";
                if($ooprtpreq!=$noprtpreq)
                  {
                  if($noprtpreq==1)
                    {
                    $ki = Array('curtbl'=>173,'f_type'=>1,'f_objtype'=>15,'f_objid'=>$row['f_specid']);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      if($menomes==0){mSendMail($_SESSION['loginid'],20,0,"Запрошено согласование способа оплаты ТП",
                        "По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_specid']."\">спецификации</a> Запрошено <a href=\"".$redirect_uri."?pgid=173&obid=".$ar[2]."\">согласование</a> способа оплаты ТП","",0,0);}
                      }
                    }
                  }
                if(($ozopltpreq!=$row['f_zopltpreq'])&&($ozopltpreq==0))//установили - Запрос согл. оплаты ТП
                  {
                  $ki = Array('curtbl'=>18,'f_specid'=>$row['f_specid'],'f_status'=>1,
                              'f_amount'=>($row['f_sumst']+$row['f_sumip']+$row['f_sumnds']+$row['f_sumsp']),
                              'f_val'=>$row['f_sumstval'],'f_lnstype'=>2,'f_tdid'=>$row['f_id']);
                  // sdid 2707
                  // $ar = json_decode(addRowTbl($ki), true);
                  $FundingClass = new Funding('lns', $dbh);
                  $ar = $FundingClass->addRowTbl($dbh, $ki, "");
                  // ~ sdid 2707
                  if($ar[0]=="true")
                    {
                    if($menomes==0){mSendMail($_SESSION['loginid'],20,0,"Запрошено финансирование для оплаты ТП",
                      "По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_specid']."\">спецификации</a> запрошено ".
                      "<a href=\"".$redirect_uri."?pgid=18&obid=".$ar[2]."\">финансирование</a> оплаты ТП на суммы (Сбор: ".$row['f_sumst'].";Пошлина: ".$row['f_sumip'].";НДС: ".$row['f_sumnds'].";СП: ".$row['f_sumsp'].")","",0,0);}
                    }
                  }
                $nzopltposreq = $row['f_zopltposreq'];
                if($ozopltposreq!=$nzopltposreq)//установили - Запрос согласования оплаты ТП по заявелнию из ОС
                  {
                  if($nzopltposreq==1)
                    {
                    //echo $nzopltposreq."|";
                    $ki = Array('curtbl'=>173,'f_type'=>2,'f_objtype'=>15,'f_objid'=>$row['f_specid']);
                    $ar = json_decode(addRowTbl($ki), true);
                    if($ar[0]=="true")
                      {
                      //echo $menomes."|true|".$row['f_specid']."|".$ar[2]."|";
                      if($menomes==0){mSendMail($_SESSION['loginid'],20,0,"Запрошено согласование способа оплаты ТП по заявлению",
                        "По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_specid']."\">спецификации</a> Запрошено <a href=\"".$redirect_uri."?pgid=173&obid=".$ar[2]."\">согласование</a> способа оплаты ТП по заявлению","",0,0);}
                      }
                    }
                  }
                if(($row['f_uvedins']!=$ouvedins)&&($row['f_uvedins']==1))//установили признак уведомления страхования
                  {
                  //sdid 1546
                  $subject = "В информации о ДТ (статус - ".$row['statusname'].") установили признак уведомления отдела страхования ";
                  $postbody = "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ (статус - ".$row['statusname'].")</a> установили признак уведомления отдела страхования";
                  $sbsh = 0;
                  if($menomes==0) 
                    { 
                    $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 61, $row['f_id'], 151, 1, 11); 
                    if($sbsh == 0) 
                      { 
                      mSendMail($_SESSION['loginid'],8,0,$subject,$postbody,"",61,$row['f_id']);
                      }
                    }                     
                  //mSendMail($_SESSION['loginid'],8,0,"В информации о ДТ (статус - ".$row['statusname'].") установили признак уведомления отдела страхования ",
                  //      "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ (статус - ".$row['statusname'].")</a> установили признак уведомления отдела страхования","",61,$row['f_id']);
                  //~sdid 1546
                  //sdid - 934 - добавляем задачу о подготовке документов для бухгалтерии по страхованию
                  $linsid    = "";
                  $sql       = "select f_id from ".DBPref."ensures where f_id>0 and f_dtid=$curidx and f_dtid>0 ";
                  $res1      = $dbh->query($sql);
                  if($row1   = $res1->fetch(PDO::FETCH_ASSOC))
                    {$linsid = $row1['f_id'];}
                  else
                    {
                    $sql        = "select f_id from ".DBPref."ensures 
                                   where f_id>0 and f_objtype=2 and f_objid>0 and (f_objid in (select f_specid from ".DBPref."dt where f_id=$curidx) or
                                     f_objid in (select f_parentspecid from ".DBPref."specs where f_parentspecid>0 and f_id in 
                                     (select f_specid from ".DBPref."dt where f_id=$curidx and f_specid>0)))";
                    $res1       = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      if(strlen($linsid)>0){$linsid=$linsid.",";}
                      $linsid   = $linsid.$row1['f_id'];
                      }
                    }
                  if(strlen($linsid)>0)
                    {
                    $lains = array();
                    $pos   = strpos($linsid, ",");
                    if ($pos === false) {$lains[0]=$linsid;} 
                    else {$lains = explode(",", $linsid);}
                    $i=0;
                    while($i<count($lains))
                      {
                      $sql = "SELECT concat(ifnull(t.f_ensnum,''),'/',ifnull(t.f_ensdt,''),' (ИД ',t.f_id,')') insname 
                              FROM ".DBPref."ensures t
                              where t.f_id=".$lains[$i];
                      $res1       = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        $ki = Array('curtbl'=>254,'f_subjtype'=>2,'f_subjid'=>8,'f_objtype'=>54,'f_objid'=>$lains[$i],'f_start'=>date("Y-m-d"),'f_end'=>date('Y-m-d',strtotime("+1 days", strtotime(nextWorkDay(date("Y-m-d"),1)))),'f_calendarid'=>3,'f_status'=>3,'f_title'=>"Подготовка документов. Страхование ".$row1['insname'],'f_type'=>1,'f_isallday'=>1,'f_icon'=>8);
                        $ar = json_decode(addRowTbl($ki), true);
                        }
                      $i++;
                      }
                    }
                  //~sdid - 934
                  }
                $nstatus = $row['f_status'];
                if($nstatus!=$ostatus)//изменился статус информации о ДТ
                  {
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=34 and f_num=".$nstatus;
                  //sdid - 164
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=34 and f_num=".$nstatus;
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=34 and f_num=".$nstatus;
                  //~sdid 703
                  //~sdid - 164
                  //$ans=$ans.$sql;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      $ans=$ans."|".$mgrp."_".$musr;
                      if($menomes==0)
                        {
                        //sdid - 164
                        //sdid 703
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 1, $curidx);}
                        else
                          {$subject = "В информации о ДТ изменен статус на ".$row1['f_name'];}
                        //~sdid 703
                        $postbody = "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ</a> изменен статус на \"".$row1['f_name']."\"<br>";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 1, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",61,$row['f_id']);
                        //~sdid - 164
                        }
                      }
                    }
                  //sdid2381
                  //if($nstatus==17)//Выпуск разрешен/ДТ выпущена
                  //  {
                  //    //sdid 611
                  //    //if(in_array($row['f_ufid'], array(15,30,31))) // Если в поле "Декларант(гр.14 ДТ)" стоит НЕ ЮЛ организации,  то операция по списанию платежей не формируется
                  //    //$ans=$ans."|| isourorg = ".$row['isourorg']." ||";
                  //    if($row['isourorg'] == 1) // Если в поле "Декларант(гр.14 ДТ)" стоит НЕ ЮЛ организации,  то операция по списанию платежей не формируется
                  //      {
                  //    //~sdid 611
                  //       $kkk = 0;
                  //       //echo $row['f_sumnds']."_";
                  //       if($row['f_sumndsf']>0)
                  //         {
                  //         $sql = "select 
                  //                   ifnull((select sum(i.f_sum) from ".DBPref."spec_invoices i 
                  //                           where i.f_parenttype=2 and i.f_specid=".$row['f_specid']." and i.f_idoper=79),0) isum,
                  //                   ifnull((select sum(dt.f_sumndsf) from ".DBPref."dt dt where dt.f_specid=".$row['f_specid']."),0) dsum";
                  //         //echo $sql."|";
                  //         $res1 = $dbh->query($sql);
                  //         if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  //           {
                  //           if($row1['dsum']>$row1['isum'])
                  //             {
                  //             //echo $row['f_sumnds']."_";
                  //             makeOper(2,$row['f_specid'],"","",0,$row['f_sumndsf'],$row['f_sumndsfval'],0,1,"",
                  //                      "","",0,"",0,79,$row['f_contrid'],$row['f_orgid'],0,0,"",
                  //                      0,0,0,0,-5,"","",185,"",0,
                  //                      "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                  //             //$kkk = addOperRow(2,0,$row['f_specid'],45,2,"","",0,$row['f_sumnds'],$row['f_sumndsval'],0,1,"","","",0,"",0,79,$row['f_contrid'],$row['f_orgid'],0,0,0,0,0,0,0,17,0,"","",150,"");
                  //             //if($kkk>0)
                  //             //  {
                  //             //  $sql = "insert into ".DBPref."els (f_orgid            ,f_typepay,f_dtid          ,f_ppid,f_ppdt   ,f_outsum            ,f_ppsum,f_com,f_idoper) values ".
                  //             //                                  " (".$row['f_orgid'].",2        ,".$row['f_id'].",0     ,CURDATE(),".$row['f_sumnds'].",0      ,''   ,".$kkk.")";
                  //             //  $kk  = $dbh->exec($sql);
                  //             //  }
                  //             }
                  //           }
                  //         }
                  //       //echo $row['f_sumip']."_";
                  //       if($row['f_sumipf']>0)
                  //         {
                  //         //$sql = "select count(*) cnt from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=77";
                  //         $sql = "select ".
                  //                "  ifnull((select sum(i.f_sum) from ".DBPref."spec_invoices i ".
                  //                "          where i.f_parenttype=2 and i.f_specid=".$row['f_specid']." and i.f_idoper=77),0) isum,".
                  //                "  ifnull((select sum(dt.f_sumipf) from ".DBPref."dt dt where dt.f_specid=".$row['f_specid']."),0) dsum";
                  //         $res1 = $dbh->query($sql);
                  //         if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  //           {
                  //           //if($row1['cnt']==0)
                  //           if($row1['dsum']>$row1['isum'])
                  //             {
                  //             makeOper(2,$row['f_specid'],"","",0,$row['f_sumipf'],$row['f_sumipfval'],0,1,"",
                  //                      "","",0,"",0,77,$row['f_contrid'],$row['f_orgid'],0,0,"",
                  //                      0,0,0,0,-5,"","",185,"",0,
                  //                      "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                  //             //$kkk = addOperRow(2,0,$row['f_specid'],44,2,"","",0,$row['f_sumip'],$row['f_sumipval'],0,1,"","","",0,"",0,77,$row['f_contrid'],$row['f_orgid'],0,0,0,0,0,0,0,17,0,"","",150,"");
                  //             //if($kkk>0)
                  //             //  {
                  //             //  $sql = "insert into ".DBPref."els (f_orgid            ,f_typepay,f_dtid          ,f_ppid,f_ppdt   ,f_outsum           ,f_ppsum,f_com,f_idoper) values ".
                  //             //                                  " (".$row['f_orgid'].",1        ,".$row['f_id'].",0     ,CURDATE(),".$row['f_sumip'].",0      ,''   ,".$kkk.")";
                  //             //  $kk  = $dbh->exec($sql);
                  //             //  }
                  //             }
                  //           }
                  //         }
                  //       //echo $row['f_sumst']."_";
                  //       if($row['f_sumstf']>0)
                  //         {
                  //         //$sql = "select count(*) cnt from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=75";
                  //         $sql = "select ".
                  //                "  ifnull((select sum(i.f_sum) from ".DBPref."spec_invoices i ".
                  //                "          where i.f_parenttype=2 and i.f_specid=".$row['f_specid']." and i.f_idoper=75),0) isum,".
                  //                "  ifnull((select sum(dt.f_sumstf) from ".DBPref."dt dt where dt.f_specid=".$row['f_specid']."),0) dsum";
                  //         $res1 = $dbh->query($sql);
                  //         if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  //           {
                  //           //if($row1['cnt']==0)
                  //           if($row1['dsum']>$row1['isum'])
                  //             {
                  //             makeOper(2,$row['f_specid'],"","",0,$row['f_sumstf'],$row['f_sumstfval'],0,1,"",
                  //                      "","",0,"",0,75,$row['f_contrid'],$row['f_orgid'],0,0,"",
                  //                      0,0,0,0,-5,"","",185,"",0,
                  //                      "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                  //             //$kkk = addOperRow(2,0,$row['f_specid'],25,2,"","",0,$row['f_sumst'],$row['f_sumstval'],0,1,"","","",0,"",0,75,$row['f_contrid'],$row['f_orgid'],0,0,0,0,0,0,0,17,0,"","",150,"");
                  //             //if($kkk>0)
                  //             //  {
                  //             //  $sql = "insert into ".DBPref."els (f_orgid            ,f_typepay,f_dtid          ,f_ppid,f_ppdt   ,f_outsum           ,f_ppsum,f_com,f_idoper) values ".
                  //             //                                  " (".$row['f_orgid'].",2        ,".$row['f_id'].",0     ,CURDATE(),".$row['f_sumst'].",0      ,''   ,".$kkk.")";
                  //             //  $kk  = $dbh->exec($sql);
                  //             //  }
                  //             }
                  //           }
                  //         }
                  //       //echo $row['f_sumsp']."_";
                  //       if($row['f_sumspf']>0)
                  //         {
                  //         //$sql = "select count(*) cnt from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=98";
                  //         $sql = "select ".
                  //                "  ifnull((select sum(i.f_sum) from ".DBPref."spec_invoices i ".
                  //                "          where i.f_parenttype=2 and i.f_specid=".$row['f_specid']." and i.f_idoper=98),0) isum,".
                  //                "  ifnull((select sum(dt.f_sumspf) from ".DBPref."dt dt where dt.f_specid=".$row['f_specid']."),0) dsum";
                  //         $res1 = $dbh->query($sql);
                  //         if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  //           {
                  //           //if($row1['cnt']==0)
                  //           if($row1['dsum']>$row1['isum'])
                  //             {
                  //             makeOper(2,$row['f_specid'],"","",0,$row['f_sumspf'],$row['f_sumspfval'],0,1,"",
                  //                      "","",0,"",0,98,$row['f_contrid'],$row['f_orgid'],0,0,"",
                  //                      0,0,0,0,-5,"","",185,"",0,
                  //                      "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                  //             }
                  //           }
                  //         }
                  //      //sdid 611
		              //      }
                  //      //~sdid 611
                  //  }
                  //~sdid2381
                  //elseif($nstatus==22)//ТС принята - статус больше не используется, перехал в доп статусы
                  //  {
                  //  //делаем операцию возврата обеспечения
                  //  if($row['f_provsum']>0)
                  //    {
                  //    //echo $row['f_provsum']."|".$row['f_bglink'];
                  //    //makeOper($typedog,$curobj,$oprsrokdt,$oprsroktm,$otvetst,$oprsum,$val,$nds,$vozm,$oprwloans,
                  //    //        $oprloansperc,$oprloansdt,$status,$oprcomm,$dpoprid,$typeopr,$contrid,$orgid,$dogid,$opruved,$opradr,
                  //    //        $temple,$oprcrfd,$typefd,$contraccid,$orgaccid,$invnum,$invdt,$bdrarticle,$invcomm,$dpisforfd,
                  //    //        $uvedadr1,$uvedadr2,$tppayc,$dtid,$uvedtyp,$addnds,$oprst,$oprstid,$invid)
                  //    if($row['f_bglink']>0)
                  //      {
                  //      makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],643,0,0,"",
                  //               "","",0,"Возврат обеспечения",0,82,$row['bgcntr'],$row['f_orgid'],$row['f_bglink'],0,"",
                  //               0,0,0,0,0,"","",150,"",0,
                  //               "","",0,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00");
                  //      }
                  //    else
                  //      {
                  //      makeOper(2,$row['f_specid'],"","",0,$row['f_provsum'],643,0,0,"",
                  //               "","",0,"Возврат обеспечения",0,82,$row['f_contrid'],$row['f_orgid'],0,0,"",
                  //               0,0,0,0,-5,"","",150,"",0,
                  //               "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00");
                  //      }
                  //    }
                  //  }
                  elseif($nstatus==25)//Комментарий получен
                    {
                    if($row['f_dtcom']=="0000-00-00")
                      {
                      $ki = Array('curtbl'=>61,'curidx'=>$row['f_id'],'f_dtcom'=>date('Y-m-d'));
                      editRowTbl($ki);
                      }
                    }
                  }
                //sdid2381
                //// sdid 2168
                //if(($oupels!=$row['f_upels'])&&($row['f_upels']==1))//изменилось поле "Пополнение елс" в таблице информации о ДТ
                //  {
                //  //Поручение на пополнение ЕЛС
                //  //sdid - 942
                //  //$sql = "select nvl(count(*),0) cnt from ".DBPref."spec_invoices where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper in (select f_id from ".DBPref."typeopers where f_name like '%Поручение на пополнение ЕЛС%')";
                //  //echo $sql."|";
                //  //$res1 = $dbh->query($sql);
                //  //if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если не было операций пополнения списываем все суммы ЕЛС
                //  //  {
                //  //  if($row1['cnt']==0)
                //  //    {
                //  //~sdid - 942
                //      if($row['f_sumnds']>0)
                //        {
                //        $sql = "select nvl(sum(f_sum),0) sopr from ".DBPref."spec_invoices 
                //                where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=80 and f_dtid=".$row['f_id'];
                //        $res1 = $dbh->query($sql);
                //        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если не было операций пополнения списываем все суммы ЕЛС
                //          {
                //          if(($row['f_sumnds']-$row1['sopr'])>0)
                //            {
                //            makeOper(2,$row['f_specid'],"","",0,($row['f_sumnds']-$row1['sopr']),$row['f_sumndsval'],0,1,"",
                //                 "","",0,"",0,80,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //                 0,0,0,0,-5,"","",185,"",0,
                //                 "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //            }
                //          }
                //        }
                //      if($row['f_sumip']>0)
                //        {
                //        $sql = "select nvl(sum(f_sum),0) sopr from ".DBPref."spec_invoices 
                //                where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=78 and f_dtid=".$row['f_id'];
                //        $res1 = $dbh->query($sql);
                //        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если не было операций пополнения списываем все суммы ЕЛС
                //          {
                //          if(($row['f_sumip']-$row1['sopr'])>0)
                //            {
                //            makeOper(2,$row['f_specid'],"","",0,($row['f_sumip']-$row1['sopr']),$row['f_sumipval'],0,1,"",
                //                 "","",0,"",0,78,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //                 0,0,0,0,-5,"","",185,"",0,
                //                 "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //            }
                //          }
                //        }
                //      if($row['f_sumst']>0)
                //        {
                //        $sql = "select nvl(sum(f_sum),0) sopr from ".DBPref."spec_invoices 
                //                where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=76 and f_dtid=".$row['f_id'];
                //        $res1 = $dbh->query($sql);
                //        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если не было операций пополнения списываем все суммы ЕЛС
                //          {
                //          if(($row['f_sumst']-$row1['sopr'])>0)
                //            {
                //            makeOper(2,$row['f_specid'],"","",0,($row['f_sumst']-$row1['sopr']),$row['f_sumstval'],0,1,"",
                //                 "","",0,"",0,76,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //                 0,0,0,0,-5,"","",185,"",0,
                //                 "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //            }
                //          }
                //        }
                //      if($row['f_sumsp']>0)
                //        {
                //        $sql = "select nvl(sum(f_sum),0) sopr from ".DBPref."spec_invoices 
                //                where f_parenttype=2 and f_specid=".$row['f_specid']." and f_idoper=99 and f_dtid=".$row['f_id'];
                //        $res1 = $dbh->query($sql);
                //        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))//если не было операций пополнения списываем все суммы ЕЛС
                //          {
                //          if(($row['f_sumsp']-$row1['sopr'])>0)
                //            {
                //            makeOper(2,$row['f_specid'],"","",0,($row['f_sumsp']-$row1['sopr']),$row['f_sumspval'],0,1,"",
                //                 "","",0,"",0,99,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //                 0,0,0,0,-5,"","",185,"",0,
                //                 "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //            }
                //          }
                //        }
                //  //    }
                //  //  }
                //  }
                //if(($row['f_upels']==1)&&($oupels==$row['f_upels']))//проверяем изменения сумм
                //  {
                //  $sumst_diff = $osumst - $row['f_sumst'];
                //  $sumip_diff = $osumip - $row['f_sumip'];
                //  $sumsp_diff = $osumsp - $row['f_sumsp'];
                //  $sumnds_diff = $osumnds - $row['f_sumnds'];
                //  if(abs($sumst_diff)>PHP_FLOAT_EPSILON)
                //    {
                //    $sums_diff = $old_sumst_inv - $row['f_sumst'];
                //    if($old_sumst_inv>0)
                //      {
                //      if($sumst_diff < 0)
                //        {
                //        // 2.1.2. Если какая-то из сумм стала больше чем была и при этом по ней были созданы соответствующие  операция на меньшую сумму - создавать соответствующую операцию на сумму разницы
                //        if($sums_diff < 0)
                //          {
                //          $sums_diff = round(abs($sums_diff),2);
                //          makeOper(2,$row['f_specid'],"","",0,$sums_diff,$row['f_sumstval'],0,1,"",
                //              "","",0,"",0,76,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //              0,0,0,0,-5,"","",185,"",0,
                //              "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //          }
                //        }
                //      }
                ////elseif ((boolean)json_decode(strtolower($oupels)) != (boolean)json_decode(strtolower($row['f_upels'])))
                //    else
                //      {
                //      makeOper(2,$row['f_specid'],"","",0,$row['f_sumst'],$row['f_sumstval'],0,1,"",
                //          "","",0,"",0,76,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //          0,0,0,0,-5,"","",185,"",0,
                //          "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //      }
                //    }
                //  if(abs($sumip_diff)>PHP_FLOAT_EPSILON)
                //    {
                //    $sums_diff = $old_sumip_inv - $row['f_sumip'];
                //    if($old_sumip_inv > 0)
                //      {
                //      if($sumip_diff < 0)
                //        {
                //        // 2.1.2. Если какая-то из сумм стала больше чем была и при этом по ней были созданы соответствующие  операция на меньшую сумму - создавать соответствующую операцию на сумму разницы
                //        if($sums_diff < 0)
                //          {
                //          $sums_diff = round(abs($sums_diff),2);
                //          makeOper(2,$row['f_specid'],"","",0,$sums_diff,$row['f_sumipval'],0,1,"",
                //              "","",0,"",0,78,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //              0,0,0,0,-5,"","",185,"",0,
                //              "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //          }
                //        }
                //      }
                ////    elseif ((boolean)json_decode(strtolower($oupels)) != (boolean)json_decode(strtolower($row['f_upels'])))
                //    else
                //      {
                //      makeOper(2,$row['f_specid'],"","",0,$row['f_sumip'],$row['f_sumipval'],0,1,"",
                //          "","",0,"",0,78,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //          0,0,0,0,-5,"","",185,"",0,
                //          "","",1,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //      }
                //    }
                //  if(abs($sumsp_diff)>PHP_FLOAT_EPSILON)
                //    {
                //    $sums_diff = $old_sumsp_inv - $row['f_sumsp'];
                //    if($old_sumsp_inv>0)
                //      {
                //      if($sumsp_diff < 0)
                //        {
                //        // 2.1.2. Если какая-то из сумм стала больше чем была и при этом по ней были созданы соответствующие  операция на меньшую сумму - создавать соответствующую операцию на сумму разницы
                //        if($sums_diff < 0)
                //          {
                //          $sums_diff = round(abs($sums_diff),2);
                //          makeOper(2,$row['f_specid'],"","",0,$sums_diff,$row['f_sumspval'],0,1,"",
                //              "","",0,"",0,99,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //              0,0,0,0,-5,"","",185,"",0,
                //              "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //          }
                //        }
                //      }
                ////    elseif ((boolean)json_decode(strtolower($oupels)) != (boolean)json_decode(strtolower($row['f_upels'])))
                //    else
                //      {
                //      makeOper(2,$row['f_specid'],"","",0,$row['f_sumsp'],$row['f_sumspval'],0,1,"",
                //          "","",0,"",0,99,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //          0,0,0,0,-5,"","",185,"",0,
                //          "","",3,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //      }
                //    }
                //  if(abs($sumnds_diff)>PHP_FLOAT_EPSILON)
                //    {
                //    $sums_diff = $old_sumnds_inv - $row['f_sumnds'];
                //    if($old_sumnds_inv > 0)
                //      {
                //      if($sumnds_diff < 0)
                //        {
                //        // 2.1.2. Если какая-то из сумм стала больше чем была и при этом по ней были созданы соответствующие  операция на меньшую сумму - создавать соответствующую операцию на сумму разницы
                //        if($sums_diff < 0)
                //          {
                //          $sums_diff = round(abs($sums_diff),2);
                //          makeOper(2,$row['f_specid'],"","",0,$sums_diff,$row['f_sumndsval'],0,1,"",
                //              "","",0,"",0,80,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //              0,0,0,0,-5,"","",185,"",0,
                //              "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //          }
                //        }
                //      }
                //    elseif ((boolean)json_decode(strtolower($oupels)) != (boolean)json_decode(strtolower($row['f_upels'])))
                //    else
                //      {
                //      makeOper(2,$row['f_specid'],"","",0,$row['f_sumnds'],$row['f_sumndsval'],0,1,"",
                //          "","",0,"",0,80,$row['f_contrid'],$row['f_orgid'],0,0,"",
                //          0,0,0,0,-5,"","",185,"",0,
                //          "","",2,$row['f_id'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                //      }
                //    }
                //  }
                ////~sdid 2168
                //~sdid3281
                if(($nstatus==6)&&($obroker!=$row['f_broker'])&&
                   (($row['f_brokertype']==0)||($row['f_brokertype']==2)||($row['f_brokertype']==4)))//изменился декларант при статусе "Документы переданы декларанту"
                  {
                  mSendMail($_SESSION['loginid'],0,$row['f_broker'],"В информации о ДТ изменен декларант",
                        "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ</a> изменен декларант","",61,$row['f_id']);
                  mSendMail($_SESSION['loginid'],15,0,"В информации о ДТ изменен декларант",
                        "В <a href=\"".$redirect_uri."?pgid=61&obid=".$curidx."\">информации о ДТ</a> изменен декларант","",61,$row['f_id']);
                  }
                //sdid - 693
                if(($otddt!=$row['f_TDdt'])&&($row['f_subtype']==3))//изменилась "Дата выпуска ДТ (растаможки)" для "Услуги ТП"
                  {
                  $ki = Array('curtbl'=>15,'curidx'=>$row['f_specid'],'f_dttoclnt'=>$row['f_TDdt'],'f_dtreptocl'=>$row['f_TDdt'],'f_status'=>16);
                  editRowTbl($ki);
                  }
                //~sdid - 693
                // sdid 1553
                if ($ooperto != $row['f_operto'])
                  {
                  $operto = "";
                  $sql_operto = "SELECT CONCAT(users.f_name1,' ',users.f_name2) operto from ".DBPref."users users WHERE users.f_id=".$row['f_operto'];
                  $res_operto = $dbh->query($sql_operto);
                  if($row_operto = $res_operto->fetch(PDO::FETCH_ASSOC))
                    {$operto = $row_operto['operto'];}
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from " . DBPref . "spr where f_type=151 and f_num=15";
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if ($row1['f_uslint'] == 1)
                      {
                      if (strcmp($row1['f_uslstr'], "2") == 0)
                        {
                        $mgrp = $row1['f_namedop'];
                        $musr = 0;
                        } 
                      else
                        {
                        $mgrp = 0;
                        $musr = $row1['f_namedop'];
                        }
                      $subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 1, $curidx);
                      $postbody = lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 1, $curidx);
                      $postbody =  "По записи <a href=\"" . $redirect_uri . "?pgid=61&obid=" . $curidx . "\">Информация о ТО</a> назначен специалист ТО  ".$operto." <br><br>" . $postbody;
                      if($menomes == 0) 
                        {mSendMail($_SESSION['loginid'], $mgrp, $musr, $subject, $postbody, "", 61, $curidx);}
                      }
                    }
                  }
                // ~ sdid 1553
                }
              }
            elseif(strcmp($tblname,DBPref."shipments")==0)//обрабатываем обновление информации о доставке
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select f_status from ".$tblname." where f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                }
              //$ans=$ans.$sql."_".$nstatus."_".$ostatus;
              if($nstatus!=$ostatus)//изменился статус доставки
                {
                // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=43 and f_num=".$nstatus;
                //sdid - 164
                //sdid 703
                //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=43 and f_num=".$nstatus;
                $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=43 and f_num=".$nstatus;
                //~sdid 703
                //~sdid - 164
                //$ans=$ans.$sql;
                $res = $dbh->query($sql);
                if($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_uslint']==1)
                    {
                    if(strcmp($row['f_uslstr'],"2")==0)
                      {$mgrp=$row['f_namedop'];$musr=0;}
                    else
                      {$mgrp=0;$musr=$row['f_namedop'];}
                    //$ans=$ans."|".$mgrp."_".$musr;
                    if($menomes==0){
                      //sdid - 164
                      //sdid 703
                      if($row['f_dopprstr1']>0)
                        {$subject = lGetPostSubjShablon($row['f_dopprstr1'], 0, 0, 0, 0, 2, $curidx);}
                      else
                        {$subject = "По доставке изменен статус на ".$row['f_name'];}
                      //~sdid 703
                      $postbody = "По <a href=\"".$redirect_uri."?pgid=102&obid=".$curidx."\">доставкe</a> изменен статус на \"".$row['f_name']."\"<br>";
                      $postbody = $postbody . lGetPostShablon($row['f_dopprint'], 0, 0, 0, 0, 2, $curidx);
                      mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",102,$curidx);
                      //~sdid - 164
                    }
                    }
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."ensures")==0)//после обновления страхования перевозок, обрабатываем обновление информации о страховке
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select e.f_status, e.f_enssump, e.f_enssumpval, e.f_declareid, e.f_dogid, ifnull(e.f_sumens,0) sumens, e.f_sumensval, 
                      case ifnull(e.f_ensnum,'') when '_' then '' else ifnull(e.f_ensnum,'') end fensnum,
                      concat(ifnull(e.f_ensnum,''),'/',ifnull(e.f_ensdt,''),' (ИД ',e.f_id,')') insname, d.f_contrid, d.f_orgid, e.f_objtype, e.f_objid, ifnull(e.f_ensdt,'') f_ensdt
                      from ".$tblname." e,".DBPref."dogs d where d.f_id=e.f_dogid and e.f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                //sdid 3550
                // Если изменилась дата полиса
                $buhdoctype = 0;
                $oensdt = $row['f_ensdt'];
                if((strcmp($nensdt,$oensdt)!=0) && (strcmp($nensdt,"0000-00-00")!=0) && strlen($nensdt)>0)
                  {
                  // Если контрагент Ингосстрах и указан номер договора
                  if(($row['f_contrid']==2554) && $row['f_dogid']>0)
                    {
                    // Если у договора присутствует категория "Тип бух. документа"
                    $sql = "select f_valstr from veda_categs where f_ctgtype=36 and f_objecttype=3 and f_objectid=".$row['f_dogid'];
                    $res3 = $dbh->query($sql);
                    if($row3 = $res3->fetch(PDO::FETCH_ASSOC)) {$buhdoctype = $row3['f_valstr'];}
                    // ищем наличие операции поручения на оплату страхования
                    $sql = "select si.f_id from veda_spec_invoices si where si.f_objtype=54 and si.f_objid=" . $curidx . " and si.f_idoper in (85,138,139,246)";
                    $res1 = $dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $sql_akt = "SELECT a.f_id, a.f_num, a.f_dt, a.f_dt1c, a.f_kod1c, ad.f_id adfid FROM " . DBPref . "akts a, ".DBPref."akts_details ad WHERE ad.f_aktid=a.f_id AND ad.f_num=1 AND a.f_operid=" . $row1["f_id"];
                      $res_akt = $dbh->query($sql_akt);
                      while($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                        {if(isset($row_akt["f_id"]))
                          {//то меняем дату и тип бух.документа
                          $akt_update = [
                            "curtbl"  => 83,
                            "curidx"  => $row_akt['f_id'],
                            "f_dt"    => $nensdt,
                            "f_tdocb" => $buhdoctype
                            ];
                          editRowTbl($akt_update);
                          }
                        }
                      }
                    else
                      {// если не нашли, ищем первую без objtype/objid, включенную в операции спецификации, связанной с текущей страховкой
                      $sql = "select si.f_id from veda_ensures e,veda_specs s,veda_spec_invoices si
                              where e.f_id=" . $curidx . " and s.f_id=e.f_objid and si.f_specid=s.f_id and si.f_idoper in (85,138,139,246) and si.f_objtype=0 and si.f_objid=0";
                      $res2 = $dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        $sql_akt = "SELECT a.f_id, a.f_num, a.f_dt, a.f_dt1c, a.f_kod1c, ad.f_id adfid FROM " . DBPref . "akts a, ".DBPref."akts_details ad WHERE ad.f_aktid=a.f_id AND ad.f_num=1 AND a.f_operid=" . $row2["f_id"];
                        $res_akt = $dbh->query($sql_akt);
                        while($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                          {if(isset($row_akt["f_id"]))
                            {//то меняем дату и тип бух.документа
                            $akt_update = [
                              "curtbl"  => 83,
                              "curidx"  => $row_akt['f_id'],
                              "f_dt"    => $nensdt,
                              "f_tdocb" => $buhdoctype
                              ];
                            editRowTbl($akt_update);
                            }
                          }
                        }
                      else
                        {
                        // если не нашли, то создаем новое поручение на оплату (операцию) по данным страховки с созданием закрывающего документа
                        $retval = makeOper($row['f_objtype'], $row['f_objid'], "", "", 0, $row['sumens'], $row['f_sumensval'], 0, 1, "",
                          "", "", 0, "", 0, 85, $row['f_contrid'], $row['f_orgid'], $row['f_dogid'], 0, "",
                          0, 1, 7,/*$row['f_contraccid']*/ 0,/*$row['f_orgaccid']*/ -5, $row['fensnum'], $row['f_ensdt'],/*$row['f_bdrarticle']*/ 181, "", 0,
                          "", "", 0, 0, 0, 0, 0, 0, 0, "0000-00-00", "0000-00-00", 
                          //"{\"oprobjtype\":\"54\",\"oprobjid\":\"" . $curidx . "\",\"schetsum\":\"" . $row['sumens'] . "\"}"
                          "{\"oprobjtype\":\"54\",\"oprobjid\":\"".$curidx."\",\"schetsum\":\"".$row['sumens']."\",\"aktdt\":\"".$nensdt."\",\"tdocb\":\"".$buhdoctype."\"}"
                        );
                        }
                      }
                    }
                  }
                //~sdid 3550
                //sdid 1482
                if($row['sumens']!=$osumens) // Если менялась сумма страховой премии
                  {$sql_specs = "SELECT s.f_id, s.f_status FROM " . DBPref . "specs s, " . DBPref . "ensures e WHERE s.f_id=e.f_objid AND e.f_objtype=2 AND e.f_id=" . $curidx;
                  $res_specs = $dbh->query($sql_specs);
                  if($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                    {if(isset($row_specs["f_id"]))
                      {$sql_invoices = "SELECT i.f_id FROM " . DBPref . "spec_invoices i, " . DBPref . "typeopers t WHERE i.f_idoper=t.f_id AND t.f_name LIKE \"%Страхов%\" AND i.f_specid=" . $row_specs["f_id"];
                      $res_invoices = $dbh->query($sql_invoices);
                      while($row_invoices = $res_invoices->fetch(PDO::FETCH_ASSOC))
                        {if(isset($row_invoices["f_id"]))
                          {$sql_akt = "SELECT a.f_id, a.f_num, a.f_dt, a.f_dt1c, a.f_kod1c, ad.f_id adfid FROM " . DBPref . "akts a, ".DBPref."akts_details ad WHERE ad.f_aktid=a.f_id AND ad.f_num=1 AND a.f_operid=" . $row_invoices["f_id"];
                          $res_akt = $dbh->query($sql_akt);
                          while($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                            {if(isset($row_akt["f_id"]))
                              {//то меняем сумму закрывающего документа
                              $akt_update = [
                                "curtbl" => 83,
                                "curidx" => $row_akt['f_id'],
                                "f_sum" => $row['sumens']
                                ];
                              editRowTbl($akt_update);
                              $akt_detail_update = [
                                "curtbl" => 180,
                                "curidx" => $row_akt['adfid'],
                                "f_sum" => $row['sumens'],
                                "f_price" => $row['sumens']
                                ];
                              editRowTbl($akt_detail_update);
                              if(isset($row_akt["f_kod1c"]))
                                {if(strlen($row_akt["f_kod1c"]) > 0)
                                  {//2.1.1. Если закрывающий документ выгружен в 1С, перевыгружаем с новой суммой.
                                  $retval = json_decode(expFinDocTo1C(7, $row_akt["f_id"]), true); // Выгружаеем заново акт в 1С
                                  $expmsg = "";
                                  if($retval[0] == true)
                                    {$expmsg = "Выгрузка закрывающего документа в 1С: удачно.<br>Сообщение системы: " . $retval[1];}
                                  elseif ($retval[0] == false)
                                    {$expmsg = "Выгрузка закрывающего документа в 1С: неудачно.<br>Сообщение системы: " . $retval[1];}
                                  // sdid 1566
                                  if($row_specs['f_status']==6 || $row_specs['f_status']==8)
                                    {
                                    mSendMail($_SESSION['loginid'], 14, 0, "Выгрузка закрывающего документа № " . $row_akt['f_num'] . " от " . $row_akt['f_dt'] . " в 1С",
                                        " Выгрузка закрывающего документа № " . $row_akt['f_num'] . " от " . $row_akt['f_dt'] . " в 1С" . ", <a href=\"" . $redirect_uri . "?pgid=83&obid=" . $row_akt['f_id'] . "\">Ссылка на закрывающий документ</a><br>" . $expmsg, "", 35, $row_akt["f_id"]);
                                    }
                                  //// ~ sdid 1566
                                  }}}}}}
                      }
                    }
                  }
                // ~ sdid 1482
                //sdid 1388
                // если сумма премии больше 0
                if($row['sumens'] > 0)
                  {// ищем наличие операции поручения на оплату страхования
                  $sql = "select si.f_id from veda_spec_invoices si where si.f_objtype=54 and si.f_objid=" . $curidx . " and si.f_idoper in (85,138,139,246)";
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {// если нашли этим методом, то обновляем информацию о сумме, валюте, договоре, контрагенте на данные из страховки, сумма - это сумма премии
                    $ki = array('curtbl' => 35, 'curidx' => $row1['f_id'], 'f_sum' => $row['sumens'], 'f_val' => $row['f_sumensval'], 'f_dogid' => $row['f_dogid'], 'f_contrid' => $row['f_contrid']);
                    editRowTbl($ki);}
                  else
                    {// если не нашли, ищем первую без objtype/objid, включенную в операции спецификации, связанной с текущей страховкой
                    $sql = "select si.f_id from veda_ensures e,veda_specs s,veda_spec_invoices si
                            where e.f_id=" . $curidx . " and s.f_id=e.f_objid and si.f_specid=s.f_id and si.f_idoper in (85,138,139,246) and si.f_objtype=0 and si.f_objid=0";
                    $res2 = $dbh->query($sql);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {// если нашли этим методом, то обновляем информацию о сумме, валюте, договоре, контрагенте на данные из страховки, сумма - это сумма премии
                      $ki = array('curtbl' => 35, 'curidx' => $row2['f_id'], 'f_sum' => $row['sumens'], 'f_val' => $row['f_sumensval'], 'f_dogid' => $row['f_dogid'], 'f_contrid' => $row['f_contrid']);
                      editRowTbl($ki);}
                    else
                      {
                      //if($_SESSION['loginid']==2) {echo "sumens=".$row['sumens']."|";}
                      // если не нашли, то создаем новое поручение на оплату (операцию) по данным страховки с созданием закрывающего документа
//sdid 1388 2023-07-11
                      //$retval = makeOper($row['f_objtype'], $row['f_objid'], "", "", 0, $row['sumens'], $row['f_sumensval'], 0, 1, "",
                      //    "", "", 0, "", 0, 85, $row['f_contrid'], $row['f_orgid'], $row['f_dogid'], 0, "",
                      //    0, 1, 7,/*$row['f_contraccid']*/ 0,/*$row['f_orgaccid']*/ -5, $row['fensnum'], date('Y-m-d'),/*$row['f_bdrarticle']*/ 181, "", 0,
                      //    "", "", 0, 0, 0, 0, 0, 0, 0, "0000-00-00", "0000-00-00", "{\"oprobjtype\":\"54\",\"oprobjid\":\"" . $curidx . "\",\"schetsum\":\"" . $row['sumens'] . "\"}");
                      if( strcmp($row['f_ensdt'],"0000-00-00")==0 or strlen($row['f_ensdt'])==0 ){}
                      else
                        {$retval = makeOper($row['f_objtype'], $row['f_objid'], "", "", 0, $row['sumens'], $row['f_sumensval'], 0, 1, "",
                          "", "", 0, "", 0, 85, $row['f_contrid'], $row['f_orgid'], $row['f_dogid'], 0, "",
                      //sdid 1683
                      //    0, 1, 7,/*$row['f_contraccid']*/ 0,/*$row['f_orgaccid']*/ -5, $row['fensnum'], date('Y-m-d'),/*$row['f_bdrarticle']*/ 181, "", 0,
                          0, 1, 7,/*$row['f_contraccid']*/ 0,/*$row['f_orgaccid']*/ -5, $row['fensnum'], $row['f_ensdt'],/*$row['f_bdrarticle']*/ 181, "", 0,
                      //~sdid 1683
                          "", "", 0, 0, 0, 0, 0, 0, 0, "0000-00-00", "0000-00-00", "{\"oprobjtype\":\"54\",\"oprobjid\":\"" . $curidx . "\",\"schetsum\":\"" . $row['sumens'] . "\"}");
                        }
//~sdid 1388 2023-07-11
                      //if(isset($djspar['oprobjtype'])){$oprobjtype = $djspar['oprobjtype'];}
                      //if(isset($djspar['oprobjid'])){$oprobjid = $djspar['oprobjid'];}
                      //if(isset($djspar['schetsum'])){$schetsum = $djspar['schetsum'];}
                      //if($_SESSION['loginid']==2) {echo "retval=".$retval."|";}
                      }}
                  }
                //~sdid 1388
//sdid 1257
                 //если у записи изменился договр, и статус страховки в пределах 1,2,3,6, перемещаем запись в текущую/новую декларацию 
                //if(($oinsdogid != $row['f_dogid']) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287)) && ($row['f_contrid']==2554) && (($row['f_status']==1) || ($row['f_status']==2) || ($row['f_status']==3) || ($row['f_status']==6)))//sdid2542
                if(($oinsdogid != $row['f_dogid']) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287) || ($row['f_dogid']==7916)) && ($row['f_contrid']==2554) && (($row['f_status']==1) || ($row['f_status']==2) || ($row['f_status']==3) || ($row['f_status']==6)))//sdid2542
                  {
                  //$sql = "select f_id from ".DBPref."insuredeclare where f_dt=DATE_FORMAT(now(),'%Y-%m-01') and f_status=1 and f_dogid=".$row['f_dogid'];  
                  $sql = "select f_id from ".DBPref."insuredeclare where f_status=1 and f_dogid=".$row['f_dogid'];  
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                     $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_declareid'=>$row1['f_id']);    
                     editRowTbl($ki);
                    }
                  else // иначе создаем новую и перносим туда
                    {
                     $sql = "select (ifnull(max(f_dt),0)+1) nextnum from ".DBPref."insuredeclare where f_dogid=".$row['f_dogid'];
                     $res4 = $dbh->query($sql);
                     if($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                       { 
                         $ki = Array('curtbl'=>262,'f_dogid'=>$row['f_dogid'],'f_status'=>1,'f_dt'=>$row4['nextnum']);    
                         $ar = json_decode(addRowTbl($ki), true);
                         if($ar[0]=="true")
                          {
                           //$sql = "select f_id from ".DBPref."insuredeclare where f_dt=DATE_FORMAT(now(),'%Y-%m-01') and f_status=1 and f_dogid=".$row['f_dogid'];
                           $sql = "select f_id from ".DBPref."insuredeclare where f_dt=".$row4['nextnum']." and f_status=1 and f_dogid=".$row['f_dogid'];
                           $res1 = $dbh->query($sql);
                           if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                            {
                              $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_declareid'=>$row1['f_id']);
                              editRowTbl($ki);
                            }
                          }
                       }
                    } 
                  // При смене номера договора у страховки, если не заполнено||=№ договора поле "Номер полиса" и выбрана декларация или добавили ее, 
                  // то заполняем номер цифровым значением следующим от максимального в текущей декларации, номер ищем как максимальный  + 1 
                  $sql = "select e.f_declareid, case ifnull(e.f_ensnum,'') when '_' then '' else ifnull(e.f_ensnum,'') end fensnum, d.f_dogname, e.f_dogid 
                             from veda_ensures e, veda_dogs d 
                            where d.f_id=e.f_dogid and e.f_id=".$curidx;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    { //echo "row1['f_declareid']=".$row1['f_declareid']." | row1['fensnum']= ".$row1['fensnum']." | row1['f_dogname']= ".$row1['f_dogname']." | row1['f_dogid']= ".$row1['f_dogid']." |"; 
                    if(((strcmp($row1['fensnum'],($row1['f_dogname']))==0) || (strcmp($row1['fensnum'],($row1['f_dogname']."/"))==0) || (strlen($row1['fensnum'])==0)) && ($row1['f_declareid']>0)) 
                      {
                      $sql = "select (concat(d.f_dogname,'/',(select c.f_abbr from veda_clients c, veda_dogs dd where c.f_id=dd.f_orgid and dd.f_id=".$row['f_dogid']."),'/',
                                         case LENGTH(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1))
                                         when 1 then LPAD(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1),2,'0')
                                         else max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1)
                                          end )) nextnum
                                    from veda_ensures st,veda_dogs d
                                   where st.f_dogid in (5286,5287,7916) and d.f_id=st.f_dogid";//sdid2542
                      $res2 = $dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                         $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_ensnum'=>$row2['nextnum']); 
                         editRowTbl($ki);
                        }
                      } 
                    }
                  }
//~sdid 1257

                $nstatus = $row['f_status'];
                if($row['f_status']!=$ostatus)//изменился статус страховки
                  {
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=19 and f_num=".$nstatus;
                  //sdid - 164
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=19 and f_num=".$nstatus;
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=19 and f_num=".$nstatus;
                  //~sdid 703
                  //~sdid - 164
                  //$ans=$ans.$sql;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      if($menomes==0){
                        //sdid - 164                        
                        //sdid 703                       
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 4, $curidx);}
                        else
                          {$subject = "По страховке изменен статус на ".$row1['f_name'];}
                        //~sdid 703
                        $postbody = "По <a href=\"".$redirect_uri."?pgid=54&obid=".$curidx."\">страховкe</a> изменен статус на \"".$row1['f_name']."\"";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 4, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",54,$curidx);
                        //~sdid - 164     
                        }
                      }
                    }
                  //sdid - 934 - ставим задаче о подготовке документов для бухгалтерии по страхованию статус - закрыта и создаем задачу "Страхование. Документы в бухгалтерию"
                  if($row['f_status']==2)//изменился статус страховки на Окончательно
                    {
                    $sql = "select f_id,case when f_end<now() then 6 else 5 end status 
                            from ".DBPref."tasks where f_objtype=54 and f_objid=$curidx and f_calendarid=3 and f_type=1";
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>254,'curidx'=>$row1['f_id'],'f_status'=>$row1['status']);
                      editRowTbl($ki);
                      }
                    $ki = Array('curtbl'=>254,'f_subjtype'=>2,'f_subjid'=>8,'f_objtype'=>54,'f_objid'=>$curidx,
                                'f_start'=>date("Y-m-d"),'f_end'=>date('Y-m-d',strtotime("+3 days", strtotime(nextWorkDay(date("Y-m-d"),1)))),
                                'f_calendarid'=>3,'f_status'=>3,'f_title'=>"Документы в бухгалтерию. Страхование ".$row['insname'],
                                'f_type'=>2,'f_isallday'=>1,'f_icon'=>8);
                    $ar = json_decode(addRowTbl($ki), true);                    
                    }
                  elseif($row['f_status']==3)//изменился статус страховки на В бухгалтерии
                    {
                    $sql = "select f_id,case when f_end<now() then 6 else 5 end status 
                            from ".DBPref."tasks where f_objtype=54 and f_objid=$curidx and f_calendarid=3 and f_type=2";
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>254,'curidx'=>$row1['f_id'],'f_status'=>$row1['status']);
                      editRowTbl($ki);
                      }
                      //sdid 1207
                      //изменился статус страховки на "В бухгалтерии" --> устанавливаем дату поля "Дата получения заявл. из СК" на Now()
                      $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_stskdt'=>date("Y-m-d"));    
                      editRowTbl($ki);
                      //~sdid 1207
                    }
                  //~sdid - 934
                  // sdid 1482
                  elseif($row['f_status'] == 5)
                    {$sql_specs = "SELECT s.f_id FROM ".DBPref."specs s,".DBPref."ensures e WHERE s.f_id=e.f_objid AND e.f_objtype=2 AND e.f_id=".$curidx;
                    $res_specs = $dbh->query($sql_specs);
                    if($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                      {if(isset($row_specs["f_id"]))
                        {$sql_invoices = "SELECT i.f_id FROM ".DBPref."spec_invoices i,".DBPref."typeopers t WHERE i.f_idoper=t.f_id AND t.f_name LIKE '%Страхов%' AND i.f_specid=".$row_specs["f_id"]." and i.f_objtype=54 and i.f_objid=$curidx";
                        $res_invoices = $dbh->query($sql_invoices);
                        while($row_invoices = $res_invoices->fetch(PDO::FETCH_ASSOC))
                          {if(isset($row_invoices["f_id"]))
                             {$sql_akt = "SELECT a.f_id, a.f_dt1c, a.f_kod1c FROM " . DBPref . "akts a WHERE a.f_operid=" . $row_invoices["f_id"];
                             $res_akt = $dbh->query($sql_akt);
                             while($row_akt = $res_akt->fetch(PDO::FETCH_ASSOC))
                               {if(isset($row_akt["f_kod1c"]))
                                 {if(strlen($row_akt["f_kod1c"]) > 0)
                                   {// 1.3. закрывающий документ, если он выгружен в 1С - аннулируем в 1С.
                                   $akt_annul = [
                                       "curtbl" => 83,
                                       "curidx" => $row_akt['f_id'],
                                       "f_status" => 9
                                     ];
                                   editRowTbl($akt_annul);
                                   }}}}}}}

                    }
                  // ~ sdid 1482
//sdid 1257
//sdid 1388
                  //изменился статус страховки на  "Предварительно", "Окончательно", "Оригинал", "В бухгалтерии"
                  //if(($row['f_contrid']==2554) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287)) && (($row['f_status']==1) || ($row['f_status']==2) || ($row['f_status']==3) || ($row['f_status']==6)))
                  //if(($row['f_contrid']==2554) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287)) && (($nstatus==1) || ($nstatus==2) || ($nstatus==3) || ($nstatus==6)))                          //sdid2542
                  if(($row['f_contrid']==2554) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287) || ($row['f_dogid']==7916)) && (($nstatus==1) || ($nstatus==2) || ($nstatus==3) || ($nstatus==6))) //sdid2542
                    {
                    //if(!isset($row['f_declareid']) || $row['f_declareid']==0) 
                    //if($_SESSION['loginid']==2) {echo "declareid=".$row['f_declareid']."|";}
                    if($row['f_declareid']==0)  
//~sdid 1388
                       {
                        //$sql = "select f_id from ".DBPref."insuredeclare where f_dt=DATE_FORMAT(now(),'%Y-%m-01') and f_status=1 and f_dogid=".$row['f_dogid'];
                        $sql = "select f_id from ".DBPref."insuredeclare where f_status=1 and f_dogid=".$row['f_dogid'];
                        $res1 = $dbh->query($sql);
                        if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {
                             $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_declareid'=>$row1['f_id']);    
                             editRowTbl($ki);
                          }
                        else
                          {
                           $sql = "select (ifnull(max(f_dt),0)+1) nextnum from ".DBPref."insuredeclare where f_dogid=".$row['f_dogid'];
                           $res4 = $dbh->query($sql);
                           if($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                             {
                              $ki = Array('curtbl'=>262,'f_dogid'=>$row['f_dogid'],'f_status'=>1,'f_dt'=>$row4['nextnum']);    
                              $ar = json_decode(addRowTbl($ki), true);
                              if($ar[0]=="true")
                                {
                                 $sql = "select f_id from ".DBPref."insuredeclare where f_dt=".$row4['nextnum']." and f_status=1 and f_dogid=".$row['f_dogid'];
                                 $res1 = $dbh->query($sql);
                                 if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                                   {
                                    $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_declareid'=>$row1['f_id']);
                                    editRowTbl($ki);
                                   }
                                }
                             }
                          }  
                       }
                    }

                  // При смене статуса у страховки, если не заполнено поле "Номер полиса" и выбрана декларация или добавили ее, 
                  //то заполняем номер цифровым значением следующим от максимального в текущей декларации, номер ищем как максимальный  + 1 
                  //echo "fdeclareid=".$fdeclareid." | row['fensnum']= ".$row['fensnum']." |"; 
                  if($row['f_contrid']==2554)
                    {
                      $sql = "select e.f_declareid, 
                                case ifnull(e.f_ensnum,'') when '_' then '' else ifnull(e.f_ensnum,'') end fensnum, d.f_dogname, e.f_dogid 
                                from veda_ensures e, veda_dogs d where d.f_id=e.f_dogid and e.f_id=".$curidx;
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        { //echo "row1['f_declareid']=".$row1['f_declareid']." | row1['fensnum']= ".$row1['fensnum']." | row1['f_dogname']= ".$row1['f_dogname']." | row1['f_dogid']= ".$row1['f_dogid']." |"; 
                          if(((strcmp($row1['fensnum'],($row1['f_dogname']))==0) || (strcmp($row1['fensnum'],($row1['f_dogname']."/"))==0) || (strlen($row1['fensnum'])==0)) && ($row1['f_declareid']>0)) // !!!!НУЖНО ВЫДЕЛИТЬ ОТДЕЛЬНОЕ УСЛОВИЕ ПО НЕ ИНГОССТРАХ
                            {
                              /*$sql = "select (concat(d.f_dogname,'/',(select f_abbr from veda_clients where f_id=d.f_orgid),'/',
                                              case when DATE_FORMAT(id.f_dt,'%Y')='2023' then lpad((DATE_FORMAT(id.f_dt,'%m')-3),2,'0') else DATE_FORMAT(id.f_dt,'%m') end,'/',
                                              case LENGTH(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1))
                                              when 1 then LPAD(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1),2,'0')
                                              else max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1)
                                               end
                                             )) nextnum
                                        from veda_ensures st,veda_dogs d,veda_insuredeclare id
                                       where st.f_dogid=".$row1['f_dogid']." and d.f_id=st.f_dogid and id.f_id=st.f_declareid
                                         and (case d.f_contrid when 2554 then (st.f_declareid=".$row1['f_declareid']." and st.f_declareid<>0) else 1=1 end)";*/
/*                             $sql = "select (concat(d.f_dogname,'/',(select f_abbr from veda_clients where f_id=d.f_orgid),'/',
                                           case LENGTH(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1))
                                           when 1 then LPAD(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1),2,'0')
                                           else max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1)
                                            end )) nextnum
                                      from veda_ensures st,veda_dogs d
                                     where st.f_dogid in (5286,5287) and d.f_id=st.f_dogid";
*/
                             $sql = "select (concat(d.f_dogname,'/',(select c.f_abbr from veda_clients c, veda_dogs dd where c.f_id=dd.f_orgid and dd.f_id=".$row['f_dogid']."),'/',
                                           case LENGTH(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1))
                                           when 1 then LPAD(max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1),2,'0')
                                           else max(cast((SUBSTRING_INDEX(SUBSTRING_INDEX(st.f_ensnum,'/',-1), '-',1)) as unsigned)+1)
                                            end )) nextnum
                                      from veda_ensures st,veda_dogs d
                                     where st.f_dogid in (5286,5287,7916) and d.f_id=st.f_dogid";//sdid2542

                             $res2 = $dbh->query($sql);
                             if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                               {
                                $ki = Array('curtbl'=>54,'curidx'=>$curidx,'f_ensnum'=>$row2['nextnum']); 
                                editRowTbl($ki);
                               }
                            } 
                        }
                    }
//~sdid 1257
                  }
                if(($row['f_enssump']!=$oenssump)||($row['f_enssumpval']!=$oenssumpval))//была изменена сумма или валюта предварительной страховки
                  {
                  //$ki = Array('curtbl'=>54,'curidx'=>$curidx,''=>1);
                  //editRowTbl($ki);
                  }
                }
              }
            //sdid - 164
//sdid 1257
            elseif(strcmp($tblname,DBPref."insuredeclare")==0)//обрабатываем обновление Страхование\Декларации
              {
              //$dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              //$dbh->exec('SET CHARACTER SET utf8');
              $sql = "select f_status,f_dogid from $tblname where f_id=".$curidx;
              //echo $sql;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                //echo $row['f_status']."|".$ostatus;
                //if($row['f_status']!=$ostatus)//изменился статус декларации
                //if(($row['f_status']!=$ostatus) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287)))//изменился статус декларации                          //sdid2542
                if(($row['f_status']!=$ostatus) && (($row['f_dogid']==5286) || ($row['f_dogid']==5287) || ($row['f_dogid']==7916)))//изменился статус декларации //sdid2542
                  {
                    if($nstatus==2) // статус "В страховой"
                      {
                        $sql = "select f_id, f_dogid from ".DBPref."ensures where f_status not in (2,3,6) and f_declareid=".$curidx;
                        //echo $sql;
                        $res1 = $dbh->query($sql);
                        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {
                            //$sql = "select f_id from ".DBPref."insuredeclare where f_dt=DATE_FORMAT(now(),'%Y-%m-01') and f_status=1 and f_dogid=".$row1['f_dogid'];
                            $sql = "select f_id from ".DBPref."insuredeclare where f_status=1 and f_dogid=".$row1['f_dogid'];
                            $res2 = $dbh->query($sql);
                            if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                              {
                                $ki = Array('curtbl'=>54,'curidx'=>$row1['f_id'],'f_declareid'=>$row2['f_id']);
                                editRowTbl($ki);
                              }
                            else
                              {
                                $sql = "select (ifnull(max(f_dt),0)+1) nextnum from ".DBPref."insuredeclare where f_dogid=".$row1['f_dogid'];
                                $res4 = $dbh->query($sql);
                                if($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                                  {
                                    $ki = Array('curtbl'=>262,'f_dogid'=>$row1['f_dogid'],'f_status'=>1,'f_dt'=>$row4['nextnum']);    
                                    $ar = json_decode(addRowTbl($ki), true);
                                    if($ar[0]=="true")
                                      {
                                        //$sql = "select f_id from ".DBPref."insuredeclare where f_dt=DATE_FORMAT(now(),'%Y-%m-01') and f_status=1 and f_dogid=".$row1['f_dogid'];
                                        $sql = "select f_id from ".DBPref."insuredeclare where f_dt=".$row4['nextnum']." and f_status=1 and f_dogid=".$row1['f_dogid'];
                                        $res3 = $dbh->query($sql);
                                        if($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                                          {
                                            $ki = Array('curtbl'=>54,'curidx'=>$row1['f_id'],'f_declareid'=>$row3['f_id']);
                                            editRowTbl($ki);
                                          }
                                      }
                                  }
                              } 
                          }
                      }
//sdid 1275
                    if($nstatus==3) // статус "В бухгалтерии"
                      { // проверим наличие обобщенного платежа по этой декларации. Если уже есть, новый не формируем
                      $sql = "select mp.f_id from ".DBPref."maspayfe mp, ".DBPref."dogs d
                              where mp.f_dogid=".$curidx." and mp.f_type=3 and d.f_id=".$row['f_dogid']." and mp.f_orgid=d.f_orgid and mp.f_clntid=d.f_contrid";
                      $res = $dbh->query($sql);
                      if($row = $res->fetch(PDO::FETCH_ASSOC)) 
                        {}
                      else
                        {
                        // ищем операции поручений на оплату страховок из включенных в декларацию
                        $sql = "select GROUP_CONCAT(si.f_id SEPARATOR ',') si_id_str
                                from ".DBPref."ensures e,".DBPref."specs s,".DBPref."spec_invoices si
                                where e.f_declareid=".$curidx." and s.f_id=e.f_objid and si.f_specid=s.f_id and si.f_idoper in (85,138,139,246) and 
                                  si.f_objtype=54 and si.f_objid=e.f_id";
                        $res = $dbh->query($sql);
                        if($row = $res->fetch(PDO::FETCH_ASSOC)) 
                          {
                          $selstr = $row['si_id_str'];
                          $ar = explode(",",$selstr);
                          $cca=count($ar);
                          if(count($ar)==0){$ar[0]=$selstr;}
                          if(count($ar)>0)
                            {
                            //---
                            $candomp = 1;
                            if($candomp==1)
                              {
                              $clntid = 0;$osum = 0;$oval = 0;$odogid = 0;$oorgid = 0;
                              $sql  = "select si.f_contrid,si.f_orgid,si.f_dogid,si.f_val,ifnull(si.f_sum,0) csum,substr(si.f_dttmcr,1,10) ppdt  
                                       from ".DBPref."spec_invoices si where si.f_id in ($selstr)";
                              $res1 = $dbh->query($sql);
                              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                                {
                                if($clntid == 0){$clntid=$row1['f_contrid'];}
                                $osum = $osum+$row1['csum'];
                                if($oval == 0){$oval=$row1['f_val'];}
                                if($odogid == 0){$odogid=$row1['f_dogid'];}
                                if($oorgid == 0){$oorgid=$row1['f_orgid'];}
                                $ppdt = $row1['ppdt'];
                                }
                              if($osum>0)
                                {
                                //$ki = Array('curtbl'=>230,'f_clntid'=>$clntid,'f_sum'=>$osum,'f_val'=>$oval,'f_type'=>1,'f_dogid'=>$odogid,'f_orgid'=>$oorgid,'f_ppdt'=>$ppdt);
                                $ki = Array('curtbl'=>230,'f_clntid'=>$clntid,'f_sum'=>$osum,'f_val'=>$oval,'f_type'=>3,'f_dogid'=>$curidx,'f_orgid'=>$oorgid,'f_ppdt'=>$ppdt);
                                $oar = json_decode(addRowTbl($ki), true);
                                if($oar[0]=="true")
                                  {
                                  $opc  = 0;
                                  while($opc<count($ar))
                                    {
                                    $oki = Array('curtbl'=>237,'f_maspayfeid'=>$oar[2],'f_operid'=>$ar[$opc]);
                                    $ooar = json_decode(addRowTbl($oki), true);
                                    if($ooar[0]=="true")
                                      {
                                      $okash = Array('curtbl'=>35,'curidx'=>$ar[$opc],'f_status'=>15);
                                      $oarsh = json_decode(editRowTbl($okash), true);
                                      //if($oarsh[0]=="true")
                                      //  {
                                      //    //$answ = $answ."Обновили статус операции (ИД ".$ar[$opc].").<br>";
                                      //  }
                                      }
                                    $opc++;
                                    }
                                  }
                                }
                              }
                            //---
                            }
                          }
                        }
                      }
//~sdid 1275
                  }
                } 
              }
//~sdid 1257
            elseif(strcmp($tblname,DBPref."ktk")==0)//обрабатываем обновление информации о КТК
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select f_status from $tblname where f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                if($row['f_status']!=$ostatus)//изменился статус КТК
                  {
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=37 and f_num=".$nstatus;
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=37 and f_num=".$nstatus;
                  //~sdid 703
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      if($menomes==0){                     
                        //sdid 703                
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 8, $curidx);}
                        else
                          {$subject = "По КТК изменен статус на ".$row1['f_name'];}
                        //~sdid 703
                        $postbody = "По <a href=\"".$redirect_uri."?pgid=103&obid=".$curidx."\">КТК</a> изменен статус на \"".$row1['f_name']."\"";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 8, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",103,$curidx);
                      }                     
                      }
                    }
                  }
                }
              }
            //~sdid - 164
            elseif(strcmp($tblname,DBPref."certificates")==0)//обрабатываем обновление информации о сертификатах
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select c.f_docdt,c.f_status,c.f_specid,s.f_subtype,s.f_dttoclnt from $tblname c,".DBPref."specs s where c.f_specid=s.f_id and c.f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                if($nstatus!=$ostatus)//изменился статус сертификата
                  {
                  // $sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=23 and f_num=".$nstatus;
                  //sdid - 164
                  //sdid 703
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=23 and f_num=".$nstatus;
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=23 and f_num=".$nstatus;
                  //~sdid 703
                  //~sdid - 164
                  //$ans=$ans.$sql;
                  $res1 = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['f_uslint']==1)
                      {
                      if(strcmp($row1['f_uslstr'],"2")==0)
                        {$mgrp=$row1['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row1['f_namedop'];}
                      if($menomes==0)
                        {
                        //sdid - 164
                        //sdid 703
                        if($row1['f_dopprstr1']>0)
                          {$subject = lGetPostShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 7, $curidx);}
                        else
                          {$subject = "По сертификату изменен статус на ".$row1['f_name'];}
                        //~sdid 703
                        $postbody = "По <a href=\"".$redirect_uri."?pgid=56&obid=".$curidx."\">сертификату</a> изменен статус на \"".$row1['f_name']."\"";
                        $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 7, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",56,$curidx);
                        //~sdid - 164
                        }
                      }
                    }
                  if(($nstatus==5)&&($row['f_subtype']==2))//Статус Готов по спецификации "Сертификация"
                    {
                    $ki = Array('curtbl'=>15,'curidx'=>$row['f_specid'],'f_status'=>16);
                    editRowTbl($ki);
                    }
                  }
                if(($odocdt!=$row['f_docdt'])&&($row['f_subtype']==2))//Изменилась дата оказания услуги по спецификации "Сертификация"
                  {
                  $nupds = 0;
                  if(strcmp($row['f_dttoclnt'],"0000-00-00")==0){$nupds = 1;}
                  elseif($row['f_dttoclnt']<$row['f_docdt']){$nupds = 1;}
                  if($nupds==1)
                    {
                    $ki = Array('curtbl'=>15,'curidx'=>$row['f_specid'],'f_dttoclnt'=>$row['f_docdt']);
                    editRowTbl($ki);
                    }
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."prjparts")==0)//после обновления обрабатываем Разделение ролей и объектам
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select f_typeobj,f_idobj,f_typerole,f_ismain,f_idsubj,f_zam from ".$tblname." where f_id=".$curidx;
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($row['f_ismain']>0)
                  {
                  $sql = "update ".$tblname." set f_ismain=0 where f_typeobj=".$row['f_typeobj']." and f_idobj=".$row['f_idobj'].
                         " and f_typerole=".$row['f_typerole']." and f_id<>".$curidx;
                  $dbh->exec($sql);
                  if(($row['f_typeobj']==1)&&($row['f_typerole']==1))
                    {
                    $sql = "update ".DBPref."specs set f_operid=".$row['f_idsubj'].",f_dttmcr=NOW(),f_userid=".$_SESSION['loginid']." where f_id=".$row['f_idobj'];
                    $dbh->exec($sql);
                    }
                  elseif(($row['f_typeobj']==1)&&($row['f_typerole']==2))
                    {
                    $sql = "update ".DBPref."specs set f_buhid=".$row['f_idsubj'].",f_dttmcr=NOW(),f_userid=".$_SESSION['loginid']." where f_id=".$row['f_idobj'];
                    $dbh->exec($sql);
                    }
                  elseif(($row['f_typeobj']==5)&&($row['f_typerole']==3))
                    {
                    $sql = "update ".DBPref."dt set f_operto=".$row['f_idsubj'].",f_dttmcr=NOW(),f_userid=".$_SESSION['loginid']." where f_id=".$row['f_idobj'];
                    $dbh->exec($sql);
                    }
                  }
                //отправляем уведомление сотруднику бухгалтерии, которого назначали замещающим бухгалтером по спецификации/заявке
                elseif(($row['f_typerole']==2)&&($row['f_typeobj']==1)&&($row['f_zam']==1)&&($row['f_zam']!=$ozam))
                  {mSendMail($_SESSION['loginid'],0,$row['f_idsubj'],"По спецификации/заявке Вы назначены замещающим бухгалтером.",
                      "По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_idobj']."\">спецификации/заявке</a> Вы назначены замещающим бухгалтером. ","",146,$curidx);
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."routes")==0)//если обновили запись в маршруте
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
/*              $sql = "select r.f_status,r.f_parentid,r.f_postid,r.f_p1dtp,r.f_p1dt,r.f_p1dt,r.f_p2dt,r.f_p2dtp,r.f_p2iscustom,
                        ifnull((select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id),0) cntpot,
                        ifnull((SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ') FROM ".DBPref."routes_ktk rk,".DBPref."ktk k 
                         where k.f_id=rk.f_ktkid and rk.f_routeid=r.f_id),'') ktk 
                      from ".$tblname." r where r.f_id=".$curidx;
*/
//sdid 962
/*              $sql = "select r.f_status,r.f_parentid,r.f_postid,r.f_p1dtp,r.f_p1dt,r.f_p1dt,r.f_p2dt,r.f_p2dtp,r.f_p2iscustom,
                        ifnull((select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id),0) cntpot,
                        ifnull((SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ') FROM ".DBPref."routes_ktk rk,".DBPref."ktk k 
                         where k.f_id=rk.f_ktkid and rk.f_routeid=r.f_id),'') ktk 
                      from ".$tblname." r where r.f_id=".$curidx;
*/
//    sdid 1429 $sql = "select r.f_status,r.f_parentid,r.f_postid,r.f_p1dtp,r.f_p1dt,r.f_p1dt,r.f_p2dt,r.f_p2dtp,r.f_p2iscustom,".
                $sql = "select r.f_status,r.f_parentid,r.f_postid,ifnull(r.f_p1dt,'0000-00-00') f_p1dt,ifnull(r.f_p2dt,'0000-00-00 00:00:00') f_p2dt,r.f_p2iscustom," .//sdid2860
                     //sdid 1214
                     "  r.f_com, " .
                     //~sdid 1214
                     //sdid 3131
                     "  r.f_p1dtppsdt, " .
                     //~sdid 3131
                     "  r.f_p1pointtype,r.f_p2pointtype,r.f_p1location,r.f_p2location,r.f_routetype, #sdid2860~#
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 
                            (select f_namedop from veda_spr where f_type=39 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED))
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=39 and f_num=(select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)))
                          else 0
                        end country1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED))
                          else 0
                        end city1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=39 then 0
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then 
                            CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)
                          else 0 
                        end point1num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p1location,1,2),UNSIGNED)=40 then 
                            case 
                              when CONVERT(SUBSTRING(r.f_p1location,3),UNSIGNED)>=10000 then 2
                              else 1
                            end
                          else 0 
                        end p1pointtyped,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 
                            (select f_namedop from veda_spr where f_type=39 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED))
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=39 and f_num=(select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)))
                          else 0
                        end country2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then
                            (select f_namedop from veda_spr where f_type=40 and f_num=CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED))
                          else 0
                        end city2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=38 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=39 then 0
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then 
                            CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)
                          else 0 
                        end point2num,
                        case 
                          when CONVERT(SUBSTRING(r.f_p2location,1,2),UNSIGNED)=40 then 
                            case 
                              when CONVERT(SUBSTRING(r.f_p2location,3),UNSIGNED)>=10000 then 2
                              else 1
                            end
                          else 0 
                        end p2pointtyped,
                        ifnull((select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id),0) cntpot,
                        ifnull((SELECT GROUP_CONCAT(distinct k.f_num SEPARATOR '; ') FROM ".DBPref."routes_ktk rk,".DBPref."ktk k 
                         where k.f_id=rk.f_ktkid and rk.f_routeid=r.f_id),'') ktk 
                      from ".$tblname." r where r.f_id=".$curidx;

//~sdid 962

              //if($_SESSION['loginid']==2){echo $sql."|";}
              //echo $sql."<br>";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
//sdid 962
// При изменении Локации отправления/Локации прибытия, обновляем Тип пункта, Страну, Город, Порт/Станцию
                //echo $op1location."|".$op2location;
                if(($row['f_p1location']!=$op1location)||($row['f_p2location']!=$op2location))
                  {
                  $p1ptype = $row['f_p1pointtype'];
                  if($row['p1pointtyped']>0){$p1ptype = $row['p1pointtyped'];}
                  $p2ptype = $row['f_p2pointtype'];
                  if($row['p2pointtyped']>0){$p2ptype = $row['p2pointtyped'];}
                  $ki = Array('curtbl'        =>77,
                              'curidx'        =>$curidx,
                              'f_p1country'   =>$row['country1num'],
                              'f_p1city'      =>$row['city1num'],
                              'f_p1point'     =>$row['point1num'],
                              'f_p1pointtype' =>$p1ptype,
                              'f_p2country'   =>$row['country2num'],
                              'f_p2city'      =>$row['city2num'],
                              'f_p2point'     =>$row['point2num'],
                              'f_p2pointtype' =>$p2ptype
                             );
                  editRowTbl($ki);

                    // Найдем Город, Страну, Тип пункта отправления
                    // $sql = "";


                  }
                //elseif(strcmp($row['f_p2location'],$ik['p2location'])!=0)
                //  {
                
                
                //  } 
//~sdid 962

//sdid 1429       if((strcmp($row['f_p2dtp'],$odt2)!=0)&&($row['f_p2iscustom']==1))
                //if ((strcmp($row['f_p2dt'], $odt2) != 0) && ($row['f_p2iscustom'] == 1))
                //  {
//sdid 887
// Отправить письмо-уведомление, используя шаблоны Темы и Тела письма
// operId   - Тип операции из lib.php -> getExtendedInfoTemplate() 
// $uvedNum - Номер Уведомления из справочника "Настройка уведомлений"
// $sprId - Номер справочника: Справочники -> Статусы + Настройка уведомлений. Разрпабатываем пока функционал по Настройка уведомлений
// $menuId - id меню
// $curidx - id таблицы меню
// function mSendMailByTemplates($userId, $subject, $postbody, $menuId, $curidx, $sprId, $operId, $uvedNum)
                //    $subject = "Изменена плановая дата прибытия в пункт ТО. КТК: ".$row['ktk'];
//sdid 1429           $postbody = "КТК: ".$row['ktk'].".<br>Изменена плановая дата прибытия в пункт ТО на ".$row['f_p2dtp'].
                //    $postbody = "КТК: " . $row['ktk'] . ".<br>Изменена плановая дата прибытия в пункт ТО на " . $row['f_p2dt'] .
                //                ".<br><a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">ссылка на маршут</a>";
                //    $sbsh = 0;
                //    if($menomes==0) { $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 77, $curidx, 151, 9, 1); }                     
                //    if($sbsh == 0) { mSendMail($_SESSION['loginid'],18,0,$subject,$postbody,"",77,$curidx); }

// ----------------------- orig
//                  mSendMail($_SESSION['loginid'],18,0,"Изменена плановая дата прибытия в пункт ТО. КТК: ".$row['ktk'],
//                   "КТК: ".$row['ktk'].".<br>Изменена плановая дата прибытия в пункт ТО на ".$row['f_p2dtp'].
//                   ".<br><a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">ссылка на маршут</a>","",77,$curidx);
// ----------------------- ~orig
//~sdid 887
                //  }
                //sdid2860
                if($row['f_routetype']==5)
                  {
                  if((strcmp($row['f_p2dt'],$odt2)!=0)||(strcmp($row['f_p1dt'],$odt1)!=0))
                    {
                    $subject = "Изменена дата отправления/прибытия по маршруту ЖД РФ";

                    if(strcmp($odt1,"0000-00-00")===0){$p1dto="пусто";}
                    else{$p1dto=format_dt($odt1,0,0);}

                    if(strcmp($row['f_p1dt'],"0000-00-00")===0){$p1dt="пусто";}
                    else{$p1dt=format_dt($row['f_p1dt'],0,0);}

                    if((strcmp($odt2,"0000-00-00")===0)||(strcmp($odt2,"0000-00-00 00:00:00")===0)){$p2dto="пусто";}
                    else{$p2dto=format_dt($odt2,0,0)." ".substr($odt2,11,8);}
                    //$p2dto=$odt2;
                    if((strcmp($row['f_p2dt'],"0000-00-00")===0)||(strcmp($row['f_p2dt'],"0000-00-00 00:00:00")===0)){$p2dt="пусто";}
                    else{$p2dt=format_dt($row['f_p2dt'],0,0)." ".substr($row['f_p2dt'],11,8);}
                    //$p2dt=$row['f_p2dt'];
                    $p1dtstr = "";
                    if(strcmp($row['f_p1dt'],$odt1)!=0){$p1dtstr = "Дата отправления: было ".$p1dto.", стало ".$p1dt."<br>";}
                    else{$p1dtstr = "Дата отправления: ".$p1dto."<br>";}
                    $p2dtstr = "";
                    if(strcmp($row['f_p2dt'],$odt2)!=0){$p2dtstr = "Дата прибытия: было ".$p2dto.", стало ".$p2dt."<br>";}
                    else{$p2dtstr = "Дата прибытия: ".$p2dto."<br>";}
                    $postbody = "Изменена дата отправления/прибытия по маршруту ЖД РФ:<br>".
                                $p1dtstr."".
                                $p2dtstr.
                                "Комментарий: ".$row['f_com']."<br>".
                                "<a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">ссылка на маршут</a><br>";
                    $sbsh = 0;
                    if($menomes==0){$sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 77, $curidx, 151, 9, 17);}
                    if($sbsh==0){mSendMail($_SESSION['loginid'],18,0,$subject,$postbody,"",77,$curidx);}
                    }
                  }
                //~sdid2860
                if((strcmp($row['f_p2dt'],$odtf2)!=0)&&($row['f_p2iscustom']==1))
                  {
//sdid 887
                    $subject = "Изменена фактическая дата прибытия в пункт ТО. КТК: ".$row['ktk'];
                    $postbody = "КТК: ".$row['ktk'].".<br>Изменена фактическая дата прибытия в пункт ТО на ".$row['f_p2dt'].
                                ".<br><a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">ссылка на маршут</a>";
                    $sbsh = 0;
                    if($menomes==0) { $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 77, $curidx, 151, 9, 2); }                     
                    if($sbsh == 0) { mSendMail($_SESSION['loginid'],18,0,$subject,$postbody,"",77,$curidx); }
// ----------------------- orig
//                  mSendMail($_SESSION['loginid'],18,0,"Изменена фактическая дата прибытия в пункт ТО. КТК: ".$row['ktk'],
//                   "КТК: ".$row['ktk'].".<br>Изменена фактическая дата прибытия в пункт ТО на ".$row['f_p2dt'].
//                   ".<br><a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">ссылка на маршут</a>","",77,$curidx);
// ----------------------- ~orig
//~sdid 887
                  //sdid 2084 tz2 2
                  // Если изменилась дата прибытия, меняем ее также в veda_specs
                  if($row['f_postid']>0)
                    {  
                    $sql1 = "select f_id from veda_specs where f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ndtarrivalto = $row['f_p2dt'];
                      if(strlen($row['f_p2dt'])==0) {$ndtarrivalto = "0000-00-00 00:00:00";}
                      $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id'],'f_dtarrivalto'=>$ndtarrivalto);
                      editRowTbl($ki);
                      }
                    }
                  //~sdid 2084 tz2 2
                  }
                //if($_SESSION['loginid']==2){echo $sql."|";}
                $nstatus = $row['f_status'];
                //sdid 3131
                //Если установлена галка "Дата отправки = Дата перехода прав собственности" и маршрут в статусе "Прибыл", 
                // в связанных спецификациях устанавливаем дату ППС равную "Дата отправки" маршрута с галкой "Дата отправки = Дата перехода прав собственности"
                //error_log("\n\nf_p1dtppsdt=".$row['f_p1dtppsdt']."\nop1dtppsdt=".$op1dtppsdt."\nf_status=".$row['f_status']."\nostatus=".$ostatus."\nf_p1dt=".$row['f_p1dt']."\nodt1=".$odt1."\n\n\n",0);
                if(($row['f_p1dtppsdt']==1)&&($nstatus==5))
                  { // Если изменилась галка "Дата отправки = Дата перехода прав собственности", или статус маршрута, или дата отправления 
                  if( ($op1dtppsdt!=$row['f_p1dtppsdt']) || ($ostatus!=$row['f_status']) || (strcmp($odt1,$row['f_p1dt'])!=0) )
                    {
                    //$sql = "select s.f_id from veda_specs s,veda_routes r where s.f_postid=r.f_postid and r.f_id=$curidx";
                    $sql = " select distinct s.f_id from ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                              where ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                    AND r.f_id=$curidx";
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $ars = Array('curtbl'=>15,'curidx'=>$row1['f_id'],'f_perpravdt'=>$row['f_p1dt']);
                      editRowTbl($ars);
                      }
                    }
                  }
                //~sdid 3131
                //sdid 3273
                // Если маршрут с изменившейся и установленной галкой "Дата отправки = Дата ППС", снимаем галки во всех остальных маршрутах в поставке
                if(($row['f_postid']>0)&&($op1dtppsdt!=$row['f_p1dtppsdt'])&&($row['f_p1dtppsdt']==1))
                  {
                  $sql = "select f_id from veda_routes where f_postid=".$row['f_postid']." and f_id<>$curidx";
                  $res2 = $dbh->query($sql);
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $r_update = ['curtbl'=>77,'curidx'=>$row2['f_id'],'f_p1dtppsdt'=>0]; 
                    $r_result = editRowTbl($r_update);
                    }
                  }
                //~sdid 3273
//sdid 1140
                if((strcmp($row['f_p2dt'],$odtf2)!=0) && ($nstatus==$ostatus)) // Изменили дату прибытия у маршрута со статусом прибыл, без изменения статуса
                  {
                    $sql = "select rs.f_specid, concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname, ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt
                              from veda_routes_spec rs, veda_specs s, veda_dogs d
                             where rs.f_routeid=".$curidx." 
                               and s.f_id=rs.f_specid
                               and d.f_id=s.f_dogid";
                    $res1 = $dbh->query($sql);
                    $cntRoutes=0;
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      { // если дата получения груза в спецификации выставлена и меньше даты прибытия в маршруте, меняем дату в спецификации
                        if((strcmp($row1['f_dttoclnt'],"0000-00-00")!=0) && (strtotime($row1['f_dttoclnt']) < strtotime($row['f_p2dt'])))
                          {
                            $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>$row['f_p2dt']);
                            editRowTbl($ki);
                           
                            $subject = "Изменилась дата получения товара по Спец/Заявке № ".$row1['specname'];
                            $postbody = "Изменилась дата получения товара по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                            if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']); }
                          }
                        $cntRoutes++;
                      }
                    if($cntRoutes==0)
                      {
                        //$sql = "select s.f_id from veda_routes r, veda_specs s where s.f_postid=r.f_postid and r.f_id=".$curidx;
                        $sql  = "select s.f_id, concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname
                                   from veda_routes r, veda_specs s, veda_dogs d	
                                  where s.f_postid=r.f_postid
                                    and s.f_dttoclnt IS NOT NULL and date(s.f_dttoclnt) <> '0000-00-00' and date(s.f_dttoclnt) < '".$row['f_p2dt']."'   
                                    and d.f_id=s.f_dogid
                                    and r.f_id=".$curidx;
                        $res1 = $dbh->query($sql);
                        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {
                            $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id'],'f_dttoclnt'=>$row['f_p2dt']);
                            editRowTbl($ki);
                       
                            $subject = "Изменилась дата получения товара по Спец/Заявке № ".$row1['specname'];
                            $postbody = "Изменилась дата получения товара по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_id']."\">Спец/Заявке № ".$row1['specname']."</a>";
                            if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_id']); }                     
                          }
                      }
                  }
//~sdid 1140 
                if($nstatus!=$ostatus)//изменился статус маршрута
                  {
                  ////отправляем сообщения по статусу маршрута
                  //$sql = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=43 and f_num=".$nstatus;
                  ////$ans=$ans.$sql;
                  //$res1 = $dbh->query($sql);
                  //if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  //  {
                  //  if($row1['f_uslint']==1)
                  //    {
                  //    if(strcmp($row1['f_uslstr'],"2")==0)
                  //      {$mgrp=$row1['f_namedop'];$musr=0;}
                  //    else
                  //      {$mgrp=0;$musr=$row['f_namedop'];}
                  //    //$ans=$ans."|".$mgrp."_".$musr;
                  //    if($menomes==0){mSendMail($_SESSION['loginid'],$mgrp,$musr,"По маршруту изменен статус на ".$row1['f_name'],
                  //      "По <a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">маршруту</a> изменен статус на \"".$row1['f_name']."\"","",77,$curidx);}
                  //    }
                  //  }
                  //sdid 859
                  $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=43 and f_num=".$nstatus;
                  //if($_SESSION['loginid']==2){echo $sql."|";}
                  $res2 = $dbh->query($sql);
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row2['f_uslint']==1)
                      {
                      if(strcmp($row2['f_uslstr'],"2")==0)
                        {$mgrp=$row2['f_namedop'];$musr=0;}
                      else
                        {$mgrp=0;$musr=$row2['f_namedop'];}
                      //$ans=$ans."|".$mgrp."_".$musr;
                      if($menomes==0)
                        {
                        if($row2['f_dopprstr1']>0)
                          {$subject = lGetPostSubjShablon($row2['f_dopprstr1'], 0, 0, 0, 0, 2, $curidx);}
                        else
                          {$subject = "По маршруту изменен статус на ".$row2['f_name'];}
                        $postbody = "По <a href=\"".$redirect_uri."?pgid=77&obid=".$curidx."\">маршруту</a> изменен статус на \"".$row2['f_name']."\"<br>";
                        $postbody = $postbody . lGetPostShablon($row2['f_dopprint'], 0, 0, 0, 0, 2, $curidx);
                        mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",77,$curidx);
                        }
                      }
                    }
                  //~sdid 859


                  if(($nstatus==1)&&($row['f_parentid']==0))//если статус погрузка
                    {
                    $ap   = 1;
                    $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($row['f_status']!=$nstatus){$ap=0;}}
                    if($ap==1)
                      {
                      $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>1);
                      editRowTbl($ki);
                      }
                    }
                  elseif(($nstatus==2)&&($row['f_parentid']==0))//если статус в пути у 0-го маршрута
                    {
                    $ap   = 1;
                    $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($row['f_status']!=$nstatus){$ap=0;}}
                    if($ap==1)//если все 0-е маршруты в пути, то доставку делаем в пути
                      {
                      $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>2,'f_p1dt'=>$row['f_p1dt']);
                      editRowTbl($ki);
                      }
                    }
                  //sdid 2043
                  elseif(($nstatus==7)&&($row['f_parentid']==0))//если статус Ожидание отправления у 0-го маршрута
                    {
                    $ap   = 1;
                    $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {if($row['f_status']!=$nstatus){$ap=0;}}
                    if($ap==1)//если все 0-е маршруты в статусе Ожидание отправления, то доставку делаем в Ожидание отправления
                      {
                      //Выставляем статус Ожидание отправления у доставки
                      $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>7);
                      editRowTbl($ki);

                      // Ищем ID спецификаций, связанных с маршрутом
                      $sql  = "select f_id, f_status from veda_specs where f_postid=".$row['f_postid']; 
                      $res2 = $dbh->query($sql);
                      while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row2['f_status']!=4) // меняем статус спецификаций на Спец. Перед отправкой, если был иной
                          {
                          $ki = Array('curtbl'=>15,'curidx'=>$row2['f_id'],'f_status'=>4);
                          editRowTbl($ki);
                          }
                        }
                      }
                    }
                  //~sdid 2043
                  elseif($nstatus==8)//если статус запланировано
                    {
                    if($row['f_parentid']==0)//у 0-го маршрута
                      {
                      $ap   = 1;
                      $sql  = "select f_status from ".$tblname." where f_parentid=0 and f_postid=".$row['f_postid'];
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {if($row['f_status']!=$nstatus){$ap=0;}}
                      if($ap==1)//если все 0-е маршруты запланированы
                        {
                        $ki = array('curtbl' => 102, 'curidx' => $row['f_postid'], 'f_status' => 8, 'f_p1dt' => $row['f_p1dt']); // sdid 1429
                        editRowTbl($ki);
                        }
                      }
                    if($row['f_p2iscustom']==1)//если маршрут с таможней
                      {
                      $ap   = 1;
                      $sql  = "select r.f_status from ".$tblname." r where r.f_p2iscustom=1 and r.f_postid=".$row['f_postid'];
                      //echo "|".$sql."|";
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {if($row['f_status']!=$nstatus){$ap=0;}}
                      if($ap==1)//если все маршруты с таможней запланированы, то ставим дату судозахода запланированной
                        {
// sdid 1429              $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_pBdt'=>$row['f_p2dtp']);
                        $ki = array('curtbl' => 102, 'curidx' => $row['f_postid'], 'f_pBdt' => $row['f_p2dt']);
                        editRowTbl($ki);
                        }
                      }
                    if($row['cntpot']==0)//если последний маршрут
                      {
                      $ap   = 1;
                      $sql  = "select r.f_status from ".$tblname." r where (select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id)=0 and r.f_postid=".$row['f_postid'];
                      //echo "|".$sql."|";
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {if($row['f_status']!=$nstatus){$ap=0;}}
                      if($ap==1)//если все последние маршруты запланированы, то доставку делаем запланированной и выставляем плановую дату конца
                        {
//sdid 1429               $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>8,'f_p2dt'=>$row['f_p2dtp']);
                        $ki = array('curtbl' => 102, 'curidx' => $row['f_postid'], 'f_status' => 8, 'f_p2dt' => $row['f_p2dt']);
                        editRowTbl($ki);
                        }
                      }
                    }
                  elseif($nstatus==5)//если статус Прибыл
                    {
                    //echo $nstatus."_";
                    $p2dt = date('Y-m-d');
                    if(!isset($row['f_p2dt'])){$ki = Array('curtbl'=>77,'curidx'=>$curidx,'f_p2dt'=>$p2dt);editRowTbl($ki);}
                    else
                      {
                      if(strcmp($row['f_p2dt'],"0000-00-00")==0)
                        {$ki = Array('curtbl'=>77,'curidx'=>$curidx,'f_p2dt'=>$p2dt);editRowTbl($ki);}
                      else{$p2dt = $row['f_p2dt'];}
                      }
                    //echo "|".$p2dt."|";
                    //echo $nstatus."_";
                    if($row['f_p2iscustom']==1)//проверяем все ли контейнеры добрались до таможни
                      {
                      $ap   = 1;
                      $sql  = "select r.f_status from ".$tblname." r where r.f_p2iscustom=1 and r.f_postid=".$row['f_postid'];
                      //echo "|".$sql."|";
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {if($row['f_status']!=$nstatus){$ap=0;}}
                      if($ap==1)//если все таможенные маршруты доставлены 
                        {
                        $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_pBdt'=>$p2dt);
                        editRowTbl($ki);
                        }
                      }
                    if(($row['f_parentid']>0)&&($row['cntpot']==0))//если последний маршрут
                      {
                      $ap   = 1;
                      $sql  = "select r.f_status from ".$tblname." r where (select count(*) from ".$tblname." where f_postid=r.f_postid and f_parentid=r.f_id)=0 and r.f_postid=".$row['f_postid'];
                      //echo "|".$sql."|";
                      $res1 = $dbh->query($sql);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {if($row1['f_status']!=$nstatus){$ap=0;}}
                      if($ap==1)//если все маршруты доставлены
                        {
                        //выставляем доставке статус - прибыл и дату доставки
                        $ki = Array('curtbl'=>102,'curidx'=>$row['f_postid'],'f_status'=>5,'f_p2dt'=>$p2dt);
                        editRowTbl($ki);
//sdid 1140
//                        //выставляем "Дата передачи груза клиенту" у спецификаций, связанных с доставкой
////sdid 908
//                        // Если дата прибытия маршрута больше даты передачи груза клиенту, эту дату укажем в спецификациях
//                        // как "Дата передачи груза клиенту"
//                        // row['f_p2dt']; // фактическая дата прибытия последнего маршрута
//                        $sql = "select s.f_id from veda_specs s where s.f_postid=".$row['f_postid'].
//                               " and (s.f_dttoclnt < '".$p2dt."' or s.f_dttoclnt is NULL or s.f_dttoclnt = '0000-00-00')";
////~sdid 908
//                        $res1 = $dbh->query($sql);
//                        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
//                          {
//                          $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id'],'f_dttoclnt'=>$p2dt,'f_status'=>16);
//                          editRowTbl($ki);
//                          }

//~sdid 1140
                        }
                      }
//sdid 1140
                    // связка маршрут-спецификация в routes_spec, тогда берем спецификации из данной связки - чаще всего используется для сборных контейнеров
                    $sql = "select rs.f_specid,
                                   rs.f_routeid,
                                   r.f_status,
                                   ifnull(date(r.f_p2dt),'0000-00-00') fp2dt,
                                   concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname
                              from veda_routes_spec rs,
                                   veda_routes r,
                                   veda_specs s,
                                   veda_dogs d
                             where rs.f_routeid=".$curidx." 
                               and r.f_id=rs.f_routeid
                               and s.f_id=rs.f_specid
                               and d.f_id=s.f_dogid and s.f_sbor=0"; // sdid 2239
//                               and d.f_id=s.f_dogid"; sdid 2239
                    $cntRoutes = 0;
                    $cntArrived = 0;
                    $maxDt = "0000-00-00";
                    $isFindedInRoutesSpecTbl = 0;
                    $res1 = $dbh->query($sql);
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                        $isFindedInRoutesSpecTbl++; 
                        // идем по спецификациям
                        ////// ограничим поиск: если f_status <> 5. то есть маршрут не прибыл, и если дата прибытия = '0000-00-00', смысла поиска по этой спецификации уже нет
                        // ищем все маршруты по каждой спецификации. Если все в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации
                        $cntRoutes = 1;
                        $cntArrived = 0;
                        $maxDt = $row1['fp2dt'];
                        if(($row1['f_status'] == 5) && (strcmp($row1['fp2dt'],"0000-00-00")!=0))
                          {
                            $cntArrived++;
                            $maxDt = $row1['fp2dt'];
                            $sql = "select r.f_status, ifnull(date(r.f_p2dt),'0000-00-00') fp2dt
                                      from veda_specs s, veda_routes r
                                     where s.f_id=".$row1['f_specid']." and r.f_postid=s.f_postid";
                            $res2 = $dbh->query($sql);
                            // идем по маршрутам, связанным со спецификацией
                            while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                              {
                                $cntRoutes++;
                                if(($row2['f_status'] == 5) && (strcmp($row2['fp2dt'],"0000-00-00")!=0))
                                  {
                                    $cntArrived++;
                                    if(strtotime($row2['fp2dt']) > strtotime($maxDt)) {$maxDt = $row2['fp2dt'];}
                                  }
                              }
                          }
                        if($cntRoutes == $cntArrived) // если все маршруты в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации 
                          {
                            //выставляем "Дата передачи груза клиенту" у спецификаций и отправляем соответственное оповещение 
                            $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>$maxDt,'f_status'=>16);
                            editRowTbl($ki);
                           
                            $subject = "Дата передачи груза ".$maxDt." по Спец/Заявке № ".$row1['specname'];
                            $postbody = "Дата передачи груза ".$maxDt." по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                            if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']); }                     
                          }
                      }
                    if($isFindedInRoutesSpecTbl == 0) // если ничего не нашли в veda_routes_spec, ищем по связке veda_specs.f_postid=veda-routes.f_postid
                      {
                        $sql = "select s.f_id f_specid,
                                       r.f_id routeid,
                                       r.f_status,
                                       ifnull(date(r.f_p2dt),'0000-00-00') fp2dt,
                                       concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname
                                  from veda_routes r,
                                       veda_specs s,
                                       veda_dogs d
                                 where r.f_id=".$curidx." 
                                   and s.f_postid=r.f_postid
                                   and d.f_id=s.f_dogid and s.f_sbor=0"; // sdid 2239
//                                   and d.f_id=s.f_dogid"; sdid 2239
                        $cntRoutes = 0;
                        $cntArrived = 0;
                        $maxDt = "0000-00-00";
                        $res1 = $dbh->query($sql);
                        while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                          {
                            // идем по спецификациям
                            // ищем все маршруты по каждой спецификации. Если все в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации
                            $cntRoutes = 1;
                            $cntArrived = 0;
                            $maxDt = $row1['fp2dt'];
                            if(($row1['f_status'] == 5) && (strcmp($row1['fp2dt'],"0000-00-00")!=0))
                              {
                                $cntArrived++;
                                $maxDt = $row1['fp2dt'];
                                $sql = "select r.f_status, ifnull(date(r.f_p2dt),'0000-00-00') fp2dt
                                          from veda_specs s, veda_routes r
                                         where s.f_id=".$row1['f_specid']." and r.f_postid=s.f_postid";
                                $res2 = $dbh->query($sql);
                                // идем по маршрутам, связанным со спецификацией
                                while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                                  {
                                    $cntRoutes++;
                                    if(($row2['f_status'] == 5) && (strcmp($row2['fp2dt'],"0000-00-00")!=0))
                                      {
                                        $cntArrived++;
                                        if(strtotime($row2['fp2dt']) > strtotime($maxDt)) {$maxDt = $row2['fp2dt'];}
                                      }
                                  }
                              }
                            if($cntRoutes == $cntArrived) // если все маршруты в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации 
                              {
                                //выставляем "Дата передачи груза клиенту" у спецификаций и отправляем соответственное оповещение 
                                $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>$maxDt,'f_status'=>16);
                                editRowTbl($ki);
                               
                                $subject = "Дата передачи груза ".$maxDt." по Спец/Заявке № ".$row1['specname'];
                                $postbody = "Дата передачи груза ".$maxDt." по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                                if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']); }                     
                              }
                          }
                      } //~if($isFindedInRoutesSpecTbl == 0)
//~sdid 1140
                    $spm  = [];$spn=0;$snm = [];
                    $ap   = 1;$winr=0;
                    $sql  = "select rs.f_specid,nr.f_status,concat(d.f_dogname,'/',s.f_num) sname,s.f_id spid 
                             from ".DBPref."routes r,".DBPref."routes nr,".DBPref."routes_spec rs,".DBPref."routes_spec nrs, 
                                  ".DBPref."specs s,".DBPref."dogs d 
                             where rs.f_routeid=r.f_id and nrs.f_routeid=nr.f_id and nrs.f_specid=rs.f_specid and 
                                   s.f_id=rs.f_specid and d.f_id=s.f_dogid and r.f_id=".$curidx." order by s.f_id,nr.f_parentid";
                    //echo $sql."|";
                    $res1 = $dbh->query($sql);
                    $cspid = 0;$cspn = "";
                    while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      $winr = 1;
                      if($cspid==0){$cspid=$row1['spid'];$cspn=$row1['sname'];$ap=1;if($row1['f_status']!=$nstatus){$ap=0;}}
                      if($row1['f_status']!=$nstatus){$ap=0;}
                      if($cspid!=$row1['spid'])
                        {
                        if($ap==1){$spm[$spn]=$cspid;$snm[$spn]=$cspn;$spn++;}
                        $cspid=$row1['spid'];$cspn=$row1['sname'];$ap=1;if($row1['f_status']!=$nstatus){$ap=0;}
                        }
                      }
                    if(($winr==1)&&($ap==1)){$spm[$spn]=$cspid;$snm[$spn]=$cspn;$spn++;}
                    //echo $spn."|".$spm[$spn-1]."|".$snm[$spn-1]."|";
                    if(($winr==1)&&(count($spm)>0))//если нашли все маршруты по связке маршрут-спецификация для сборных КТК
                      {
                      $cmc = 0;
                      while($cmc<count($spm))
                        {
                        //sdid887 sdid3480
                        $subject = "Прибыл последний маршрут по спецификации ".$snm[$cmc]." (1)";
                        $postbody = "Прибыл последний маршрут по  <a href=\"".$redirect_uri."?pgid=15&obid=".$spm[$cmc]."\">спецификации ".$snm[$cmc]."</a>";
                        $sbsh = 0;
                        if($menomes==0) { $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 15, $spm[$cmc], 151, 3, 3); }                     
                        if($sbsh == 0) { mSendMail($_SESSION['loginid'],13,0,$subject,$postbody,"",15,$spm[$cmc]); }
                        // ----------------------- orig
                        /*mSendMail($_SESSION['loginid'],13,0,"Прибыл последний маршрут по спецификации ".$snm[$cmc],
                        "Прибыл последний маршрут по  <a href=\"".$redirect_uri."?pgid=15&obid=".$spm[$cmc]."\">спецификации ".$snm[$cmc]."</a>","",
                        15,$spm[$cmc]);*/
                        //----------------------- ~orig
                        //~sdid887 sdid3480
                        $cmc++;
                        }
                      }
                    else//ищем по стандартной связке маршрут-поставка-специфкация
                      {
                      $sql = "select nr.f_id,s.f_id,nr.f_status,concat(d.f_dogname,'/',sp.f_num) sname,sp.f_id spid ".
                             "from ".DBPref."specs s,".DBPref."specs ns,".DBPref."routes r,".DBPref."routes nr,".
                             "     ".DBPref."specs sp,".DBPref."dogs d ".
                             "where ns.f_id=s.f_id and ns.f_postid=nr.f_postid and s.f_postid=r.f_postid and ".
                             "      sp.f_id=s.f_id and d.f_id=sp.f_dogid and r.f_id=".$curidx." order by sp.f_id";
                      //echo $sql."|";
                      $res1 = $dbh->query($sql);
                      $cspid = 0;$cspn = "";
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        $winr = 1;
                        if($cspid==0){$cspid=$row1['spid'];$cspn=$row1['sname'];$ap=1;}
                        if($row1['f_status']!=$nstatus){$ap=0;}
                        if($cspid!=$row1['spid'])
                          {
                          if($ap==1){$spm[$spn]=$cspid;$snm[$spn]=$cspn;$spn++;}
                          $cspid=$row1['spid'];$cspn=$row1['sname'];$ap=1;
                          }
                        }
                      if(($winr==1)&&($ap==1)){$spm[$spn]=$cspid;$snm[$spn]=$cspn;$spn++;}
                      //echo $spn."|".$spm[$spn-1]."|".$snm[$spn-1]."|";
                      if(count($spm)>0)
                        {
                        $cmc = 0;
                        while($cmc<count($spm))
                          {
                          //sdid3480
                          $subject  = "Прибыл последний маршрут по спецификации ".$snm[$cmc]." (2)";
                          $postbody = "Прибыл последний маршрут по  <a href=\"".$redirect_uri."?pgid=15&obid=".$spm[$cmc]."\">спецификации ".$snm[$cmc]."</a>";
                          $sbsh = 0;
                          if($menomes==0){ $sbsh = mSendMailByTemplates($_SESSION['loginid'], "", $postbody, 15, $spm[$cmc], 151, 3, 3); }                     
                          if($sbsh == 0) { mSendMail($_SESSION['loginid'],13,0,$subject,$postbody,"",15,$spm[$cmc]); }
                          //mSendMail($_SESSION['loginid'],13,0,"Прибыл последний маршрут по спецификации ".$snm[$cmc]." (2)",
                          //  "Прибыл последний маршрут по  <a href=\"".$redirect_uri."?pgid=15&obid=".$spm[$cmc]."\">спецификации ".$snm[$cmc]."</a>","",
                          //  15,$spm[$cmc]);
                          //~sdid3480
                          $cmc++;
                          }                      
                        }
                      }

                    //sdid 1429 Поле "Дата внесения информации о передаче груза" заполняется только если все связанные маршруты имеют статус "прибыл"

                    $sql_specs = "SELECT specs.f_id
                                  FROM " . DBPref . "routes routes, " . DBPref . "routes_spec routes_spec, " . DBPref . "specs specs 
                                  WHERE routes.f_id=routes_spec.f_routeid AND specs.f_id=routes_spec.f_specid AND specs.f_sbor=0 AND routes.f_id=" . $curidx; // sdid 2239
//                                  WHERE routes.f_id=routes_spec.f_routeid AND specs.f_id=routes_spec.f_specid AND routes.f_id=" . $curidx; sdid 2239
                    $res_specs = $dbh->query($sql_specs);
                    while ($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                      {
                      $specs_update = [];
                      $sql_routes = "SELECT COUNT(routes.f_id) r_count, COUNT(CASE routes.f_status WHEN 5 THEN 1 ELSE NULL END) s_count
                                     FROM " . DBPref . "specs specs, " . DBPref . "routes routes, " . DBPref . "shipments shipments 
                                     WHERE specs.f_postid=shipments.f_id AND routes.f_postid=shipments.f_id AND specs.f_id=" . $row_specs['f_id'];
                      $res_routes = $dbh->query($sql_routes);

                      if ($row_routes = $res_routes->fetch(PDO::FETCH_ASSOC))
                        {
                        if ($row_routes['r_count'] == $row_routes['s_count'] && $row_routes['r_count'] > 0)
                          {
                          $specs_update['f_dtaddpergruz'] = date("Y.m.d", time()); // Дата внесения информации о передаче груза
                          }
                        }

                      if (!empty($specs_update))
                        {
                        $specs_update["curtbl"] = 15;
                        $specs_update["curidx"] = $row_specs['f_id'];
                        editRowTbl($specs_update);
                        }
                      }
                    //~sdid 1429

                    }
//sdid 1140
                  // если у маршрута БЫЛ статус "Доставлен", но изменился на другой:
                  // ищем связанные спецификации и аннулируем Дату передачи груза клиенту в спецификации
                  if($ostatus==5 && $nstatus!=5)
                    {
                      //$sql = "select rs.f_specid from veda_routes_spec rs where rs.f_routeid=".$curidx;
                      $sql = "select rs.f_specid,
                                     r.f_status,  
                                     concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname,
			             rs.f_routeid,
			             (select f_name from veda_spr where f_type=43 and f_num=r.f_status) status_route_name,
                                     ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt
                                from veda_routes_spec rs, veda_specs s, veda_dogs d, veda_routes r 
                               where rs.f_routeid=".$curidx." 
                                 and s.f_id=rs.f_specid
                                 and d.f_id=s.f_dogid
                                 and r.f_id=f_routeid";
                      $res1 = $dbh->query($sql);
                      $cntRoutes=0;
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                          if(strcmp($row1['f_dttoclnt'],"0000-00-00")!=0) // если выставлена дата получения товара в спецификации
                            {
                              $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>"0000-00-00");
                              editRowTbl($ki);
                            
                              $subject = "Изменился статус маршрута с 'Доставлено' на '".$row1['status_route_name']."' по Спец/Заявке № ".$row1['specname'];
                              $postbody = "Изменился статус маршрута с 'Доставлено' на '".$row1['status_route_name']."' по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                              if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']); }
                            }
                          $cntRoutes++;
                        }
                      if($cntRoutes==0)
                        {
                          //$sql = "select s.f_id from veda_routes r, veda_specs s where s.f_postid=r.f_postid and r.f_id=".$curidx;
                          $sql  = "select s.f_id,
                                          concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname, 
                                          (select f_name from veda_spr where f_type=43 and f_num=r.f_status) status_route_name	 
                                     from veda_routes r, veda_specs s, veda_dogs d	
                                    where s.f_postid=r.f_postid
                                      and s.f_dttoclnt IS NOT NULL and date(s.f_dttoclnt) <> '0000-00-00'  
                                      and d.f_id=s.f_dogid
                                      and r.f_id=".$curidx;
                          $res1 = $dbh->query($sql);
                          while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                            {
                              $ki = Array('curtbl'=>15,'curidx'=>$row1['f_id'],'f_dttoclnt'=>"0000-00-00");
                              editRowTbl($ki);
                         
                              $subject = "Изменился статус маршрута с 'Доставлено' на '".$row1['status_route_name']."' по Спец/Заявке № ".$row1['specname'];
                              //sdid 2043
                              //$postbody = "Изменился статус маршрута с 'Доставлено' на '".$row1['status_route_name']."' по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                              $postbody = "Изменился статус маршрута с 'Доставлено' на '".$row1['status_route_name']."' по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_id']."\">Спец/Заявке № ".$row1['specname']."</a>";
                              //~sdid 2043
                              if($menomes==0) { mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']); }                     
                            }
                        }
                    }
                  //~sdid 1140
                  // sdid 1756
                  if($nstatus == 2) // статус "В пути" у любого маршрута
                    {
                     $sql_rs = "SELECT DISTINCT s.f_id 
                                FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                                WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                AND r.f_id = ".$curidx;
                    $conn_rs = $dbh->query($sql_rs);
                    while ($row_rs = $conn_rs->fetch(PDO::FETCH_ASSOC)) // Берем каждую спецификацию связанную с маршрутом
                      {
                      $spec_id = $row_rs['f_id'];
                      $spec_update = ['curtbl' => 15, 'curidx' => $spec_id, 'f_status' => 15]; // Выставляем статус "В пути"
                      $update_result = editRowTbl($spec_update);
                      }
                    }
                  if($nstatus == 5) // статус "Прибыл" у любого маршрута
                    {
                    $sql_rs = "SELECT DISTINCT s.f_id 
                               FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                               WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) 
                                 AND s.f_sbor=0 AND r.f_id = ".$curidx; // sdid 2239
//                                  AND r.f_id = ".$curidx; sdid 2239
                    $conn_rs = $dbh->query($sql_rs);
                    while ($row_rs = $conn_rs->fetch(PDO::FETCH_ASSOC)) // Проверяем каждую спецификацию связанную с маршрутом
                      {
                      $spec_id = $row_rs['f_id'];
                      $sql_rs1 = "SELECT COUNT(DISTINCT r.f_id) total, 
                                    (SELECT COUNT(DISTINCT r1.f_id) 
                                     FROM ".DBPref."routes r1, ".DBPref."specs s1, ".DBPref."routes_spec rs1
                                     WHERE ((s1.f_postid = r1.f_postid) OR (s1.f_id = rs1.f_specid AND r1.f_id = rs1.f_routeid)) AND s1.f_id = s.f_id AND r1.f_status = 5) cnt
                                  FROM ".DBPref."routes r, ".DBPref."specs s, ".DBPref."routes_spec rs
                                  WHERE ((s.f_postid = r.f_postid) OR (s.f_id = rs.f_specid AND r.f_id = rs.f_routeid)) AND s.f_id = ".$spec_id;
                      $conn_rs1 = $dbh->query($sql_rs1);
                      if($row_rs1 = $conn_rs1->fetch(PDO::FETCH_ASSOC)) // Если у всех маршрутов связанных со спецификацией статус "Прибыл"
                        {
                        if($row_rs1['total'] === $row_rs1['cnt'] && $row_rs1['total'] > 0)
                          {
                          $spec_update = ['curtbl' => 15, 'curidx' => $spec_id, 'f_status' => 16]; // Выставляем статус "Спец. доставлена"
                          $update_result = editRowTbl($spec_update);
                          }
                        }
                      }
                    }
                  // ~ sdid 1756
                  }
                //sdid - 613
// sdid 1429    if(strcmp($row['f_p2dtp'],$odt2)!=0)//изменили плановую дату прибытия, выставляем текущую дату обработки
                if(strcmp($row['f_p2dt'], $odt2) != 0)//изменили плановую дату прибытия, выставляем текущую дату обработки
                  {
                  if(isset($_SESSION['loginid']))
                    {
                    if($_SESSION['loginid']>0)
                      {
                      $sql  = "select f_struct_code from ".DBPref."users where f_id=".$_SESSION['loginid'];
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row1['f_struct_code']==2)
                          {$ki = Array('curtbl'=>77,'curidx'=>$curidx,'f_dtobr'=>date("Y-m-d"));editRowTbl($ki);}
                        }
                      }
                    }
                  //sdid 1429
                  /*
                   1. При изменении даты f_p2dt в маршруте
                   1.1. перебираем все связанные с маршрутом спецификации
                   1.1.1. если в спецификации был статус "в пути" - смотрим статус в specs_hist
                   1.1.1.1. проверяем во всех маршрутах, связанных со специфкацией в этой доставке, если заполнены все даты f_p2dt,
                   то максимальную дату заполняем в "Дата передачи груза/оказания услуги"
                  */
                  $sql_specs = "SELECT specs.f_id, 
                                (SELECT f_status FROM " . DBPref . "specs_hist WHERE f_hid=(SELECT MAX(f_hid) FROM " . DBPref . "specs_hist WHERE f_id=specs.f_id AND f_status=15)) specs_hist_status
                                FROM " . DBPref . "routes routes, " . DBPref . "routes_spec routes_spec, " . DBPref . "specs specs 
                                WHERE routes.f_id=routes_spec.f_routeid AND specs.f_id=routes_spec.f_specid AND routes.f_id=" . $curidx;
                  $res_specs = $dbh->query($sql_specs);
                  while ($row_specs = $res_specs->fetch(PDO::FETCH_ASSOC))
                    {
                    if(isset($row_specs['specs_hist_status']))
                      {
                      if ($row_specs['specs_hist_status'] == 15) // если у спецификации был статус "В пути"
                        {
                        $sql_routes = "SELECT routes.f_p2dt, routes.f_id
                                       FROM " . DBPref . "specs specs, " . DBPref . "routes routes, " . DBPref . "shipments shipments 
                                       WHERE specs.f_postid=shipments.f_id AND routes.f_postid=shipments.f_id AND specs.f_id=" . $row_specs['f_id'];
                        $res_routes = $dbh->query($sql_routes);
                        $routes_counter = 0;
                        $p2dt_counter = 0;
                        while ($row_routes = $res_routes->fetch(PDO::FETCH_ASSOC))
                          {
                          if (isset($row_routes['f_p2dt']))
                            {
                            if (strcmp($row_routes['f_p2dt'], "0000-00-00 00:00:00") != 0)
                              {$p2dt_counter++;}
                            }
                          $routes_counter++;
                          }
                        $specs_update = [];
                        if ($routes_counter == $p2dt_counter) // если везде проставлен f_p2dt
                          {
                          $sql_max_p2dt = "SELECT MAX(routes.f_p2dt) max_p2dt
                                           FROM " . DBPref . "specs specs, " . DBPref . "routes routes, " . DBPref . "shipments shipments 
                                           WHERE specs.f_postid=shipments.f_id AND routes.f_postid=shipments.f_id AND specs.f_id=" . $row_specs['f_id'];
                          $res_max_p2dt = $dbh->query($sql_max_p2dt);
                          if ($row_max_p2dt = $res_max_p2dt->fetch(PDO::FETCH_ASSOC))
                            {
                            if (isset($row_max_p2dt['max_p2dt']))
                              {
                              $max_p2dt = $row_max_p2dt['max_p2dt'];
                              $specs_update["f_dttoclnt"] = $max_p2dt; // максимальную дату заполняем в "Дата передачи груза/оказания услуги"
                              }
                            }
                          }
                        if (!empty($specs_update))
                          {
                          $specs_update["curtbl"] = 15;
                          $specs_update["curidx"] = $row_specs['f_id'];
                          editRowTbl($specs_update);
                          }
                        }
                      }
                    }
                  //~sdid 1429
                  }
                //~sdid - 613
                }
              //если добавили новый КТК в 0 маршрут, то прописываем для него историю КТК 
              //$sql = "select r.f_ktkid,k.f_mlid,r.f_postid,(select ifnull(count(*),0) from ".$tblname." where f_ktkid=r.f_ktkid and f_postid=r.f_postid) kcnt ".
              //       "from ".$tblname." r,".DBPref."ktk k ".
              //       "where k.f_id=r.f_ktkid and r.f_id=".$curidx;
              ////$ans = $ans.$sql;
              //$res = $dbh->query($sql);
              //if($row = $res->fetch(PDO::FETCH_ASSOC))
              //  {
              //  if(($row['f_ktkid']>0)&&($row['f_mlid']>0)&&($row['f_postid']>0)&&($row['kcnt']<=1))//если добавили не расчетный контейнер первый раз в текущей доставке
              //    {
              //    $sql = "insert into ".DBPref."ktk_hist (f_specid,f_ktkid,f_dttmcr,f_userid,f_place,f_status) values (".$row['f_postid'].",".$row['f_ktkid'].",NOW(),".$_SESSION['loginid'].",'',0)";
              //    $dbh->exec($sql);
              //    }
              //  }
//sdid 1214
              // если изменился комментарий в маршруте
              $nroutecom = $row['f_com'];
              if (strcmp($oroutecom, $nroutecom) != 0)
                {
//------------------
                //if($_SESSION['loginid']==2){echo $nroutecom."|".$oroutecom."|";}
                $sql = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from " . DBPref . "spr where f_type=151 and f_num=10";
                $res1 = $dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row1['f_uslint'] == 1)
                    {
                    if (strcmp($row1['f_uslstr'], "2") == 0)
                      {
                      $mgrp = $row1['f_namedop'];
                      $musr = 0;
                      } 
                    else
                      {
                      $mgrp = 0;
                      $musr = $row1['f_namedop'];
                      }

                    if ($musr != 0)
                      {
                      $sql = "select f_useaddmsg from veda_users where f_id=" . $musr;
                      $res2 = $dbh->query($sql);
                      if ($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        if ($row2['f_useaddmsg'] > 0)
                          {
                          $subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 9, $curidx);
                          $postbody = lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 9, $curidx);
                          $postbody = $postbody . "<br>Ссылка на марштут <a href=\"" . $redirect_uri . "?pgid=77&obid=" . $curidx . "\">" . $curidx . "</a><br>";
                          //mSendMail($_SESSION['loginid'],0,141,$subject,$postbody,"",77,$curidx);
                          //if($_SESSION['loginid']==2){echo $subject."|".$postbody;}
                          if ($menomes == 0)
                            {mSendMail($_SESSION['loginid'], $mgrp, $musr, $subject, $postbody, "", 77, $curidx);}
                          }
                        }
                      }
                    }
                  }
//------------------
                }
//~sdid 1214
              }
            elseif(strcmp($tblname,DBPref."routes_ktk")==0)//обработываем запись в связку маршрута и КТК
              {
              $ktkid = 0;
              $rid   = 0;
//sdid 856
              $sql = "select rk.f_status,rk.f_routeid,rk.f_ktkid,k.f_mlid,r.f_postid, r.f_routetype, rk.f_dttounload,
                        (select ifnull(count(*),0) from ".$tblname." where f_ktkid=rk.f_ktkid and f_routeid in 
                          (select f_id from ".DBPref."routes where f_postid=r.f_postid)) kcnt ".
                      "from ".$tblname." rk,".DBPref."ktk k,".DBPref."routes r ".
                      "where r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid and rk.f_id=".$curidx;
/*              $sql = "select rk.f_status,rk.f_routeid,rk.f_ktkid,k.f_mlid,r.f_postid,
                        (select ifnull(count(*),0) from ".$tblname." where f_ktkid=rk.f_ktkid and f_routeid in 
                          (select f_id from ".DBPref."routes where f_postid=r.f_postid)) kcnt ".
                      "from ".$tblname." rk,".DBPref."ktk k,".DBPref."routes r ".
                      "where r.f_id=rk.f_routeid and k.f_id=rk.f_ktkid and rk.f_id=".$curidx;
*/
//~sdid 856
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $ktkid = $row['f_ktkid'];
                $rid   = $row['f_routeid'];
                if(($row['f_ktkid']>0)&&($row['f_mlid']>0)&&($row['f_postid']>0)&&($row['kcnt']<=1))//если добавили не расчетный контейнер первый раз в текущей доставке
                  {
                  $sql = "insert into ".DBPref."ktk_hist (f_specid,f_ktkid,f_dttmcr,f_userid,f_place,f_status) values (".$row['f_postid'].",".$row['f_ktkid'].",NOW(),".$_SESSION['loginid'].",'',0)";
                  //echo $sql."<br>";
                  $dbh->exec($sql);
                  }
                //echo "|ost=".$ostatus."|".$row['f_status']."|".$ktkid."|";
                if(($ostatus!=$row['f_status'])&&($ktkid>0))
                  {
                  $ki = Array('curtbl'=>103,'curidx'=>$ktkid, 'f_status'=>$row['f_status']);
                  editRowTbl($ki);
//sdid 856
                  if(($row['f_status'] == 22) && ($row['f_routetype'] == 1))  // Статус - Выгружен, Тип маршрута - Морфрахт
                    {
                      // Проверим, все ли контейнеры в маршруте имеют статус Выгружен
                      $sql = "select ifnull(count(*),0) kcnt
                              from veda_routes_ktk rk, veda_routes r 
                              where r.f_id=rk.f_routeid 
                              and r.f_id=".$rid." and r.f_routetype=1 and rk.f_status <> 22";
                      $res1 = $dbh->query($sql);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                          if(row1['kcnt'] == 0) //если нет контейнеров со статусом, отличным от Выгружен, т.е. все выгружены, статусу маршрута назначаем Прибыл, дата прибытия=дате прибытия контейнера
                            {
                               $ki = Array('curtbl'=>77,'curidx'=>$rid, 'f_status'=>5, 'f_p2dt'=>$row['f_dttounload']);
                               editRowTbl($ki);
                            }
                        } 
                    } 
//~sdid 856
                  }
                }
              if(($rid>0)&&($ktkid>0))
                {
                $sql = "select rk.f_id rktkid,r.f_id rid,rk.f_ktkid ktkid,r.f_postid postid ".
                       "from ".DBPref."routes_ktk rk,".DBPref."routes r ".
                       "where r.f_postid=(select rr.f_postid from ".DBPref."routes_ktk rrk,".DBPref."routes rr where rrk.f_routeid=rr.f_id and rrk.f_id=".$curidx." limit 1) ".
                       "and rk.f_routeid=r.f_id and rk.f_ktkid=".$oldktkid." and r.f_id<>".$rid." ".
                       "group by rid,ktkid,postid";
                $res = $dbh->query($sql);
                while($row = $res->fetch(PDO::FETCH_ASSOC))
                  {
                  $sql = "update ".DBPref."routes_ktk rk set rk.f_ktkid=".$ktkid." where rk.f_id=".$row['rktkid'];
                  $dbh->exec($sql);
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."lns")==0)//если обновили запись в финансировании
              {
              //перенесено в класс Funding
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select r.f_status,r.f_specid,r.f_lnstype,r.f_tdid,r.f_dopparm, ".
                     "  (select f_name from ".DBPref."spr where f_type=96 and f_num=r.f_status) stname ".
                     "from ".$tblname." r where r.f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                if($nstatus!=$ostatus)//изменился статус финансирования
                  {
                  if(($row['f_lnstype']==1)||($row['f_lnstype']==8))//товары, операции
                    {
                    if($nstatus==2)//если согласовали
                      {
                      // $sql1 = "select f_name,f_uslint,f_namedop,f_uslstr from ".DBPref."spr where f_type=96 and f_num=".$nstatus;
                      //sdid - 164
                      //sdid 703 
                      //$sql1 = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint from ".DBPref."spr where f_type=96 and f_num=".$nstatus;
                      $sql1 = "select f_name,f_uslint,f_namedop,f_uslstr,f_dopprint,f_dopprstr1 from ".DBPref."spr where f_type=96 and f_num=".$nstatus;
                      //~sdid 703
                      //~sdid - 164
                      //echo $sql1."|";
                      $res1 = $dbh->query($sql1);
                      if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        if($row1['f_uslint']==1)
                          {
                          if(strcmp($row1['f_uslstr'],"2")==0)
                            {$mgrp=$row1['f_namedop'];$musr=0;}
                          else
                            {$mgrp=0;$musr=$row1['f_namedop'];}
                          if($menomes==0)
                            {
                              //sdid - 164
                              //sdid 703 
                              if($row1['f_dopprstr1']>0)
                                {$subject = lGetPostSubjShablon($row1['f_dopprstr1'], 0, 0, 0, 0, 5, $curidx);}
                              else
                                {$subject = "По записи финансирования изменен статус на ".$row1['f_name'];}
                              //~sdid 703
                              $postbody = "По <a href=\"".$redirect_uri."?pgid=18&obid=".$curidx."\">записи финансирования</a> изменен статус на \"".$row1['f_name']."\"";
                              $postbody = $postbody . lGetPostShablon($row1['f_dopprint'], 0, 0, 0, 0, 5, $curidx);
                              mSendMail($_SESSION['loginid'],$mgrp,$musr,$subject,$postbody,"",15,$row['f_specid']);
                              //~sdid - 164
                            }
                          }
                        }
                      //обновим операции, выставим признак кредитования
                      $sql1 = "select f_docid from ".DBPref."lns_docs where f_lnsid=".$curidx;
                      //echo $sql1."|";
                      $res1 = $dbh->query($sql1);
                      while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                        {
                        $ki = Array('curtbl'=>35,'curidx'=>$row1['f_docid'],'f_wloans'=>1);
                        editRowTbl($ki);
                        }
                      }
                    }
                  elseif($row['f_lnstype']==2)//таможенные платежи
                    {
                    $sql1 = "select f_operto from ".DBPref."dt where f_id=".$row['f_tdid'];
                    //echo $sql1."|";
                    $res1 = $dbh->query($sql1);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                      {
                      mSendMail($_SESSION['loginid'],0,$row1['f_operto'],"По записи финансирования изменен статус на ".$row['stname'],
                            "По <a href=\"".$redirect_uri."?pgid=18&obid=".$curidx."\">записи финансирования</a> изменен статус на \"".
                            $row['stname']."\"","",61,$row['f_tdid']);
                      }
                    }
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."agr")==0)//если обновили согласование
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "SELECT c.f_type,c.f_objid,c.f_com,c.f_status,c.f_parm,".
                     "  (select f_name from ".DBPref."spr where f_type=99 and f_num=c.f_type) type, ".
                     "  (select f_name from ".DBPref."spr where f_type=96 and f_num=c.f_status) status, ".
                     "  (select f_id from ".DBPref."dt where f_specid=c.f_objid limit 1) dtid,".
                     "  case when c.f_type in (1,2) then ".
                     "    (select f_name from ".DBPref."spr where f_type=98 and f_num=c.f_parm) ".
                     "  else '' ".
                     "  end parm, ".
                     "  c.f_id ".
                     "FROM ".$tblname." c WHERE c.f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if($row['f_type']==1)//согласовывали способ оплаты обеспечения по ДТ
                  {
                  if($row['f_status']==2)
                    {
                    mSendMail($_SESSION['loginid'],18,0,$row['status']." ".$row['type'],"По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_objid']."\">спецификации</a><br>Для ".$row['type']." согласован параметр: ".$row['parm']."<br>Комментарий: ".$row['f_com'],"",15,$row['f_objid']);
                    //echo $row['dtid']."|";
                    $ki = Array('curtbl'=>61,'curidx'=>$row['dtid'],'f_oprtptype'=>$row['f_parm']);
                    editRowTbl($ki);
                    }
                  else
                    {
                    mSendMail($_SESSION['loginid'],18,0,$row['status']." ".$row['type'],"По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_objid']."\">спецификации</a> не согласован ".$row['type']."<br>Комментарий: ".$row['f_com'],"",15,$row['f_objid']);
                    }
                  }
                elseif($row['f_type']==2)//согласовывали способ оплаты ТП по заявлению
                  {
                  if($row['f_status']==2)
                    {
                    mSendMail($_SESSION['loginid'],18,0,$row['status']." ".$row['type'],"По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_objid']."\">спецификации</a><br>Для ".$row['type']." согласован параметр: ".$row['parm']."<br>Комментарий: ".$row['f_com'],"",15,$row['f_objid']);
                    //echo $row['dtid']."|";
                    $ki = Array('curtbl'=>61,'curidx'=>$row['dtid'],'f_oprtptype'=>$row['f_parm']);
                    editRowTbl($ki);
                    //if($row['f_status']==2)//Из средств ЕЛС(ОС)
                    //  {
                    //  $sql = "select dt.f_zayavsum,dt.f_specid,d.f_orgid,d.f_contrid,".
                    //         "  ifnull((select count(*) from ".DBPref."spec_invoices where f_idoper in (177,178) and f_parenttype=2 and ".
                    //         "            f_specid=dt.f_specid),0) zocnt ".
                    //         "from ".DBPref."dt dt,".DBPref."specs s,".DBPref."dogs d ".
                    //         "where s.f_id=dt.f_specid and d.f_id=s.f_dogid and dt.f_id=".$row['dtid'];
                    //  $res1 = $dbh->query($sql);
                    //  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    //    {
                    //    if(($row1['f_zayavsum']>0)&&($row1['zocnt']==0))
                    //      {
                    //      //Таможенные платежи: Задолженность ТП по заявлению. Списание с ЕЛС
                    //      makeOper(2,$row1['f_specid'],"","",0,$row1['f_zayavsum'],643,0,1,"",
                    //             "","",0,"",0,177,$row1['f_contrid'],$row1['f_orgid'],0,0,"",
                    //             0,0,0,0,-5,"","",185,"",0,
                    //             "","",2,$row['dtid'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                    //      //Таможенные платежи: Задолженность ТП по заявлению. Поручение на пополнение ЕЛС
                    //      makeOper(2,$row1['f_specid'],"","",0,$row1['f_zayavsum'],643,0,1,"",
                    //             "","",0,"",0,178,$row1['f_contrid'],$row1['f_orgid'],0,0,"",
                    //             0,0,0,0,-5,"","",185,"",0,
                    //             "","",2,$row['dtid'],0,0,0,0,0,"0000-00-00","0000-00-00","");
                    //      }
                    //    }
                    //  }
                    }
                  else
                    {
                    mSendMail($_SESSION['loginid'],18,0,$row['status']." ".$row['type'],"По <a href=\"".$redirect_uri."?pgid=15&obid=".$row['f_objid']."\">спецификации</a> не согласован ".$row['type']."<br>Комментарий: ".$row['f_com'],"",15,$row['f_objid']);
                    }
                  }
                }
              }
            elseif(strcmp($tblname,DBPref."schets")==0)//если обновили счет
              {
              $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
              $dbh->exec('SET CHARACTER SET utf8');
              $sql = "select r.f_type,r.f_status,r.f_edoid,r.f_sum,r.f_maininv,r.f_num,date_format(r.f_dt,'%d.%m.%Y') schdt,
                        r.f_orgid, -- sdid 2315
                        ifnull((select f_edid from ".DBPref."edoarh where f_id=r.f_edoid),'') edid 
                       ,ifnull((select f_nbd1c from ".DBPref."edoarh where f_id=r.f_edoid),'') nbd1c
                      from ".$tblname." r where r.f_id=".$curidx;
              //$ans = $ans."|".$sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                if($nstatus!=$ostatus)//изменился статус счета
                  {
                  //$ans = $ans."|".$nstatus."|".$ostatus."|";
                  if($row['f_type']==2)//если изменяем счет от поставщика
                    {
                    if($nstatus==5)//отправялем счет на согласование оплаты
                      {
                      if(($ostatus!=0)&&($ostatus!=4))
                        {
                        $ki = Array('curtbl'=>17,'curidx'=>$curidx,'f_status'=>$ostatus);
                        editRowTbl($ki);
                        }
                      else
                        {
                        $sql = "select ifnull(count(*),0) cnt from ".DBPref."usr_grp where f_usrid=".$_SESSION['loginid']." and f_grpid=27";
                        //echo $sql."|";
                        $nres = $dbh->query($sql);
                        if($nrow = $nres->fetch(PDO::FETCH_ASSOC))
                          {
                          if(($nrow['cnt']>0)||($ostatus==0))
                            {
                            //echo $nrow['cnt']."|".$ostatus."|";
                            if($row['f_edoid']>0)
                              {
                              if(strlen($row['edid'])>2)
                                {
                                setEDOLogist($row['edid'],1,$row['f_edoid'],$row['nbd1c']);
                                }
                              }
                            //sdid 3484 - далее закомментируем уведомление, когда дадут отмашку                           
                            //if($ostatus==0){$ms1="Счет передан на оплату";$ms2="передан на оплату";}
                            //else{$ms1="Согласован счет на оплату";$ms2="согласован для оплаты";}
                            //// sdid 1566
                            ////mSendMail($_SESSION['loginid'], 4, 0, $ms1,
                            ////sdid2315
                            //$buh_mail_group = 0;
                            //if($row['f_orgid']==15){$buh_mail_group=58;}else{$buh_mail_group=59;}
                            //if($buh_mail_group > 0)
                            //  {
                            //  //mSendMail($_SESSION['loginid'], 52, 0, $ms1,
                            //  mSendMail($_SESSION['loginid'], $buh_mail_group, 0, $ms1,
                            //        "<a href=\"".$redirect_uri."?pgid=17&obid=".$curidx."\">Счет ".$row['f_num']." от ".$row['schdt']."</a> ".$ms2,"",0,0);
                            //  }
                            ////~sdid2315                            
                            //~sdid 3484
                            }
                          else
                            {
                            $ki = Array('curtbl'=>17,'curidx'=>$curidx,'f_status'=>$ostatus);
                            editRowTbl($ki);
                            }
                          }
                        }
                      }
                    }
                  //sdid3461
                  elseif(($nstatus==9)||(($ostatus==9)&&($nstatus!=9)))//аннулировали счет или убрали аннулированость
                    {
                    $sql = "select f_id from ".DBPref."schets where f_maininv=$curidx";
                    $nres = $dbh->query($sql);
                    while($nrow = $nres->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>17,'curidx'=>$nrow['f_id'],'f_status'=>$nstatus);
                      editRowTbl($ki);
                      }
                    }
                  //~sdid3461
                  }
                //sdid - 819
                //echo $row['f_sum']."|".$osum."|".$row['f_type']."|".$row['f_maininv']."|".$nstatus;
                if(($row['f_sum']>$osum)&&($row['f_type']==1)&&($row['f_maininv']==0)&&($nstatus==3))//если у сумму счета клиенту и статус - оплачен
                  {
                  $sql     = "select 
                                ifnull(sum(ahd.f_clssum),0) clsum,
                                round(CAST(".$row['f_sum']."*ahd.f_curs AS DECIMAL(15,3)),2) ninvsum,
                                ifnull((select ah.f_sum from veda_acchist ah where ah.f_id=ahd.f_acchistid),0) ahsum,
                                round(CAST((ifnull((select ah.f_sum from veda_acchist ah where ah.f_id=ahd.f_acchistid),0)-
                                            ifnull(round(CAST(sum(ahd.f_clssum) AS DECIMAL(15,3)),2),0)) AS DECIMAL(15,3)),2) rahsum,
                                round(CAST((".$row['f_sum']."-".$osum.")*ahd.f_curs AS DECIMAL(15,3)),2) rinvsum,
                                ahd.f_id
                              from veda_acchist_docs ahd 
                              where ahd.f_doctype=1 and ahd.f_docid=$curidx";
                  //echo $sql;
                  $res1    = $dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row1['clsum']<$row1['ninvsum'])//сумма выписки меньше чем стала сумма счета
                      {
                      if($row1['rinvsum']<=$row1['rahsum'])//распределяем сумму увеличения
                        {
                        //$ki = Array('curtbl'=>147,'curidx'=>$curidx,'f_clssum'=>$row1['rinvsum']); //sdid 2656
                        $ki = Array('curtbl'=>147,'curidx'=>$curidx,'f_clssum'=>$row1['rinvsum'],'f_manual'=>-2); //sdid 2656
                        editRowTbl($ki);
                        }
                      else//меняем статус на Оплачен частично
                        {
                        $ki = Array('curtbl'=>17,'curidx'=>$curidx,'f_status'=>2);
                        editRowTbl($ki);
                        }
                      }
                    }
                  }
                //~sdid - 819
                //sdid1444
                $schet_type = $row['f_type']; // Тип счета
                $main_invoice_id = $row['f_maininv']; // ID агрегирующего счета
                $id = $curidx; // ID счета
                if(intval($schet_type) === 1 && intval($main_invoice_id) > 0 && abs(doubleval($row['f_sum']) - $osum) > 0.0001)
                  {
                  $sql_sum = "SELECT round(cast(SUM(f_sum) as decimal(15,3)),2) total FROM ".DBPref."schets WHERE f_maininv = ".$main_invoice_id;
                  $conn_sum = $dbh->query($sql_sum);
                  if($row_sum = $conn_sum->fetch(PDO::FETCH_ASSOC))
                    {
                    $total = $row_sum['total'];
                    $aggregation_invoice_update = [
                        "curtbl" => 17,
                        "curidx" => $main_invoice_id,
                        "f_sum" => $total
                      ];
                    editRowTbl($aggregation_invoice_update);
                    }
                  }
                if($schet_type == 1 && $main_invoice_id == 0) 
                  { // Если счет покупателю, который не включен в агрегирующий счет
                  $response = json_decode(expFinDocTo1C($schet_type, $id));
                  $ans = $ans . $response[1];
                  if($response[0]) 
                    {$kr = -5;}
                  else 
                    {$kr = -6;}
                  }
                //~sdid1444
                }
              }
            elseif(strcmp($tblname,DBPref."akts")==0)//если обновили акт
              {
              $dbh = dbconnect();
              $sql = "select case when r.f_dt1c='0000-00-00' then r.f_dt else r.f_dt1c end dtc,r.f_kod1c numc,r.f_type,r.f_status,o.f_kod1c okod,
                        c.f_kod1c ckod,r.f_c1guid,c.f_id contrid,o.f_id orgid,
                        (select f_typecode from ".DBPref."client_codes where f_contrid=o.f_id) nbd1c,
                        #sdid - 203
                        r.f_sum, r.f_nds, 
                        #~sdid - 203
                        #sdid - 353
                        f_operid 
                        #~sdid - 353
                        ,r.f_ndssum,r.f_mainakt #sdid 1865
                      from ".$tblname." r,".DBPref."clients o,".DBPref."clients c 
                      where o.f_id=r.f_orgid and c.f_id=r.f_contrid and r.f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                $nstatus = $row['f_status'];
                if($nstatus!=$ostatus)//изменился статус счета
                  {
                  if(($row['f_type']==7)||($row['f_type']==10))//если изменяем Поступления (акты, накладные) или Реализация (акты, накладные)
                    {
                    if($nstatus==9)//аннулируем акт
                      {
                      $cckodc = getClntInf(2,2,$row['contrid'],0,$row['orgid'],2);
                      //echo $row['dtc']."|".$row['numc']."|".$row['okod']."|".$cckodc."|".$row['f_c1guid']."|".$row['f_type']."|".$row['nbd1c'];
                      $ret1c = c1c_delAkt($row['dtc'],$row['numc'],$row['okod'],$cckodc,$row['f_c1guid'],$row['f_type'],$row['nbd1c']);
                      //var_dump($ret1c);
                      }
                    }
                  }
                //sdid - 203,353
                //sdid1454
                if(!isset($old_operation_id)){$old_operation_id=-1;}
                //~sdid1454
                if($old_operation_id==0 && $row['f_operid']>0)
                  {
                  $closing_document_hash = array('sum'=>$row['f_sum'], 'nds'=>$row['f_nds']);
                  if(isset($new_operation_id)){reset_nodoccalc($new_operation_id, $closing_document_hash, $dbh);}
                  //sdid1454
                  if(!isset($new_operation_id)){$new_operation_id=-1;}
                  //~sdid1454
                  if((!isset($extra_data_hash["only_document_change"]) || !$extra_data_hash["only_document_change"]) && 
                     ($old_operation_id != $new_operation_id))
                    {
                    handle_adjustments($curidx, $tblname, $dbh);
                    }
                  }
                //~sdid - 203,353
                // sdid 1865
                if($row['f_type'] == 10 && $row['f_mainakt'] > 0)
                  {
                  $do_update = false;
                  $main_akt = $row['f_mainakt'];
                  $nds_type = $row['f_nds'];
                  $nds_sum = $row['f_ndssum'];
                  $akt_update = ['curtbl' => 83, 'curidx' => $main_akt];
                  $sql_akt_query = "SELECT 
                                      COUNT(a.f_id) total, SUM(a.f_ndssum) ndssum, 
                                      (SELECT COUNT(ak.f_id) FROM ".DBPref."akts ak WHERE ak.f_nds=".$nds_type." AND ak.f_mainakt=".$main_akt.") cnt
                                    FROM ".DBPref."akts a WHERE a.f_mainakt=".$main_akt;
                  $sql_akt_conn = $dbh->query($sql_akt_query);
                  if($sql_akt_row = $sql_akt_conn->fetch(PDO::FETCH_ASSOC))
                    {
                    if($old_akt_nds != $nds_type)
                      {
                      if($sql_akt_row['total']==$sql_akt_row['cnt'])
                        {
                        $akt_update['f_nds'] = $nds_type;
                        $do_update = true;
                        }
                      }
                    if($old_akt_ndssum != $nds_sum)
                      {
                      $akt_update['f_ndssum'] = $sql_akt_row['ndssum'];
                      $do_update = true;
                      }
                    if($do_update)
                      {$akt_update_result = json_decode(editRowTbl($akt_update), true);}
                    }
                  }
                // ~ sdid 1865
                }
              }
            elseif(strcmp($tblname,DBPref."remote_clnt")==0)//если обновили ЛКК. Клиенты
              {
              $sql = "select f_login,f_bpassword,f_name1,f_isuse from ".$tblname." where f_id=".$curidx;
              //echo $sql."|";
              $res = $dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC))
                {
                if((strcmp($obpassword,$row['f_bpassword'])!=0)||($oisuse!=$row['f_isuse']))
                  {
                  $data = array(
                    'email'       => $row['f_login'],
                    'first_name'  => $row['f_name1'],
                    'is_active'   => $row['f_isuse'],
                    'password'    => $row['f_bpassword']
                               );
                  $curl = curl_init(lkurl."/private/api/v1/users/auth/sign-up/");
                  curl_setopt($curl, CURLOPT_POST, true);
                  curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($curl);
                  curl_close($curl);
                  $opars = json_decode($response,true);
                  if($opars['result'])
                    {$ans = " Пользователя ЛКК обновлен";}
                  else
                    {$ans = " Ошибка обновления пользователя ЛКК";}
                  //echo $ans;
                  $kr = -4;
                  }
                }
              }
            //sdid1314
            elseif($curtbl==271)//Изменяем запись - Категории - Учет расхода
              {
              $sql  = "select f_valstr,f_objectid from $tblname where f_id=$curidx";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if($ovalstr!=(int)$row1['f_valstr'])//если изменилась категория, меняем статью бюджета у операции
                  {
                  if((int)$row1['f_valstr']!=2)//Если НЕ Расход ОВЭД, удаляем дополнительную категорию
                    {
                    $sql_bdr = "SELECT ctg.f_id FROM ".DBPref."categs ctg 
                                WHERE f_ctgtype=32 AND f_objecttype=5 AND f_objectid=".$row1['f_objectid'];
                    $res_bdr = $dbh->query($sql_bdr);
                    if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                      {
                      $bdr_id = $row_bdr['f_id'];
                      $bdr_delete = ["curtbl" => 270,"curidx" => $bdr_id];
                      delRowTbl($bdr_delete);
                      }
                    }
                  if((int)$row1['f_valstr']==1)//Учесть как расход ОЛ, ставим 186 - БДР/Расходы/КО(-)/услуги по контейнерам (пользование)
                    {
                    $sql_bdr = "SELECT si.f_id,si.f_bdrarticle FROM ".DBPref."spec_invoices si WHERE si.f_id=".$row1['f_objectid'];
                    $res_bdr = $dbh->query($sql_bdr);
                    if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row_bdr['f_bdrarticle']!=186)
                        {$bdr_edit = ["curtbl" => 35,"curidx" => $row_bdr['f_id'],"f_bdrarticle" => 186];
                        editRowTbl($bdr_edit);}
                      }
                    }
                  elseif((((int)$row1['f_valstr']==3)&&($ovalstr==1))//Учесть как расход Клиента
                       ||(((int)$row1['f_valstr']==2)&&($ovalstr==1)))//Учесть как расход ОВЭД, ставим - 176 - БДР/Доходы/КО(+)/услуги по контейнерам (пользование)
                    {
                    $sql_bdr = "SELECT si.f_id,si.f_bdrarticle FROM ".DBPref."spec_invoices si WHERE si.f_id=".$row1['f_objectid'];
                    $res_bdr = $dbh->query($sql_bdr);
                    if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row_bdr['f_bdrarticle']!=176)
                        {$bdr_edit = ["curtbl" => 35,"curidx" => $row_bdr['f_id'],"f_bdrarticle" => 176];
                        editRowTbl($bdr_edit);}
                      }
                    }
                  }
                }
              }
            //~sdid1314
            //sdid - 353
            //elseif(strcmp($tblname,DBPref."akts_details_opers")==0)//если обновили детализацию акта
            //  {
            //  $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
            //  $dbh->exec('SET CHARACTER SET utf8');
            //  //sdid - 203
            //  $sql = "SELECT r.f_operid, d.f_sum, d.f_nds FROM $tblname r, ".DBPref."akts_details d WHERE d.f_id=r.f_akts_detailsid AND r.f_id=".$curidx;
            //  //~sdid - 203
            //  $res = $dbh->query($sql);
            //  if($row = $res->fetch(PDO::FETCH_ASSOC))
            //    {
            //    $new_operation_id = $row['f_operid'];
            //    if($old_operation_id==0 && $new_operation_id>0)
            //      {
            //      //sdid - 203
            //      $closing_document_hash = array('sum'=>$row['f_sum'], 'nds'=>$row['f_nds']);
            //      reset_nodoccalc($new_operation_id, $closing_document_hash, $dbh);
            //      //~sdid - 203
            //      }
            //    }
            //  //sdid - 203
            //  if((!isset($extra_data_hash["only_document_change"]) || !$extra_data_hash["only_document_change"]) && ($old_operation_id != $new_operation_id)){
            //    handle_adjustments($curidx, $tblname, $dbh);
            //    }
            //  //~sdid - 203
            //  }
            ////~sdid - 353
            //echo "[true,\"ok".$ans."\"]";
            // sdid 2549
            elseif(strcmp($tblname,DBPref."maspayfe_opers") === 0)
              {
              if(isset($osum))
                {
                $sql = "SELECT mo.f_sum, mo.f_maspayfeid FROM ".DBPref."maspayfe_opers mo, ".DBPref."maspayfe m 
                        WHERE m.f_id = mo.f_maspayfeid AND m.f_type = 4 AND mo.f_id = ".$curidx;
                $conn = $dbh->query($sql);
                if($row = $conn->fetch(PDO::FETCH_ASSOC))
                  {
                  if($row['f_maspayfeid'] > 0)
                    {
                    if(abs($row['f_sum'] - $osum) > PHP_FLOAT_EPSILON)
                      {
                      $sql1 = "SELECT SUM(f_sum) new_sum FROM ".DBPref."maspayfe_opers WHERE f_maspayfeid = ".$row['f_maspayfeid'];
                      $conn1 = $dbh->query($sql1);
                      if($row1 = $conn1->fetch(PDO::FETCH_ASSOC))
                        {
                        $to_update = [
                                        'curtbl' => 230,
                                        'curidx' => $row['f_maspayfeid'],
                                        'f_sum' => $row1['new_sum']
                                    ];
                        editRowTbl($to_update);
                        }
                      }
                    }
                  }
                }
              }
            // ~ sdid 2549
            if($kr>0)
              {
              $retval = "[true,\"ok".$ans."\"]";
              }
            //sdid 2084
            elseif($kr==-3)
              {
              $a = array(false,$errmsg);
              $retval  = json_encode($a);
              }
            //sdid 2084
            elseif($kr==-4)
              {
              $retval = "[true,\"ok\",\"2\",\"1\",\"".$ans."\"]";
              }
            //sdid1444
            elseif($kr==-5)
              {
              $ans = str_replace('"', "'", $ans);
              $retval = "[true,\"ok\",\"1\",\"2\",\"".$ans."\"]";
              }
            elseif($kr==-6)
              {
              $ans = str_replace('"', "'", $ans);
              $retval = "[true,\"ok\",\"2\",\"2\",\"".$ans."\"]";
              }
            //~sdid1444
            }
          elseif($kr==-2)
            {$retval = "[false,\"Запрещено обновление данной записи\"]";}
          elseif($kr==-3)
            {
            $a = array(false,$errmsg);
            $retval  = json_encode($a);
            //$retval = "[false,\"Запрещено обновление данной записи\"]";
            }
          else
            //{echo "[false,\"Ни одна строка не обновлена\"]";}
            {$retval = "[false,\"Ни одна строка не обновлена ".$sql."\"]";}
          //echo $sql;
          }
        //echo $retval;
        else
          //{echo "[false,\"Отсутствуют данные для обновления\"]";}
          {$retval = "[false,\"Отсутствуют данные для обновления\"]";}
        }
      else
        //{echo "[false,\"Не найдена таблица для обновлния данных\"]";}
        {$retval = "[false,\"Не найдена таблица для обновлния данных\"]";}
      }
    else
      //{echo "[false,\"Не определена таблица или запись для обновления\"]";}
      {$retval = "[false,\"Не определена таблица или запись для обновления\"]";}
    return $retval;
    }

  //удалить запись в таблицу
  function delRowTbl($ik)
    {
    $lmes   = "";
    $retval = "";
    //читаем параметры
    $curtbl = 0;
    if(isset($ik['curtbl']))
      {$curtbl = $ik['curtbl'];}
    $curidx = 0;
    if(isset($ik['curidx']))
      {$curidx = $ik['curidx'];}
    //echo $curtbl."|".$curidx."|";
    if(($curtbl>0) and (strlen($curidx)>0))
      {
      //подключаемся к базе
      $dbh = new PDO('mysql:host='.DBHost.';dbname='.DataBase, UserName, Password);
      //указываем, мы хотим использовать utf8
      $dbh->exec('SET CHARACTER SET utf8');
      $sql = "select f_tablename from ".DBPref."menu_all where f_id=".$curtbl;
      //echo $sql."<br>";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
//sdid 1334
        if(!isset($redirect_uri))
          {$redirect_uri = redirect_uri;}
//~sdid 1334
        $insspecnum = "";
        $tblname=$row['f_tablename'];
        //проверки перед удалением
        $candel = 1;$candelmsg = "Запрещено удалять одну или несколько записей!!!";
        if(($curtbl==15)||($curtbl==35)||($curtbl==145))//проверка запрета удаления записей спецификаций и операций
          {
          $sql = "select f_status from $tblname where f_id in (".$curidx.")";
          $res1 = $dbh->query($sql);
          while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
            if((($curtbl==15)&&($row1['f_status']==6))||((($curtbl==35)||($curtbl==145))&&(($row1['f_status']==4)||($row1['f_status']==7))))//в зависимости от статусов
              {
              $sql = "select count(*) cnt from ".DBPref."usr_grp where f_usrid=".$_SESSION['loginid']." and f_grpid=38";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {if($row1['cnt']==0){$candel=0;$candelmsg = "Одна или несколько записей уже закрыты - удаление запрещено!!! Обратитесь к сотрудникам с соответствующими правами.";}}
              }
            }
          //sdid3047
          //if(($candel==1)&&(($curtbl==35)||($curtbl==145)))
          //  {
          //  $sql  = "select count(*) cnt from ".DBPref."pays p where p.f_subtype in (1,2) and (p.f_objid in (".$curidx.") or p.f_objidist in (".$curidx.") or p.f_objidсorrect in (".$curidx."))";
          //  $res1 = $dbh->query($sql);
          //  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
          //    {
          //    if($row1['cnt']>0)
          //      {$candel=0;$candelmsg = "Одна или несколько выбранных операций, включены в операции переноса - удаление запрещено!!!";}
          //    }
          //  }
          //~sdid3047
          //sdid 1315
          if(($candel==1)&&(($curtbl==35)||($curtbl==145)))
            {
            //sdid2168
            //$sql = "select f_parenttype,f_specid,f_itemcalcrp from $tblname where f_id in (:curidx)";
            $sql = "select t.f_parenttype,t.f_specid,t.f_itemcalcrp,t.f_idoper,t.f_dtid,t.f_id
                      #sdid2519
                      #,t.f_parentid
                      #,case
                      #  when ifnull((select count(*) from ".DBPref."netting_agr_details nad,".DBPref."netting_agr na 
                      #               where na.f_id=nad.f_nettingagrid and nad.f_operid2=t.f_id and na.f_typeoperid=2),0)>0 then 1
                      #  when t.f_parentid=0 then 0
                      #  when ifnull((select count(*) from ".DBPref."netting_agr_details nad,".DBPref."netting_agr na 
                      #               where na.f_id=nad.f_nettingagrid and nad.f_operid2=t.f_parentid and na.f_typeoperid=2),0)>0 then 2
                      #end naparentcnt
                      #~sdid2519
                    from $tblname t where t.f_id in (:curidx)";
            //~sdid2168
            $res1 = $dbh->prepare($sql);
            $res1->bindParam(':curidx',$curidx,PDO::PARAM_INT);
            $res1->execute();
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              // sdid 2168
              // f_idoper: f_sumnds 80, f_sumip 78, f_sumst 76, f_sumsp 99
              $oidoper = $row1['f_idoper'];
              $odtid = $row1['f_dtid'];
              if($odtid > 0)
                {
                $field = "";
                if($oidoper == 80){$field = " dt.f_sumnds ";}
                if($oidoper == 78){$field = " dt.f_sumip ";}
                if($oidoper == 76){$field = " dt.f_sumst ";}
                if($oidoper == 99){$field = " dt.f_sumsp ";}
                if(strlen($field)>0)
                  {
                  $sqls = "SELECT $field dtsum 
                           FROM ".DBPref."dt dt 
                           WHERE dt.f_id=$odtid";
                  $conn = $dbh->query($sqls);
                  if($rows = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                    $dtsum = $rows['dtsum'];
                    if($dtsum > 0)
                      {
                      $candel = -3;
                      if (strlen($candelmsg) > 0)
                        {$candelmsg .= "<br>";}
                      $candelmsg .= "(Операция ИД ".$row1['f_id']."): Ненулевая сумма, указанная в Информации о ТО ($dtsum)";
                      }
                    }
                  }
                }
              // ~ sdid 2168
              if(($row1['f_parenttype']==2)&&($row1['f_specid']>0))
                {
                //sdid2519
                //if($row1['naparentcnt']>0)
                //  {
                //  $candel = -3;
                //  $candelmsg .= "(Операция ИД ".$row1['f_id'].") связана с соглашением о взаимозачете) ";
                //  }
                //else
                //  {
                  $ischeck = 0;
                  //sdid 3490
                  //$sql = "select f_id from $tblname where f_itemcalcrp=:itemcalcrp and f_parenttype=:parenttype and f_specid=:specid and 
                  //          (f_checked1>:ischeck or f_checked2>:ischeck or f_checked3>:ischeck) and f_id<>:curidx";
                  $sql = "select f_id from $tblname where f_itemcalcrp=:itemcalcrp and f_parenttype=:parenttype and f_specid=:specid and 
                            (f_checked1>:ischeck or f_checked2>:ischeck or f_checked3>:ischeck) and f_id not in (:curidx)";
                  //~sdid 3490
                  $res2 = $dbh->prepare($sql);
                  $res2->bindParam(':itemcalcrp',$row1['f_itemcalcrp'],PDO::PARAM_INT);
                  $res2->bindParam(':parenttype',$row1['f_parenttype'],PDO::PARAM_INT);
                  $res2->bindParam(':specid'    ,$row1['f_specid'],PDO::PARAM_INT);
                  $res2->bindParam(':ischeck'   ,$ischeck,PDO::PARAM_INT);
                  $res2->bindParam(':curidx'    ,$curidx,PDO::PARAM_INT);
                  $res2->execute();
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $kai = Array('curtbl'=>35,'curidx'=>$row2['f_id'],'f_checked1'=>0,'f_checked2'=>0,'f_checked3'=>0);
                    $kr  = json_decode(editRowTbl($kai));
                    }
                //  }
                //~sdid2519
                //sdid 3490
                // проверяем входит ли операция в закрытый отчет агента
                $candel_by_agreps = 1;
                $sql = "select ar.f_id from veda_agentreps ar, veda_agentreps_opers aro where ar.f_id=aro.f_agentrepid and ar.f_status=1 and aro.f_operid=:curidx";
                $res3 = $dbh->prepare($sql);
                $lcuridx = $row1['f_id'];
                $res3->bindParam(':curidx',$lcuridx,PDO::PARAM_INT);
                $res3->execute();
                while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
                   {
                   $candel_by_agreps = 0;
                   $candel = -3;
                   if(strlen($candelmsg)>0) {$candelmsg .= "<br>";}
                   $candelmsg .= "Невозможно удалить объект (Операция ИД ".$row1['f_id']."), так как связанный с ним объект Отчет агента ИД ".$row3['f_id']." Закрыт.";
                   }
                // проверяем входит ли операция в закрытую корректировку
                $candel_by_corrects = 1;
                if($candel_by_agreps == 1)
                  {
                  $sql = "select c.f_id from veda_corrects c, veda_corrects_opers co where c.f_id=co.f_correctid and c.f_status=3 and co.f_correctoperid=:curidx";
                  $res4 = $dbh->prepare($sql);
                  $lcuridx = $row1['f_id'];
                  $res4->bindParam(':curidx',$lcuridx,PDO::PARAM_INT);
                  $res4->execute();
                  while($row4 = $res4->fetch(PDO::FETCH_ASSOC))
                    {
                    $candel_by_corrects = 0;
                    $candel = -3;
                    if(strlen($candelmsg)>0) {$candelmsg .= "<br>";}
                    $candelmsg .= "Невозможно удалить объект (Операция ИД ".$row1['f_id']."), так как связанный с ним объект Корректировка ИД ".$row4['f_id']." Закрыт.";
                    }
                  }
                if($candel_by_agreps==1 && $candel_by_corrects==1) 
                  {
                  $sql = "select aro.f_id from veda_agentreps ar, veda_agentreps_opers aro where ar.f_id=aro.f_agentrepid and ar.f_status<>1 and aro.f_operid=:curidx";
                  $res5 = $dbh->prepare($sql);
                  $lcuridx = $row1['f_id'];
                  $res5->bindParam(':curidx',$lcuridx,PDO::PARAM_INT);
                  $res5->execute();
                  while($row5 = $res5->fetch(PDO::FETCH_ASSOC))
                    {
                    $del_oao = ["curtbl" => 287,"curidx" => $row5['f_id']];
                    delRowTbl($del_oao);
                    }

                  $sql = "select co.f_id from veda_corrects c, veda_corrects_opers co where c.f_id=co.f_correctid and c.f_status<>3 and co.f_correctoperid=:curidx";
                  $res6 = $dbh->prepare($sql);
                  $lcuridx = $row1['f_id'];
                  $res6->bindParam(':curidx',$lcuridx,PDO::PARAM_INT);
                  $res6->execute();
                  while($row6 = $res6->fetch(PDO::FETCH_ASSOC))
                    {
                    $del_co = ["curtbl" => 291,"curidx" => $row6['f_id']];
                    delRowTbl($del_co);
                    }
                  }
                //~sdid 3490
                }
              }
            }
          //~sdid 1315
          }
//sdid 1140
        elseif($curtbl==77)
          {
              // связка маршрут-спецификация в routes_spec, тогда берем спецификации из данной связки - чаще всего используется для сборных контейнеров
              $sql = "select rs.f_specid,
                             rs.f_routeid,
                             r.f_status,
                             ifnull(date(r.f_p2dt),'0000-00-00') fp2dt,
                             ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt,
                             concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname
                        from veda_routes_spec rs, veda_routes r, veda_specs s, veda_dogs d
                       where rs.f_routeid in (".$curidx.")
                         and r.f_id=rs.f_routeid
                         and s.f_id=rs.f_specid
                         and d.f_id=s.f_dogid";
              $cntRoutes = 0;
              $cntArrived = 0;
              $maxDt = "0000-00-00";
              $isFindedInRoutesSpecTbl = 0;
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                  $isFindedInRoutesSpecTbl++; 
                  // идем по спецификациям
                  // ищем все маршруты по каждой спецификации. Если все в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации
                  $cntRoutes = 1;
                  $cntArrived = 0;
                  $maxDt = "0000-00-00";
                  if(($row1['f_status'] == 5) && (strcmp($row1['fp2dt'],"0000-00-00")!=0))
                    {
                      $cntArrived++;
                      $maxDt = $row1['fp2dt'];
                      $sql = "select r.f_status, ifnull(date(r.f_p2dt),'0000-00-00') fp2dt
                                from veda_specs s, veda_routes r
                               where s.f_id=".$row1['f_specid']." and r.f_postid=s.f_postid and r.f_id not in (".$curidx.")";
                      $res2 = $dbh->query($sql);
                      // идем по маршрутам, связанным со спецификацией
                      while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                          $cntRoutes++;
                          if(($row2['f_status'] == 5) && (strcmp($row2['fp2dt'],"0000-00-00")!=0))
                            {
                              $cntArrived++;
                              if(strtotime($row2['fp2dt']) > strtotime($maxDt)) {$maxDt = $row2['fp2dt'];}
                            }
                        }
                    }
                  if(($cntRoutes == $cntArrived) && (strtotime($row1['f_dttoclnt']) != strtotime($maxDt))) // если все маршруты в статусе "Прибыл" 
                    { //и у всех указана дата прибытия и дата в спец. != max дате из маршрутов, то максимальную дату указываем в спецификации 
                      //выставляем "Дата передачи груза клиенту" у спецификаций и отправляем соответственное оповещение 
                      $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>$maxDt,'f_status'=>16);
                      editRowTbl($ki);                     
                      $subject = "Дата передачи груза ".$maxDt." по Спец/Заявке № ".$row1['specname'];
                      $postbody = "Дата передачи груза ".$maxDt." по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                      mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']);                      
                    }
                }
              if($isFindedInRoutesSpecTbl == 0) // если ничего не нашли в veda_routes_spec, ищем по связке veda_specs.f_postid=veda-routes.f_postid
                {
                  $sql = "select s.f_id f_specid,
                                 r.f_id routeid,
                                 r.f_status,
                                 ifnull(date(r.f_p2dt),'0000-00-00') fp2dt,
                                 ifnull(date(s.f_dttoclnt),'0000-00-00') f_dttoclnt,
                                 concat(d.f_dogname,'/',s.f_num, '/',s.f_dt) specname
                            from veda_routes r, veda_specs s, veda_dogs d
                           where r.f_id in (".$curidx.") 
                             and s.f_postid=r.f_postid
                             and d.f_id=s.f_dogid";
                  $cntRoutes = 0;
                  $cntArrived = 0;
                  $maxDt = "0000-00-00";
                  $res1 = $dbh->query($sql);
                  while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                    {
                      // идем по спецификациям
                      // ищем все маршруты по каждой спецификации. Если все в статусе "Прибыл" и у всех указана дата прибытия, то максимальную дату указываем в спецификации
                      $cntRoutes = 1;
                      $cntArrived = 0;
                      $maxDt = "0000-00-00";
                      if(($row1['f_status'] == 5) && (strcmp($row1['fp2dt'],"0000-00-00")!=0))
                        {
                          $cntArrived++;
                          $maxDt = $row1['fp2dt'];
                          $sql = "select r.f_status, ifnull(date(r.f_p2dt),'0000-00-00') fp2dt
                                    from veda_specs s, veda_routes r
                                   where s.f_id=".$row1['f_specid']." and r.f_postid=s.f_postid and r.f_id not in (".$curidx.")";		                                      
                          $res2 = $dbh->query($sql);
                          // идем по маршрутам, связанным со спецификацией
                          while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                            {
                              $cntRoutes++;
                              if(($row2['f_status'] == 5) && (strcmp($row2['fp2dt'],"0000-00-00")!=0))
                                {
                                  $cntArrived++;
                                  if(strtotime($row2['fp2dt']) > strtotime($maxDt)) {$maxDt = $row2['fp2dt'];}
                                }
                            }
                        }
                      if(($cntRoutes == $cntArrived) && (strtotime($row1['f_dttoclnt']) != strtotime($maxDt))) // если все маршруты в статусе "Прибыл" 
                        { //и у всех указана дата прибытия и дата в спец. != max дате из маршрутов, то максимальную дату указываем в спецификации 
                          //выставляем "Дата передачи груза клиенту" у спецификаций и отправляем соответственное оповещение 
                          $ki = Array('curtbl'=>15,'curidx'=>$row1['f_specid'],'f_dttoclnt'=>$maxDt,'f_status'=>16);
                          editRowTbl($ki);
                         
                          $subject = "Дата передачи груза ".$maxDt." по Спец/Заявке № ".$row1['specname'];
                          $postbody = "Дата передачи груза ".$maxDt." по <a href=\"".$redirect_uri."?pgid=15&obid=".$row1['f_specid']."\">Спец/Заявке № ".$row1['specname']."</a>";
                          mSendMail($_SESSION['loginid'],14,0,$subject,$postbody,"",15,$row1['f_specid']);                      
                          //mSendMail($_SESSION['loginid'],0,130,$subject,$postbody,"",15,$row1['f_specid']);                      
                        }
                    }
                } //~if($isFindedInRoutesSpecTbl == 0)
          //sdid 1993
          //sdid 1764
          /*$sql = "SELECT COUNT(rk.f_id) cnt FROM ".DBPref."routes_ktk rk WHERE rk.f_routeid in (:routeid)";
          $conn = $dbh->prepare($sql);
          $conn->bindParam(':routeid', $curidx);
          $conn->execute();
          if($row_cnt = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row_cnt['cnt'] > 0)
              {
              $candel = -1;
              $candelmsg = "С маршрутом есть связанные КТК. Удаление запрещено.";
              }
            }*/
          //~sdid 1764
          $sql = "SELECT rk.f_id FROM ".DBPref."routes_ktk rk WHERE rk.f_routeid in (:routeid)";
          $conn = $dbh->prepare($sql);
          $conn->bindParam(':routeid', $curidx);
          $conn->execute();
          while($row_rk = $conn->fetch(PDO::FETCH_ASSOC))
            {
            $del_rk = ["curtbl" => 189,"curidx" => $row_rk['f_id']];
            delRowTbl($del_rk);
            }
          $sql = "SELECT rs.f_id FROM ".DBPref."routes_spec rs WHERE rs.f_routeid in (:routeid)";
          $conn = $dbh->prepare($sql);
          $conn->bindParam(':routeid', $curidx);
          $conn->execute();
          while($row_rs = $conn->fetch(PDO::FETCH_ASSOC))
            {
            $del_rs = ["curtbl" => 193,"curidx" => $row_rs['f_id']];
            delRowTbl($del_rs);
            }
          //~sdid 1993
          }
        //~sdid 1140
        //sdid1743
        elseif(($curtbl==222)||($curtbl==184))//Акты-детал. - операции
          {
          $adostorno       = 0;
          $adostornoid     = 0;
          $adostornooperid = 0;
          $adostornooprid  = 0;
          $adostornoopridid= 0;
          $adostornoprid   = 0;
          $adostornopridid = 0;
          //проверяем наличие вхождения операции в закрытый отчет агента
          $sql = "SELECT sis.f_id FROM $tblname ado,".DBPref."spec_invoices_storno sis,".DBPref."agentreps_opers ao,".DBPref."agentreps a 
                  WHERE a.f_id=ao.f_agentrepid and ao.f_operid=sis.f_operstornoid and a.f_status=1 and sis.f_stornotype in (2,3) and
                    ado.f_operid=sis.f_operstornoid and ado.f_id in (:oid)";
          $conn = $dbh->prepare($sql);
          $conn->bindParam(':oid', $curidx);
          $conn->execute();
          if($row_rk = $conn->fetch(PDO::FETCH_ASSOC))
            {$candel=0;$candelmsg = "Одна или несколько записей включены в закрытые отчеты агента - удаление запрещено!!!";}
          else
            {
            $sql = "select 
                       ifnull((select count(*) from ".DBPref."spec_invoices_storno sis where sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostrono
                      ,ifnull((select sis.f_id from ".DBPref."spec_invoices_storno sis where sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostronoid
                      ,ifnull((select sis.f_operstornoid from ".DBPref."spec_invoices_storno sis where sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostornooperid
                      ,ifnull((select sis1.f_operstornoid from ".DBPref."spec_invoices_storno sis,".DBPref."spec_invoices_storno sis1 where sis1.f_operid=sis.f_operid and sis1.f_stornotype=2 and sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostornooprid
                      ,ifnull((select sis1.f_id from ".DBPref."spec_invoices_storno sis,".DBPref."spec_invoices_storno sis1 where sis1.f_operid=sis.f_operid and sis1.f_stornotype=2 and sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostornoopridid
                      ,ifnull((select sis.f_operid from ".DBPref."spec_invoices_storno sis where sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostornoprid
                      ,ifnull((select sis1.f_id from ".DBPref."spec_invoices_storno sis,".DBPref."spec_invoices_storno sis1 where sis1.f_operid=sis.f_operid and sis1.f_stornotype=1 and sis.f_operstornoid=ado.f_operid and sis.f_stornotype=3),0) adostornopridid
                    from $tblname ado,".DBPref."spec_invoices_storno sis where ado.f_id in (:oid)";
            $conn = $dbh->prepare($sql);
            $conn->bindParam(':oid', $curidx);
            $conn->execute();
            if($row_rk = $conn->fetch(PDO::FETCH_ASSOC))
              {
              $adostorno       = $row_rk['adostrono'];
              $adostornoid     = $row_rk['adostronoid'];
              $adostornooperid = $row_rk['adostornooperid'];
              $adostornooprid  = $row_rk['adostornooprid'];
              $adostornoopridid= $row_rk['adostornoopridid'];
              $adostornoprid   = $row_rk['adostornoprid'];
              $adostornopridid = $row_rk['adostornopridid'];
              }
            }
          }
        //~sdid1743
        // sdid 1314
        elseif($curtbl==271)
          {
          $sql_bdr = "SELECT ctg.f_id 
                      FROM ".DBPref."categs ctg 
                      WHERE f_ctgtype=32 AND f_objecttype=5 AND f_objectid=(SELECT f_objectid FROM ".DBPref."categs WHERE f_id=".$curidx.")";
          $res_bdr = $dbh->query($sql_bdr);
          if($row_bdr = $res_bdr->fetch(PDO::FETCH_ASSOC))
            {
            $bdr_id     = $row_bdr['f_id'];
            $bdr_delete = ["curtbl" => 270,"curidx" => $bdr_id];
            delRowTbl($bdr_delete);
            }
          }
        // ~ sdid 1314
        elseif($curtbl==255)
          {
          if($usenumdocreestr==1)
            {
            $sql = "select count(*) cnt from $tblname where f_id in (".$curidx.") and (f_docid>0 or f_out1C=1)";
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if($row1['cnt']>0)
                {$candel=0;$candelmsg = "Одна или несколько записей связаны с документами или выгружены в 1С - удаление запрещено!!!";}
              }
            }
          }
        // sdid 1314
        elseif ($curtbl == 270) // Дополнительная статья бюджета
          {
          $sql = "SELECT COUNT(ctg1.f_id) budget_lines, ctg2.f_valstr 
                FROM ".DBPref."categs ctg1, ".DBPref."categs ctg2, ".DBPref."categs ctg3 
                WHERE ctg1.f_ctgtype = 32 
                AND ctg1.f_objecttype = 5 
                AND ctg1.f_objectid = ctg2.f_objectid 
                AND ctg2.f_objecttype = 5 
                AND ctg2.f_ctgtype = 33 
                AND ctg1.f_objectid = ctg3.f_objectid 
                AND ctg3.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['f_valstr'] == 2 && $row['budget_lines'] == 1)
              {
              $candel = -1;
              $candelmsg = "Запрещено удаление последней категории (Дополнительная статья бюджета), если есть категория (Учет расхода) равная (Расход ОВЭД)";
              }
            }
          }
        elseif ($curtbl == 271) // Категории учета расхода
          {
          $sql =    "SELECT COUNT(s.f_id) paid_invoices 
                     FROM ".DBPref."schets s, ".DBPref."categs ctg  
                     WHERE s.f_operid = ctg.f_objectid 
                          AND ctg.f_ctgtype = 33 
                          AND ctg.f_objecttype = 5 
                          AND ctg.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['paid_invoices'] > 0)
              {
              $candel = -1;
              $candelmsg = "Запрещена смена учета расхода, если по операции уже выставлен счет";
              }
            }
          $sql =    "SELECT COUNT(s.f_id) paid_invoices 
                     FROM ".DBPref."akts s, ".DBPref."categs ctg  
                     WHERE s.f_operid = ctg.f_objectid 
                          AND ctg.f_ctgtype = 33 
                          AND ctg.f_objecttype = 5 
                          AND ctg.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['paid_invoices'] > 0)
              {
              $candel = -1;
              $candelmsg = "Запрещена смена учета расхода, если по операции уже создан акт";
              }
            }
          $sql =    "SELECT COUNT(ad.f_id) paid_acchist 
                     FROM ".DBPref."acchist_docs ad, ".DBPref."categs ctg  
                     WHERE ad.f_doctype = 3 
                          AND ad.f_docid = ctg.f_objectid 
                          AND ctg.f_ctgtype = 33 
                          AND ctg.f_objecttype = 5 
                          AND ctg.f_id = ".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['paid_acchist'] > 0)
              {
              $candel = -1;
              $candelmsg = "Запрещена смена учета расхода, если по операции уже произведена оплата";
              }
            }
          }
        // ~ sdid 1314
        // sdid 1743
        elseif ($curtbl == 286) // Отчеты Агента
          {
          $sql = "SELECT * FROM ".DBPref."agentreps WHERE f_id=".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['f_status'] == 1)
              {
              $candel = -1;
              $candelmsg = "Отчет закрыт - удаление запрещено";
              }
            else
              {
              $sql = "SELECT aro.f_id aroid FROM ".DBPref."agentreps ar, ".DBPref."agentreps_opers aro WHERE ar.f_id=aro.f_agentrepid AND ar.f_id=".$curidx;
              $conn = $dbh->query($sql);
              while($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                $aro = Array('curtbl'=>287,'curidx'=>$row['aroid']);
                delRowTbl($aro);
                }
              }
            }
          }
        elseif($curtbl == 287) // Отчеты Агента-операции
          {
          $sql = "SELECT ar.* FROM ".DBPref."agentreps ar, ".DBPref."agentreps_opers aro WHERE ar.f_id=aro.f_agentrepid AND aro.f_id=".$curidx;
          $conn = $dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
            if($row['f_status'] == 1)
              {
              $candel = -1;
              $candelmsg = "Запрещено удаление привязанной операции - отчет закрыт";
              }
            }
          }
        // ~ sdid 1743
        if($candel==1)
          {
          $sql = "";
          if(($curtbl==14)||($curtbl==127)||($curtbl==133)||($curtbl==134)||($curtbl==138))
            {$sql = "select count(*) cnt from $tblname where f_id in (".$curidx.") and (length(f_kod1c)>2 or length(f_kod1cg)>2 or length(f_kod1cgo)>2 or length(f_kod1cgc)>2)";}
          elseif($curtbl==15)
            {$sql = "select count(*) cnt from $tblname where f_id in (".$curidx.") and (length(f_kod1cb)>2 or length(f_kod1cp)>2)";}
          elseif(($curtbl==17)||($curtbl==83))
            {$sql = "select count(*) cnt from $tblname where f_id in (".$curidx.") and (length(f_kod1c)>2 or length(f_num)>2) and f_status<>0";}
          $res1 = $dbh->query($sql);
          if(strlen($sql)>0)
            {
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if($row1['cnt']>0)
                {
                $sql = "select count(*) cnt from ".DBPref."usr_grp where f_usrid=".$_SESSION['loginid']." and f_grpid=38";
                $res2 = $dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                //sdid 1334 2023-06-15
                  //{if($row2['cnt']==0){$candel=0;$candelmsg = "Одна или несколько записей имеют Код 1С/Номер - удаление запрещено!!!";}}
                  {if($row2['cnt']==0){$candel=0;$candelmsg = "Удаляемая запись выгружена в 1С.<br>Необходимо откатить запись в 1С,<br>после чего удалить коды 1С в удаляемой записи.<br>Обратитесь в бухгалтерию.";}}
                //~sdid 1334 2023-06-15

                }
              }
            }
          }
        //sdid1583
        if($candel==1)
          {
          $candelmsg = "";
          $ndelopr1583 = 0;$deloprid1583 = 0;$ndeldif1583=0;$deldif1583=0;$deldifd1583=0;
          if(strcmp($tblname,DBPref."acchist_docs")==0)//Перед удалением ПП-документы
            {
            //sdid2462 - запрещаем удалять документы по выпискам в закрытом периоде
            $lcando = 1;
            //sdid2685
            $canaddupddelacchistl = 0;
            if(isset($ik['canaddupddelacchistl'])){$canaddupddelacchistl=$ik['canaddupddelacchistl'];unset($ik['canaddupddelacchistl']);}
            if($canaddupddelacchistl==0)
              {
              $dtbuhclserp = "";
              $sql_dtbuhcls = "SELECT MAX(f_dtbuhcls) dtbuhcls FROM ".DBPref."settings where f_settype=1";
              $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
              if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                {$dtbuhclserp = strtotime($row_dtbuhcls["dtbuhcls"]);}
              if(strlen($dtbuhclserp)>0)
                {
                $sql_dtbuhcls = "SELECT f_dt1C dt1c FROM ".DBPref."acchist ah,".DBPref."acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_id=".$curidx;
                $res_dtbuhcls = $dbh->query($sql_dtbuhcls);
                if($row_dtbuhcls = $res_dtbuhcls->fetch(PDO::FETCH_ASSOC))
                  {
                  $ldt1c = strtotime($row_dtbuhcls["dt1c"]);
                  if($ldt1c < $dtbuhclserp)
                    {
                    $lcando = 0;$candel=0;$candelmsg = $candelmsg."Выписка в закрытом бух.периоде, удаление связанных документов запрещено<br>";
                    }
                  }
                }
              }
            //~sdid2685
            if($lcando>0)
              {
            //~sdid2462 - запрещаем удалять документы по выпискам в закрытом периоде
              $sql = "select si.f_id
                      from ".DBPref."spec_invoices i,".DBPref."spec_invoices si,$tblname ahd 
                      where ahd.f_doctype=3 and ahd.f_docid=i.f_id and i.f_idoper=387 and si.f_parentid=i.f_id and ahd.f_id=$curidx";
              //echo "$sql|";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {$candel=0;$candelmsg = "По операции создана связанная операция, удаление запрещено<br>";}
              $sql = "select i.f_id
                        ,ifnull((select count(*) from ".DBPref."agentreps_opers where f_operid=i.f_id),0) acnt
                        ,ifnull((select count(*) from ".DBPref."corrects_opers where f_correctoperid=i.f_id),0) ccnt
                      from ".DBPref."spec_invoices i,$tblname ahd 
                      where ahd.f_doctype=3 and ahd.f_docid=i.f_id and i.f_idoper=387 and ahd.f_id=$curidx";
              //echo "$sql|";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if($row1['acnt']>0){$candel=0;$candelmsg = $candelmsg."Операции включена в отчет агента, удаление запрещено<br>";}
                if($row1['ccnt']>0){$candel=0;$candelmsg = $candelmsg."Операция включена в корректировку, удаление запрещено<br>";}
                if($candel==1)
                  {$ndelopr1583 = 1;$deloprid1583 = $row1['f_id'];}
                }
              $sql = "select ds.f_id,d.f_id did,dd.f_id ddid, length(ifnull(d.f_kod1c,'')) len from ".DBPref."difrate d,".DBPref."spec_invoices ds,".DBPref."difrate_docs dd,$tblname ahd  
                      where ahd.f_docid=ds.f_parentid and ds.f_id=dd.f_operid and d.f_id=dd.f_difrateid and ds.f_idoper=388 and ahd.f_id=$curidx";
              $res1 = $dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                if($row1['len']>0){$candel=0;$candelmsg = $candelmsg."По операции создана курсовая разница, выгруженная в 1С, удаление запрещено<br>";}
                if($candel==1)
                  {$ndelopr1583 = 1;$deloprid1583 = $row1['f_id'];$ndeldif1583=1;$deldif1583=$row1['did'];$deldifd1583=$row1['ddid'];}
                }
              }
            }
          }
        //~sdid1583
        if($candel==1)
          {
          $insspecnum = "";
          if(strcmp($tblname,DBPref."ensures")==0)//страховки
            {
            $sql = "SELECT concat(ifnull(d.f_dogname,''),'/',ifnull(s.f_num,''),'/',ifnull(c.f_cname,'')) insspecnum 
                    from ".DBPref."ensures e,".DBPref."specs s,".DBPref."dogs d,".DBPref."clients c 
                    where e.f_objtype=2 and s.f_id=e.f_objid and d.f_id=s.f_dogid and c.f_id=d.f_contrid and e.f_id=".$curidx;
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {$insspecnum = $row1['insspecnum'];}
            }
          $sql = "";
          if(strcmp($tblname,DBPref."spec_invoices")==0)//запрет удаления операций, если к ним что-нибудь привязано
            {
//sdid 1334
            /*$sql = "(select count(*) from ".DBPref."acchist_docs d where d.f_docid=c.f_id and d.f_doctype=3) cntah,
                    (select count(*) from ".DBPref."akts d where d.f_operid=c.f_id) cntakts,
                    (select count(*) from ".DBPref."akts_details_opers d where d.f_operid=c.f_id) cntaktsd,
                    (select count(*) from ".DBPref."schets d where d.f_operid=c.f_id) cntschets,
                    (select count(*) from ".DBPref."schets_details_opers d where d.f_operid=c.f_id) cntschetsd,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=5) cntcategs,
                    (select count(*) from ".DBPref."cash d where d.f_idoper=c.f_id) cntcash,
                    (select count(*) from ".DBPref."cash d where d.f_idoper=c.f_id) cntcash,
                    (select count(*) from ".DBPref."cash_details d where d.f_idoper=c.f_id) cntcashd,
                    (select count(*) from ".DBPref."maspayfe_opers d where d.f_operid=c.f_id) cntmaspay,
                    (select count(*) from ".DBPref."lns_docs d where d.f_docid=c.f_id) cntlns,
                    (select count(*) from ".DBPref."pays d where d.f_objid=c.f_id and d.f_objtype in (0,35)) cntpay ";*/

            $sql = "(select count(*) from ".DBPref."acchist_docs d where d.f_docid=c.f_id and d.f_doctype=3) cntah,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=147&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."acchist_docs d where d.f_docid=c.f_id and d.f_doctype=3) lnkah,
                    (select count(*) from ".DBPref."akts d where d.f_operid=c.f_id) cntakts,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=83&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."akts d where d.f_operid=c.f_id) lnkakts,
                    (select count(*) from ".DBPref."akts_details_opers d where d.f_operid=c.f_id) cntaktsd,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=184&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."akts_details_opers d where d.f_operid=c.f_id) lnkaktsd,
                    (select count(*) from ".DBPref."schets d where d.f_operid=c.f_id) cntschets,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=17&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."schets d where d.f_operid=c.f_id) lnkschets,
                    (select count(*) from ".DBPref."schets_details_opers d where d.f_operid=c.f_id) cntschetsd,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=183&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."schets_details_opers d where d.f_operid=c.f_id) lnkschetsd,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=5) cntcategs,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=122&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=5) lnkcategs,
                    (select count(*) from ".DBPref."cash d where d.f_idoper=c.f_id) cntcash,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=67&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."cash d where d.f_idoper=c.f_id) lnkcash,
                    (select count(*) from ".DBPref."cash_details d where d.f_idoper=c.f_id) cntcashd,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=205&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."cash_details d where d.f_idoper=c.f_id) lnkcashd,
                    (select count(*) from ".DBPref."maspayfe_opers d where d.f_operid=c.f_id) cntmaspay,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=237&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."maspayfe_opers d where d.f_operid=c.f_id) lnkmaspay,
                    (select count(*) from ".DBPref."lns_docs d where d.f_docid=c.f_id) cntlns,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=171&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."lns_docs d where d.f_docid=c.f_id) lnklns,
                    #sdid1583
                    (select count(*) from ".DBPref."difrate_docs d where d.f_operid=c.f_id and 
                       (select count(*) from ".DBPref."difrate where f_id=d.f_difrateid and length(ifnull(f_kod1c,'')))>0) cntdifrated,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=191&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."difrate_docs d where d.f_operid=c.f_id limit 1) lnkdifrated,
                    #~sdid1583
                    (select count(*) from ".DBPref."pays d where d.f_objid=c.f_id and d.f_objtype in (0,35)) cntpay, 
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=209&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."pays d where d.f_objid=c.f_id and d.f_objtype in (0,35)) lnkpay ";
//~sdid 1334
            }
          elseif(strcmp($tblname,DBPref."clients")==0)//запрет удаления клиента ЮЛ, у которого есть договоры, операции, выписки, счета, акты
            {
//sdid 1334
            /*$sql = "(select count(*) from ".DBPref."dogs d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntdogs,
                    (select count(*) from ".DBPref."acchist d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntah,
                    (select count(*) from ".DBPref."spec_invoices d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntsi,
                    (select count(*) from ".DBPref."akts d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntakts,
                    (select count(*) from ".DBPref."schets d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntschets,
                    (select count(*) from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=1) cntadr,
                    (select count(*) from ".DBPref."staff d where d.f_pid=c.f_id and d.f_ptype=1) cntstaff,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=2) cntcltypes ";*/

            $sql = "(select count(*) from ".DBPref."dogs d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntdogs,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=13&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."dogs d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) lnkdogs,
                    (select count(*) from ".DBPref."acchist d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntah,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=35&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."acchist d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) lnkah,
                    (select count(*) from ".DBPref."spec_invoices d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntsi,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=35&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."spec_invoices d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) lnksi,
                    (select count(*) from ".DBPref."akts d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntakts,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=83&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."akts d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) lnkakts,
                    (select count(*) from ".DBPref."schets d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) cntschets,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=17&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."schets d where d.f_contrid=c.f_id or d.f_orgid=c.f_id) lnkschets,
                    (select count(*) from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=1) cntadr,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=123&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=1) lnkadr,
                    (select count(*) from ".DBPref."staff d where d.f_pid=c.f_id and d.f_ptype=1) cntstaff,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=176&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."staff d where d.f_pid=c.f_id and d.f_ptype=1) lnkstaff,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=2) cntcltypes,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=122&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=2) lnkcltypes ";
//~sdid 1334
            }
          elseif(strcmp($tblname,DBPref."contacts")==0)//запрет удаления клиентов
            {
//sdid 1334
            /*$sql = "(select count(*) from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=2) cntadr,
                    (select count(*) from ".DBPref."clients d where d.f_contactid=c.f_id) cntclul,
                    (select count(*) from ".DBPref."specs d where d.f_contactid=c.f_id) cntkp,
                    (select count(*) from ".DBPref."staff d where d.f_ptype=1 and d.f_pid=c.f_id) cntstaff,
                    (select count(*) from ".DBPref."files d where d.objectid=c.f_id and d.typeobject=46) cntfl,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=1) cntcltypes ";*/
            $sql = "(select count(*) from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=2) cntadr,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=177&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."addres d where d.f_clntid=c.f_id and d.f_clnttype=2) lnkadr,
                    (select count(*) from ".DBPref."clients d where d.f_contactid=c.f_id) cntclul,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=13&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."clients d where d.f_contactid=c.f_id) lnkclul,
                    (select count(*) from ".DBPref."specs d where d.f_contactid=c.f_id) cntkp,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=15&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."specs d where d.f_contactid=c.f_id) lnkkp,
                    (select count(*) from ".DBPref."staff d where d.f_ptype=1 and d.f_pid=c.f_id) cntstaff,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=123&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."staff d where d.f_ptype=1 and d.f_pid=c.f_id) lnkstaff,
                    (select count(*) from ".DBPref."files d where d.objectid=c.f_id and d.typeobject=46) cntfl,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=0&obid=',d.id,' target=\'_blank\'>',d.id,'</a>') SEPARATOR ',') 
                       from ".DBPref."files d where d.objectid=c.f_id and d.typeobject=46) lnkfl,
                    (select count(*) from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=1) cntcltypes,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=122&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."categs d where d.f_objectid=c.f_id and d.f_objecttype=1) lnkcltypes ";
//~sdid 1334
            //$sql = "";
            }
          elseif(strcmp($tblname,DBPref."dogs")==0)//запрет удаления договоров
            {
            $sql = "";
            }
          elseif(strcmp($tblname,DBPref."akts")==0)//запрет удаления закрывающих документов
            {
            $sql = "";
            }
          elseif(strcmp($tblname,DBPref."schets")==0)//запрет удаления счетов
            {
            $sql = "";
            }
          elseif(strcmp($tblname,DBPref."shipments")==0)//запрет удаления доставок
            {
            $sql = "";
            }
          elseif(strcmp($tblname,DBPref."acchist")==0)//запрет удаления выписки
            {
//sdid 1334
            //$sql = "(select count(*) from ".DBPref."acchist_docs d where d.f_acchistid=c.f_id) cntdocs ";
            $sql = "(select count(*) from ".DBPref."acchist_docs d where d.f_acchistid=c.f_id) cntdocs,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=147&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."acchist_docs d where d.f_acchistid=c.f_id) lnkdocs ";
//~sdid 1334
            }
          elseif(strcmp($tblname,DBPref."specs")==0)//запрет удаления спецификаций
            {
//sdid 1334
/*            $sql = "(select count(*) from ".DBPref."certificates d where d.f_specid=c.f_id) cntcert,
                    (select count(*) from ".DBPref."ensures d where d.f_objtype=2 and d.f_objid=c.f_id) cntins, 
                    (select count(*) from ".DBPref."dt d where d.f_specid=c.f_id) cntdt, 
                    (select count(*) from ".DBPref."spec_invoices d where d.f_parenttype=2 and d.f_specid=c.f_id) cntsi,
                    (select count(*) from ".DBPref."prjparts d where d.f_typeobj=1 and d.f_idobj=c.f_id) cntpp, 
                    (select count(*) from ".DBPref."specs d where d.f_parentspecid=c.f_id) cntps ";
*/
            $sql = "(select count(*) from ".DBPref."certificates d where d.f_specid=c.f_id) cntcert,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=56&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."certificates d where d.f_specid=c.f_id) lnkcerts,
                    (select count(*) from ".DBPref."ensures d where d.f_objtype=2 and d.f_objid=c.f_id) cntins, 
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=54&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."ensures d where d.f_objtype=2 and d.f_objid=c.f_id) lnkins,
                    (select count(*) from ".DBPref."dt d where d.f_specid=c.f_id) cntdt, 
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=61&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."dt d where d.f_specid=c.f_id) lnkdt,
                    (select count(*) from ".DBPref."spec_invoices d where d.f_parenttype=2 and d.f_specid=c.f_id) cntsi,
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=35&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."spec_invoices d where d.f_parenttype=2 and d.f_specid=c.f_id) lnksi,
                    (select count(*) from ".DBPref."prjparts d where d.f_typeobj=1 and d.f_idobj=c.f_id) cntpp, 
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=146&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."prjparts d where d.f_typeobj=1 and d.f_idobj=c.f_id) lnkpp,
                    (select count(*) from ".DBPref."specs d where d.f_parentspecid=c.f_id) cntps, 
                    (select GROUP_CONCAT(concat('<a href=".$redirect_uri."?pgid=15&obid=',d.f_id,' target=\'_blank\'>',d.f_id,'</a>') SEPARATOR ',') 
                       from ".DBPref."specs d where d.f_parentspecid=c.f_id) lnkps ";
//~sdid 1334
            //$sql = "";
            }
          if(strlen($sql)>0)
            {
            $sql  = "select $sql from $tblname c where c.f_id in (".$curidx.")";
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if(strcmp($tblname,DBPref."spec_invoices")==0)//запрет удаления операций, если к ним что-нибудь привязано
                {
                #sdid1583
                if(($row1['cntah']>0)||($row1['cntakts']>0)||($row1['cntaktsd']>0)||($row1['cntschets']>0)||($row1['cntschetsd']>0)||($row1['cntcategs']>0)||
                   ($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntpay']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntdifrated']>0))
                #~sdid1583
                  {
                  $candel=0;
//sdid 1334
                  /*if($row1['cntah']>0){$lmes=$lmes."выписками<br>";}
                  if($row1['cntakts']>0){$lmes=$lmes."закрывающими документами<br>";}
                  if($row1['cntaktsd']>0){$lmes=$lmes."детализацией закрывающих документов<br>";}
                  if($row1['cntschets']>0){$lmes=$lmes."счетами<br>";}
                  if($row1['cntschetsd']>0){$lmes=$lmes."детализацией счетов<br>";}
                  if($row1['cntcategs']>0){$lmes=$lmes."записями категорий<br>";}
                  if($row1['cntcash']>0){$lmes=$lmes."кассовыми операциями<br>";}
                  if($row1['cntcashd']>0){$lmes=$lmes."подотчетными операциями<br>";}
                  if($row1['cntmaspay']>0){$lmes=$lmes."массовыми платежами<br>";}
                  if($row1['cntlns']>0){$lmes=$lmes."финансированием<br>";}
                  if($row1['cntpay']>0){$lmes=$lmes."прочими платежами<br>";}*/

                  if($row1['cntah']>0)
                    {$lmes=$lmes."выписках ".$row1['lnkah'];
                     if(($row1['cntakts']>0)||($row1['cntaktsd']>0)||($row1['cntschets']>0)||($row1['cntschetsd']>0)||($row1['cntcategs']>0)||
                        ($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}                       
                  if($row1['cntakts']>0)
                    {$lmes=$lmes."закрывающих документах ".$row1['lnkakts'];
                     if(($row1['cntaktsd']>0)||($row1['cntschets']>0)||($row1['cntschetsd']>0)||($row1['cntcategs']>0)||
                        ($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntaktsd']>0)
                    {$lmes=$lmes."детализации закрывающих документов ".$row1['lnkaktsd'];
                     if(($row1['cntschets']>0)||($row1['cntschetsd']>0)||($row1['cntcategs']>0)||
                        ($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntschets']>0)
                    {$lmes=$lmes."счетах ".$row1['lnkschets'];
                     if(($row1['cntschetsd']>0)||($row1['cntcategs']>0)||($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntschetsd']>0)
                    {$lmes=$lmes."детализации счетов ".$row1['lnkschetsd'];
                     if(($row1['cntcategs']>0)||($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntcategs']>0)
                    {$lmes=$lmes."записях категорий ".$row1['lnkcategs'];
                     if(($row1['cntcash']>0)||($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntcash']>0)
                    {$lmes=$lmes."кассовых операциях ".$row1['lnkcash'];
                     if(($row1['cntcashd']>0)||($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntcashd']>0)
                    {$lmes=$lmes."подотчетных операциях ".$row1['lnkcashd'];
                     if(($row1['cntmaspay']>0)||($row1['cntlns']>0)||($row1['cntpay']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntmaspay']>0)
                    {$lmes=$lmes."массовых платежах ".$row1['lnkmaspay'];
                     if(($row1['cntlns']>0)||($row1['cntpay']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntlns']>0)
                    {$lmes=$lmes."финансировании ".$row1['lnklns'];
                     if(($row1['cntlns']>0)||($row1['cntpay']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntpay']>0){$lmes=$lmes."прочих платежах ".$row1['lnkpay']."<br>";}
                  //sdid1583
                  if($row1['cntdifrated']>0){$lmes=$lmes."курсовая разница, выгруженная в 1С: ".$row1['lnkdifrated']."<br>";}
                  //~sdid1583
//~sdid 1334
                  }
                }
              elseif(strcmp($tblname,DBPref."contacts")==0)//запрет удаления клиентов
                {
                if(($row1['cntadr']>0)||($row1['cntclul']>0)||($row1['cntkp']>0)||($row1['cntstaff']>0)||($row1['cntfl']>0)||($row1['cntcltypes']>0))
                  {
                  $candel=0;
//sdid 1334
                  /*if($row1['cntkp']>0){$lmes=$lmes."Заявками/Специфкациями/КП<br>";}
                  if($row1['cntclul']>0){$lmes=$lmes."Контрагентами - ЮЛ<br>";}
                  if($row1['cntadr']>0){$lmes=$lmes."Контактными данными<br>";}
                  if($row1['cntstaff']>0){$lmes=$lmes."Записями о персонале<br>";}
                  if($row1['cntfl']>0){$lmes=$lmes."Прикрепленные файлы<br>";}
                  if($row1['cntcltypes']>0){$lmes=$lmes."Типами обслуживания<br>";}*/

                  if($row1['cntkp']>0)
                    {$lmes=$lmes."Заявках/Специфкациях/КП ".$row1['lnkkp'];
                     if(($row1['cntclul']>0)||($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntfl']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}                       
                  if($row1['cntclul']>0)
                    {$lmes=$lmes."Контрагентах - ЮЛ ".$row1['lnkclul'];
                     if(($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntfl']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntadr']>0)
                    {$lmes=$lmes."Контактных данных ".$row1['lnkadr'];
                     if(($row1['cntstaff']>0)||($row1['cntfl']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntstaff']>0)
                    {$lmes=$lmes."Записях о персонале ".$row1['lnkstaff'];
                     if(($row1['cntfl']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntfl']>0)
                    {$lmes=$lmes."Прикрепленных файлах ".$row1['lnkfl'];
                     if($row1['cntcltypes']>0)
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntcltypes']>0){$lmes=$lmes."Типах обслуживания ".$row1['lnkcltypes']."<br>";}

//~sdid 1334
                  }
                }
              elseif(strcmp($tblname,DBPref."clients")==0)//запрет удаления клиента ЮЛ, у которого есть договоры, операции, выписки, счета, акты
                {
                if(($row1['cntdogs']>0)||($row1['cntah']>0)||($row1['cntsi']>0)||($row1['cntakts']>0)||($row1['cntschets']>0)||($row1['cntadr']>0)||
                   ($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                  {
                  $candel=0;
//sdid 1334
                  /*if($row1['cntdogs']>0){$lmes=$lmes."договорами<br>";}
                  if($row1['cntah']>0){$lmes=$lmes."выписками<br>";}
                  if($row1['cntsi']>0){$lmes=$lmes."операциями<br>";}
                  if($row1['cntakts']>0){$lmes=$lmes."закрывающими документами<br>";}
                  if($row1['cntschets']>0){$lmes=$lmes."счетами<br>";}
                  if($row1['cntadr']>0){$lmes=$lmes."контактными данными<br>";}
                  if($row1['cntstaff']>0){$lmes=$lmes."записями о персонале<br>";}
                  if($row1['cntcltypes']>0){$lmes=$lmes."типами обслуживания<br>";}*/
                  if($row1['cntdogs']>0)
                    {$lmes=$lmes."договорах ".$row1['lnkdogs'];
                     if(($row1['cntah']>0)||($row1['cntsi']>0)||($row1['cntakts']>0)||($row1['cntschets']>0)||($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntah']>0)
                    {$lmes=$lmes."выписках ".$row1['lnkah'];
                     if(($row1['cntsi']>0)||($row1['cntakts']>0)||($row1['cntschets']>0)||($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntsi']>0)
                    {$lmes=$lmes."операциях ".$row1['lnksi'];
                     if(($row1['cntakts']>0)||($row1['cntschets']>0)||($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntakts']>0)
                    {$lmes=$lmes."закрывающих документах ".$row1['lnkakts'];
                     if(($row1['cntschets']>0)||($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntschets']>0)
                    {$lmes=$lmes."счетах ".$row1['lnkschets'];
                     if(($row1['cntadr']>0)||($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntadr']>0)
                    {$lmes=$lmes."контактных данных ".$row1['lnkadr'];
                     if(($row1['cntstaff']>0)||($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntstaff']>0)
                    {$lmes=$lmes."записях о персонале ".$row1['lnkstaff'];
                     if(($row1['cntcltypes']>0))
                       {$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  if($row1['cntcltypes']>0){$lmes=$lmes."типах обслуживания ".$row1['lnkcltypes']."<br>";}
//~sdid 1334
                  }
                }
              elseif(strcmp($tblname,DBPref."specs")==0)//запрет удаления спецификаций
                {
                if(($row1['cntcert']>0)||($row1['cntins']>0)||($row1['cntdt']>0)||($row1['cntsi']>0)||($row1['cntps']>0)||($row1['cntpp']>0))
                  {
                  $candel=0;
//sdid 1334
                  //if($row1['cntcert']>0){$lmes=$lmes."сертификациями<br>";}
                  if($row1['cntcert']>0)
                    {$lmes=$lmes."сертификациях ".$row1['lnkcerts'];
                     if(($row1['cntins']>0)||($row1['cntdt']>0)||($row1['cntsi']>0)||($row1['cntps']>0)||($row1['cntpp']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}}
                  //echo "|lmes=".$lmes."lnkcerts=".$row1['lnkcerts']."|redirect_uri=".$redirect_uri."|";
                  //if($row1['cntins']>0){$lmes=$lmes."страховками<br>";}
                  if($row1['cntins']>0)
                    {$lmes=$lmes."страховках ".$row1['lnkins'];
                     if(($row1['cntdt']>0)||($row1['cntsi']>0)||($row1['cntps']>0)||($row1['cntpp']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}} 
                  //if($row1['cntdt']>0){$lmes=$lmes."информацией о ТО<br>";}
                  if($row1['cntdt']>0)
                    {$lmes=$lmes."информации о ТО ".$row1['lnkdt'];
                     if(($row1['cntsi']>0)||($row1['cntps']>0)||($row1['cntpp']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}} 
                  //if($row1['cntsi']>0){$lmes=$lmes."операциями<br>";}
                  if($row1['cntsi']>0)
                    {$lmes=$lmes."операциях ".$row1['lnksi'];
                     if(($row1['cntps']>0)||($row1['cntpp']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}} 
                  //if($row1['cntps']>0){$lmes=$lmes."другими спецификациями<br>";}
                  if($row1['cntps']>0)
                    {$lmes=$lmes."других спецификациях ".$row1['lnkps'];
                     if(($row1['cntpp']>0)){$lmes=$lmes.",<br>";} else{$lmes=$lmes."<br>";}} 
                  //if($row1['cntpp']>0){$lmes=$lmes."пользователями по объектам<br>";}
                  if($row1['cntpp']>0){$lmes=$lmes."пользователях по объектам ".$row1['lnkpp']."<br>";}
//~sdid 1334
                  }
                }
              elseif(strcmp($tblname,DBPref."acchist")==0)//запрет удаления выписко
                {
                if(($row1['cntdocs']>0))
                  {
                  $candel=0;
//sdid 1334
                  //if($row1['cntdocs']>0){$lmes=$lmes."документами системы<br>";}
                  if($row1['cntdocs']>0){$lmes=$lmes."документах системы ".$row1['lnkdocs']."<br>";}
//~sdid 1334
                  }
                }
              if($candel==0)
                {
                if(strlen($lmes)>0)
//sdid 1334
                  //{$candelmsg = "<br>Одна или несколько записей связаны с <br>$lmes Удаление запрещено!!!";}
                  {$candelmsg = "<br>Сначала удалите все записи о <br>".$lmes;}
//~sdid 1334
                }
              }
            }
          if(strcmp($tblname,DBPref."remote_clnt")==0)//ЛКК. Клиенты
            {
            $sql = "SELECT f_id
                    from $tblname
                    where f_resreg=1 and f_id=".$curidx;
            $res1 = $dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $candel=0;
              $lmes=$lmes."зарегистрированы<br>";
              $candelmsg = "<br>Один или несколько пользователей <br>$lmes Удаление запрещено!!!";
              }
            }
          //sdid2232
          elseif(strcmp($tblname,DBPref."maspayfe_opers")==0)//обобщенные платежи - операции - перед удалением
            {
            $sql = "SELECT mp.f_sum maspayfesum, mp.f_val maspayfeval, si.f_sum sisum, si.f_val sival, mp.f_acchistid, mp.f_id 
                    FROM ".DBPref."maspayfe_opers mpe, ".DBPref."maspayfe mp, ".DBPref."spec_invoices si 
                    WHERE si.f_id=mpe.f_operid AND mp.f_id=mpe.f_maspayfeid AND mpe.f_id=$curidx";
            $conn = $dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if($row['f_acchistid'] > 0)
                {
                $candel = -1;
                $candelmsg = "Запрещено удалять операции разнесенного обобщенного платежа (присутствует ссылка на ПП)";
                }
              else
                {
                $maspayfeval = $row['maspayfeval'];
                $maspayfesum = $row['maspayfesum'];
                $sisum = $row['sisum'];
                $sival = $row['sival'];
                $maspayfid = $row['f_id'];
                }
              }
            }
          //~sdid2232
          }
        if($candel==1)
          {
          if(!isset($redirect_uri))
            {$redirect_uri = redirect_uri;}
          $isthst = isTblHist($tblname);
          if(($isthst==1)||($curtbl==35)||($curtbl==145))
            {
            if(($curtbl==35)||($curtbl==145))
              {$sql = "select i.f_id,i.f_uved,i.f_uvedtyp,i.f_adr,i.f_parenttype,i.f_specid,i.f_num_oper,i.f_dttmcr,t.f_name,t.f_wmsg,t.f_wmsgadrt,t.f_wmsgadr ".
                      "from $tblname i,".DBPref."typeopers t WHERE t.f_id=i.f_idoper and i.f_id in (".$curidx.") ";}
            else
              {$sql = "select f_id from $tblname WHERE f_id in (".$curidx.") ";}
            //echo $sql."|";
            $res1 = $dbh->query($sql);
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $sql = "";
              if($curtbl==103)
                {
                $sql = "insert into ".$tblname."_hhist (f_act,f_id,f_dttmcr,f_userid) values (3,".$row1['f_id'].",NOW(),".$_SESSION['loginid'].")";
                }
              else
                {
                $sql = "insert into ".$tblname."_hist (f_act,f_id,f_dttmcr,f_userid) values (3,".$row1['f_id'].",NOW(),".$_SESSION['loginid'].")";
                }
              if(($curtbl==35)||($curtbl==145))
                {
                if($row1['f_uved']==1)
                  {
                  $tdog = "cпецификации/заявке";
                  $tdid = 15;
                  if($row1['f_parenttype']==1){$tdog = "КП";}
                  elseif($row1['f_parenttype']==3){$tdog = "доставке";$tdid = 102;}
                  elseif($row1['f_parenttype']==4){$tdog = "договору";$tdid = 127;}
                  $togrp=0;$touserid=0;
                  if($row1['f_uvedtyp']==1){$touserid=$row1['f_adr'];}
                  elseif($row1['f_uvedtyp']==2){$togrp=$row1['f_adr'];}
                  mSendMail($_SESSION['loginid'],$togrp,$touserid,"Удалена операция","Была удалена операция № ".$row1['f_num_oper']." от ".$row1['f_dttmcr']." по <a href=\"".$redirect_uri."?pgid=".$tdid."&obid=".$row1['f_specid']."\">".$tdog."</a>","",35,$row1['f_id']);
                  }
                if($row1['f_wmsg']==1)
                  {
                  $tdog = "cпецификации/заявке";
                  $tdid = 15;
                  if($row1['f_parenttype']==1){$tdog = "КП";}
                  elseif($row1['f_parenttype']==3){$tdog = "доставке";$tdid = 102;}
                  elseif($row1['f_parenttype']==4){$tdog = "договору";$tdid = 127;}
                  $togrp=0;$touserid=0;
                  if($row1['f_wmsgadrt']==1){$touserid=$row1['f_wmsgadr'];}
                  elseif($row1['f_wmsgadrt']==2){$togrp=$row1['f_wmsgadr'];}
                  mSendMail($_SESSION['loginid'],$togrp,$touserid,"Удалена операция","Была удалена операция № ".$row1['f_num_oper']." от ".$row1['f_dttmcr']." по <a href=\"".$redirect_uri."?pgid=".$tdid."&obid=".$row1['f_specid']."\">".$tdog."</a>","",35,$row1['f_id']);
                  }
                }
              if(strlen($sql)>0){$dbh->exec($sql);}
              }
            }
          }
        if($candel==1)//!!!!!
          {
          $k = 0;
          $sql = "delete from $tblname WHERE f_id in (".$curidx.")";
          //echo $sql."|$curtbl|$curidx|".usenumdocreestr."|<br>";
          $k = $dbh->exec($sql);
          //sdid 2084
          $ee = $dbh->errorInfo();
          //~sdid 2084
          if($k>0)
            {
            if($curtbl==54)
              {
              if(strlen($insspecnum)>0)
                {mSendMail($_SESSION['loginid'],8,0,"Удалена запись о страховании для ".$insspecnum,"Удалена запись о страховании для ".$insspecnum,"",0,0);}
              }
            elseif($curtbl==77)//Если удалили Маршрут
              {
              $sql = "select f_id from ".DBPref."routes_ktk where f_routeid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>189,'curidx'=>$row1['f_id']);//удаляем связанные KTK
                delRowTbl($ki);
                }
              $sql = "select f_id from ".DBPref."routes_spec where f_routeid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>193,'curidx'=>$row1['f_id']);//удаляем связанные KTK
                delRowTbl($ki);
                }
              }
            elseif($curtbl==83)//Если удалили закрывающий докмуент
              {
              $sql = "select f_id from ".DBPref."akts_details where f_aktid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>180,'curidx'=>$row1['f_id']);//удаляем связанную детализацию
                delRowTbl($ki);
                }
              $sql  = "select ndr.f_id from ".DBPref."numdocreestr ndr where ndr.f_typedoc=10 and ndr.f_doctype=83 and ndr.f_docid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>255,'curidx'=>$row1['f_id'], 'f_docid'=>0,'f_out1C'=>0,'f_doctype'=>0);
                editRowTbl($ki);
                }
              }
            elseif($curtbl==84)//Если удалили выписки
              {
              $sql = "select f_id from ".DBPref."acchist_docs where f_acchistid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>147,'curidx'=>$row1['f_id']);//удаляем связанные документы
                delRowTbl($ki);
                }
              }
            elseif($curtbl==180)//Если удалили детализацию по закрывающему документу
              {
              $sql = "select f_id from ".DBPref."akts_details_opers where f_akts_detailsid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>184,'curidx'=>$row1['f_id']);//удаляем связь с операцией
                delRowTbl($ki);
                }
              }
            elseif(($curtbl==184)||($curtbl==222))//Если удалили связку операции с детализацией по закрывающему документу
              {
              if(usenumdocreestr==1)
                {
                $sql = "select f_id,f_numdocreestrid from ".DBPref."numdocreestr_docs where f_docid in (".$curidx.")";
                //echo $sql."|";
                $res1 = $dbh->query($sql);
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  $ki = Array('curtbl'=>257,'curidx'=>$row1['f_id']);//удаляем связку операции с детализацией по закрывающему документу
                  delRowTbl($ki);
                  $sql = "select ifnull(count(*),0) cnt from ".DBPref."numdocreestr_docs where f_numdocreestrid=".$row1['f_numdocreestrid'];
                  $res2 = $dbh->query($sql);
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    if($row2['cnt']==0)//если удалили связку с последним, то чистим реестр не снимая признак выгрузки
                      {
                      $ki = Array('curtbl'=>255,'curidx'=>$row1['f_numdocreestrid'],'f_docid'=>0,'f_doctype'=>0);
                      editRowTbl($ki);
                      }
                    }
                  }
                }
              //sdid1743
              if($adostorno==1)//удалили связку акт-дет-опер по сторнир операции
                {
                if($adostornoid>0)
                  {
                  $ki = Array('curtbl'=>289,'curidx'=>$adostornoid);//удаляем связку сторнирования операции
                  delRowTbl($ki);
                  //if($adostornooperid>0)
                  //  {
                  //  $sql  = "select f_id,f_agentrepid from ".DBPref."agentreps_opers where f_operid=$adostornooperid";
                  //  $res2 = $dbh->query($sql);
                  //  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  //    {
                  //    $agentrepid = $row2['f_agentrepid'];
                  //    $ki = Array('curtbl'=>287,'curidx'=>$row2['f_id']);//удаляем связку операции и отчета агента
                  //    delRowTbl($ki);
                  //    $sql = "select count(*) cnt from ".DBPref."agentreps_opers where f_agentrepid=$agentrepid";
                  //    $res2 = $dbh->query($sql);
                  //    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  //      {
                  //      if($row2['cnt']==0)
                  //        {
                  //        $ki = Array('curtbl'=>286,'curidx'=>$agentrepid);//удаляем пустой отчета агента
                  //        delRowTbl($ki);
                  //        }
                  //      }
                  //    }
                  //  }
                  if($adostornooperid>0)
                    {
                    //sdid3313 - предварительно удаляем связку операции и корректировки, если она есть
                    $sql  = "select f_id from ".DBPref."corrects_opers where f_correctoperid=$adostornooperid";
                    $res2 = $dbh->query($sql);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      $ki = Array('curtbl'=>291,'curidx'=>$row2['f_id']);//удаляем связку операции и корректировки
                      delRowTbl($ki);
                      }
                    //~sdid3313
                    $ki = Array('curtbl'=>35,'curidx'=>$adostornooperid);//удаляем операцию
                    delRowTbl($ki);
                    }
                  if($adostornoopridid>0)
                    {
                    $sql  = "select count(*) cnt from ".DBPref."spec_invoices_storno where f_operid=$adostornoprid and f_stornotype=3";
                    $res2 = $dbh->query($sql);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row2['cnt']==0)//если больше нет операций по сторнированной, то удаляем сторнирующую операцию
                        {
                        //$sql  = "select f_id,f_agentrepid from ".DBPref."agentreps_opers where f_operid=$adostornooprid";
                        //$res2 = $dbh->query($sql);
                        //if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        //  {
                        //  $agentrepid = $row2['f_agentrepid'];
                        //  $ki = Array('curtbl'=>287,'curidx'=>$row2['f_id']);//удаляем связку операции и отчета агента
                        //  delRowTbl($ki);
                        //  $sql = "select count(*) cnt from ".DBPref."agentreps_opers where f_agentrepid=$agentrepid";
                        //  $res2 = $dbh->query($sql);
                        //  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        //    {
                        //    if($row2['cnt']==0)
                        //      {
                        //      $ki = Array('curtbl'=>286,'curidx'=>$agentrepid);//удаляем пустой отчета агента
                        //      delRowTbl($ki);
                        //      }
                        //    }
                        //  }
                        $ki = Array('curtbl'=>289,'curidx'=>$adostornoopridid);//удаляем связку сторнирующей операции
                        delRowTbl($ki);
                        //sdid3313 - предварительно удаляем связку операции и корректировки, если она есть
                        $sql  = "select f_id from ".DBPref."corrects_opers where f_correctoperid=$adostornooprid";
                        $res2 = $dbh->query($sql);
                        if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                          {
                          $ki = Array('curtbl'=>291,'curidx'=>$row2['f_id']);//удаляем связку операции и корректировки
                          delRowTbl($ki);
                          }
                        //~sdid3313
                        $ki = Array('curtbl'=>35,'curidx'=>$adostornooprid);//удаляем сторнирующую операцию
                        delRowTbl($ki);
                        }
                      }
                    }
                  if($adostornopridid>0)
                    {
                    $sql  = "select count(*) cnt from ".DBPref."spec_invoices_storno where f_operid=$adostornoprid and f_stornotype in (2,3)";
                    $res2 = $dbh->query($sql);
                    if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                      {
                      if($row2['cnt']==0)//если больше нет операций по сторнированной, то удаляем связку
                        {
                        //$sql  = "select f_id,f_agentrepid from ".DBPref."agentreps_opers where f_operid=$adostornoprid";
                        //$res2 = $dbh->query($sql);
                        //if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        //  {
                        //  $agentrepid = $row2['f_agentrepid'];
                        //  $ki = Array('curtbl'=>287,'curidx'=>$row2['f_id']);//удаляем связку операции и отчета агента
                        //  delRowTbl($ki);
                        //  $sql = "select count(*) cnt from ".DBPref."agentreps_opers where f_agentrepid=$agentrepid";
                        //  $res2 = $dbh->query($sql);
                        //  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        //    {
                        //    if($row2['cnt']==0)
                        //      {
                        //      $ki = Array('curtbl'=>286,'curidx'=>$agentrepid);//удаляем пустой отчета агента
                        //      delRowTbl($ki);
                        //      }
                        //    }
                        //  }
                        $ki = Array('curtbl'=>289,'curidx'=>$adostornopridid);//удаляем связку сторнирнированной операции
                        delRowTbl($ki);
                        }
                      }
                    }
                  }
                }
              //~sdid1743
              }
            elseif($curtbl==190)//Если удалили курсовую разницу
              {
              $sql = "select f_id from ".DBPref."difrate_docs where f_difrateid in (".$curidx.")";
              $res1 = $dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                $ki = Array('curtbl'=>191,'curidx'=>$row1['f_id']);//удаляем связанные документы
                delRowTbl($ki);
                }
              }
            // sdid 1743
            elseif($curtbl==286) // Удалили отчет агента
              {
              $sql_d = "SELECT f_id FROM ".DBPref."agentreps_opers WHERE f_agentrepid=".$curidx;
              $conn = $dbh->query($sql_d);
              while($row_d = $conn->fetch(PDO::FETCH_ASSOC))
                {
                $ki = ['curtbl' => 287, 'curidx' => $row_d['f_id']];
                delRowTbl($ki);
                }
              }
            // ~ sdid 1743
            //sdid1583
            elseif(($curtbl==147)||(strcmp($tblname,DBPref."acchist_docs")==0))//После удаления ПП-документы
              {
              if(isset($ndeldif1583))
                {
                if($ndeldif1583==1)
                  {
                  if(isset($deldif1583))
                    {
                    if($deldif1583>0)
                      {
                      $ki = ['curtbl' => 190, 'curidx' => $deldif1583];
                      delRowTbl($ki);
                      }
                    }
                  if(isset($deldifd1583))
                    {
                    if($deldifd1583>0)
                      {
                      $ki = ['curtbl' => 191, 'curidx' => $deldifd1583];
                      delRowTbl($ki);
                      }
                    }
                  }
                }
              if(isset($ndelopr1583)&&isset($deloprid1583))
                {
                //echo "$ndelopr1583|$deloprid1583";
                if(($ndelopr1583==1)&&($deloprid1583>0))
                  {
                  $ki = ['curtbl' => 35, 'curidx' => $deloprid1583];
                  delRowTbl($ki);
                  }
                }
              }
            //~sdid1583
            //sdid2232
            elseif(strcmp($tblname,DBPref."maspayfe_opers")==0)//обобщенные платежи - операции - после удаления
              {
              if($maspayfeval == $sival && $sival > 0)
                {
                $new_sum = $maspayfesum - $sisum;
                $update_maspayfe = ['curtbl' => 230, 'curidx' => $maspayfid, 'f_sum' => $new_sum];
                $result = editRowTbl($update_maspayfe);
                }
              }
            //~sdid 2232
            // sdid 2504
            elseif(($curtbl == 35) || ($curtbl == 145))
            {
                $specinvLinksClass = new SpecinvLinks('specinv_links', $dbh);

                $sql = "SELECT f_id, f_specinv_id, f_specinv_link FROM ".DBPref."specinv_links WHERE f_specinv_link>0 AND f_specinv_id>0 AND (f_specinv_id=$curidx OR f_specinv_link=$curidx)";
                $conn = $dbh->query($sql);
                while ($row = $conn->fetch(PDO::FETCH_ASSOC))
                {
                    $link_id = $row['f_id'];
                    $is_deleted = false;
                    $links = [$row['f_specinv_id'], $row['f_specinv_link']];
                    foreach ($links as $link)
                    {

                        $to_delete = [
                            'curtbl' => 35,
                            'curidx' => $link
                        ];

                        try
                        {
                            $result_json = delRowTbl($to_delete);
                            $result = json_decode($result_json, true);

                            if ($result[0] == 'true')
                            {
                                if (!$is_deleted)
                                {
                                    $to_delete = [
                                        'curtbl' => 319,
                                        'curidx' => $link_id
                                    ];

                                    $specinvLinksClass->delRowTbl($dbh, $to_delete, "");
                                    $is_deleted = true;
                                }
                            }
                        }
                        catch (\Throwable $e)
                        {
                            echo get_throwable_msg($e);
                        }
                    }
                }
            }
            // ~ sdid 2504
            }
          //sdid 2084
          //$retval = "[true,\"ok\",\"".$k."\"]";
          $retval = "[true,\"ok\",\"".$k."\",\"".$ee[0]."\",\"".$ee[1]."\",\"".$ee[2]."\"]";
          //~sdid 2084
          }
        else
          {$retval = "[false,\"".$candelmsg."\"]";}
        }
      else
        {$retval = "[false,\"Не найдена таблица для удаления данных\"]";}
      }
    else
      {$retval = "[false,\"Не определена таблица или удаляемая запись\"]";}
    return $retval;
    }
//конец файла писать сверху
?>