<?php

if(!function_exists("checkclasslib_php"))
{include __DIR__."/../../lib/classlib.php";}

function checkAgentRepsclass_php(){return 0;}

require_once (__DIR__."/../../printShablonFuntions.php");
require_once (__DIR__."/../../lib/classlib.php");
require_once (__DIR__."/../../pofflib.php");
use PhpOffice\PhpWord\Element\Table;
require_once __DIR__."/../../Classes/PHPExcel-1.8/Classes/PHPExcel.php";
require_once __DIR__."/../../Classes/PHPExcel-1.8/Classes/PHPExcel/Writer/Excel2007.php";
// sdid 2363
if(!function_exists('checkoaclass_php'))
  {include __DIR__."/../oa/class.php";}
// ~ sdid 2363

// класс отчетов агента
class AgentReps extends mBaseClass
{
    private $dbh;

    public function __construct($tbl = "agentreps", $dbh = null)
    {
        parent::__construct($tbl);

        if($dbh instanceof PDO)
          {
          $this->dbh = $dbh;
          }
    }

    //sdid 3339
    //public function getAgentRepsSubView351($curPage=0, $rowsPerPage=0, $clid=0, $ctid=0, $itemcalcrpid=-1,$id=0)
    public function getAgentRepsSubView351($curPage=0, $rowsPerPage=0, $id=0)
      {
      //error_log("\n\nid = $id\n\n");
      $response = [false,"Неопределенная ошибка, модуль AgentReps->getAgentRepsSubView351 "];
      try
        {
        if(!isset($this->dbh)) {$this->dbh = dbconnect();}
        //if($clid>0 && $ctid && $itemcalcrpid>-1)
        if($id>0)//id группирующей операции, из нее выбираем необходимый набор, рекомендую сделать 
          {
          // ---------------------------------------------------------------------------------------------
          $response = new stdClass();
          $sqlfindocs = "
                ifnull((select 
                          GROUP_CONCAT(
                          case when s.f_maininv>0 then concat(concat('<a href=# title=\'\' style=\'color:blue;\' onclick=formCtgList(17,0,0,0,',s.f_id,')>',round(cast(s.f_sum as decimal(15,3)),2),' ',
                          ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          (select f_name from veda_spr where f_type=27 and f_num=s.f_type),'/',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),
                          '/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')(входит в агр);</a>\n'),
                          (select concat('<a href=# title=\'\' 
                          onclick=formCtgList(17,0,0,0,',ss.f_id,')>',round(cast(ss.f_sum as decimal(15,3)),2),' ',
                          (select f_uslstr from veda_spr where f_type=4 and f_num=ss.f_val),' (',
                          (select f_name from veda_spr where f_type=27 and f_num=ss.f_type),'/',
                          (select f_name from veda_spr where f_type=12 and f_num=ss.f_status),
                          '/',ss.f_num,'/',DATE_FORMAT(ss.f_dt,'%d.%m.%Y'),');</a>\n')
                          from veda_schets ss where ss.f_id=s.f_maininv) 
                          ) else concat('<a href=# title=\'\' 
                          onclick=formCtgList(17,0,0,0,',s.f_id,')>',round(cast(s.f_sum as decimal(15,3)),2),' ',
                          ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          (select f_name from veda_spr where f_type=27 and f_num=s.f_type),'/',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),
                          '/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),');</a>\n') end 
                          separator '') 
                        from veda_schets s where s.f_operid=si.f_id),'') schets,
                ifnull((select 
                          GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_maininv>0 then 'style=\'color:blue;\'' else '' end,
                            ' onclick=formCtgList(17,0,0,0,',s.f_id,')>',s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                            (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                            case when s.f_maininv>0 then '(входит в агр)' else '' end,';</a>',
                            case when s.f_maininv>0 then 
                              (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(17,0,0,0,',a.f_id,')>',a.f_sum,' ',
                                (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                                (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                                DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_schets a where a.f_id=s.f_maininv) 
                            else '' end
                          ) SEPARATOR '\n')
                        from veda_schets s,veda_schets_details ad,veda_schets_details_opers ado 
                        where ad.f_schetid=s.f_id and ado.f_schets_detailsid=ad.f_id and ado.f_operid=si.f_id),'') schets1,
                #########################################################################
                ifnull((select 
                          GROUP_CONCAT(concat('<a href=# ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                            ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                            (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                            case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>\n',
                            case when s.f_mainakt>0 then 
                              (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                                (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                                (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                                DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                            else '' end
                          ) SEPARATOR '')
                          from veda_akts s where s.f_operid=si.f_id),'') akts,
                #########################################################################											
                ifnull((select 
                          GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                            ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',ado.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                            s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),'/',
                            (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                            case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>',
                            case when s.f_mainakt>0 then 
                              (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                                (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                                (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                                DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                            else '' end
                          ) SEPARATOR '\n')
                          from veda_akts s,veda_akts_details ad,veda_akts_details_opers ado 
                          where ad.f_aktid=s.f_id and ado.f_akts_detailsid=ad.f_id and ado.f_operid=si.f_id),'') akts1,
                #########################################################################											
                ifnull((select 
                          GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                            ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',ado.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                            s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),'/',
                            (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                            case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>',
                            case when s.f_mainakt>0 then 
                              (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                                (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                                (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                                DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                            else '' end
                          ) SEPARATOR '\n')
                          from veda_akts s,veda_akts_details ad,veda_akts_details_opers ado 
                          where ad.f_aktid=s.f_id and ado.f_akts_detailsid=ad.f_id and 
                                ado.f_operid in (select f_operstornoid from veda_spec_invoices_storno where f_operid=si.f_id and f_stornotype=3)),'') akts2, 
                #########################################################################											
                ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(67,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                         ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' / -',s.f_outsum,' ',
                         ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),'/',
                         s.f_operdesc,');</a>)') SEPARATOR '\n') from veda_cash s where s.f_idoper=si.f_id),'') cashs, 
       
                ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(67,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                         ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' / -',s.f_outsum,' ',
                         ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),'/',
                         s.f_com,');</a>') SEPARATOR '\n') from veda_cash_details s where s.f_idoper=si.f_id),'') cashdets, 
                #########################################################################											
                ifnull((select GROUP_CONCAT(concat('<a href=?pgid=283&obid=',na.f_id,'&typeid=4 title=\'\' target=\'_blank\'>',
                                  (select sum(f_sum) from veda_netting_agr_details where f_nettingagrid=na.f_id and (f_operid=nad.f_operid or f_operid2=nad.f_operid2)), 
				' ', (select f_uslstr from veda_spr where f_type=4 and f_num=na.f_val),
                                  ' № ',na.f_num, '/', DATE_FORMAT(na.f_dt,'%d.%m.%Y'),'</a>') SEPARATOR '\n') 
                           from veda_netting_agr na, veda_netting_agr_details nad 
                          where (nad.f_operid=si.f_id or nad.f_operid2=si.f_id) 
                            and na.f_id=nad.f_nettingagrid ),'') nettingagreements, 
                #########################################################################											
                ifnull((SELECT GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(255,0,0,0,',ndr.f_id,')>',
                          (select f_valstr from veda_categs where f_ctgtype=28 and f_objecttype=2 and f_objectid=ndr.f_orgid),'П',
                          lpad(ndr.f_numdoc,6,'0'),'/',DATE_FORMAT(a.f_dt,'%d.%m.%Y'),'</a>') SEPARATOR  '\n') 
                        FROM veda_numdocreestr ndr,veda_numdocreestr_docs ndrd,veda_akts_details_opers ado,veda_akts a,veda_akts_details ad  
                        where ndrd.f_numdocreestrid=ndr.f_id and ndr.f_typedoc=8 and ndrd.f_docid=ado.f_id and ado.f_operid=si.f_id and 
                              ad.f_id=f_akts_detailsid and a.f_id=ad.f_aktid),'') invfakt, 
                #########################################################################											
                ifnull((SELECT 
                          GROUP_CONCAT(concat(ifnull(concat(dfd.f_grnd,'/'),''),
                                              ifnull(concat(df.f_kod1c,'/'),''),
                                              ifnull((select concat(f_name,'/') from veda_spr where f_type=108 and f_num=df.f_status),''),
                                              ifnull(concat(DATE_FORMAT(df.f_dt,'%d.%m.%Y'),'/'),''),
                                              case 
                                                when dfd.f_ktsum>0 then 
                                                  concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_kval),''))
                                                else
                                                  concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_dval),''))
                                              end
                                             ,';') SEPARATOR  ';\n') 
                        FROM veda_difrate df,veda_difrate_docs dfd 
                        where df.f_id=dfd.f_difrateid and dfd.f_operid=si.f_id),'') difrates,
                #########################################################################											
                ifnull((SELECT 
                          GROUP_CONCAT(concat(ifnull(concat(dfd.f_grnd,'/'),''),
                                              ifnull(concat(df.f_kod1c,'/'),''),
                                              ifnull((select concat(f_name,'/') from veda_spr where f_type=108 and f_num=df.f_status),''),
                                              ifnull(concat(DATE_FORMAT(df.f_dt,'%d.%m.%Y'),'/'),''),
                                              case 
                                                when dfd.f_ktsum>0 then 
                                                  case 
                                                    when dfd.f_kval>0 then 
                                                      concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_kval),''))
                                                    else
                                                      concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ₽')
                                                  end
                                                else
                                                  case 
                                                    when dfd.f_kval>0 then 
                                                      concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_dval),''))
                                                    else
                                                      concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ₽')
                                                  end
                                              end
                                             ,';') SEPARATOR  '\n') 
                        FROM veda_acchist ah,veda_acchist_docs ahd,veda_difrate df,veda_difrate_docs dfd 
                        where df.f_id=dfd.f_difrateid and case when dfd.f_ktsum>0 then dfd.f_ktacchistid else dfd.f_dtacchistid end=ah.f_id and 
                          ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=si.f_id),'') difrates1,
                #########################################################################											
                ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(100,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                         (select f_uslstr from veda_spr where f_type=4 and f_num=s.f_valcrd),' (',s.f_indt,')/ -',s.f_outsum,' ',
                         (select f_uslstr from veda_spr where f_type=4 and f_num=s.f_valcrd),' (',s.f_outdt,')/',s.f_comment,';</a>') SEPARATOR '\n') 
                        from veda_knrcard s where s.f_operid=si.f_id),'') crds, 
                ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(209,0,0,0,',s.f_id,')>',
                         (select f_name from veda_spr where f_type=27 and f_num=s.f_type),' ',s.f_sum,' ',
                         ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                         s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s where s.f_objtype in (0,35) and s.f_objid=si.f_id and s.f_subtype<>1),'') lpays,
                #########################################################################											
                ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(339,0,0,0,',s.f_id,')>',
                          (select f_name from veda_spr where f_type=27 and f_num=s.f_type),' ',s.f_sum,' ',
                          ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                          s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s 
                        where s.f_subtype=1 and s.f_objtype in (0,35) and 
                          ((s.f_objid=si.f_id and s.f_objidсorrectdest=0) or (s.f_objtypeсorrectdest=35 and s.f_objidсorrectdest=si.f_id))),'') transferlpays,
                ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(340,0,0,0,',s.f_id,')>',
                          'Прочее списание',' ',s.f_sum,' ',
                          ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                          s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s 
                        where s.f_subtype=1 and s.f_objtype in (0,35) and 
                          ((s.f_objidist=si.f_id and s.f_objidсorrect=0) or (s.f_objtypeсorrect=35 and s.f_objidсorrect=si.f_id))),'') transferlpaysout,
       
                ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(84,0,0,0,',s.f_id,')>',d.f_clssum,'/',ifnull(d.f_curs,1),' ',
                                 ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',s.f_sum,'/',s.f_ppnum,'/',
                                 DATE_FORMAT(s.f_ppdt,'%d.%m.%Y'),'/',s.f_grnd,');</a>') SEPARATOR '\n') from veda_acchist s,veda_acchist_docs d 
                        where s.f_id=d.f_acchistid and d.f_doctype=3 and d.f_docid=si.f_id),'') acchist  
       
                        ";
          // ищем id отчета агента, в который входит операция. Если не входит, запрашиваем данные отдельным запросом
          $itemcalcrpstr = "";
          $agentrepid = 0;
          $sql = "select f_agentrepid from veda_agentreps_opers where f_operid=$id";
          $res1 = $this->dbh->query($sql);
          if($row1 = $res1->fetch(PDO::FETCH_ASSOC)) {$agentrepid=$row1['f_agentrepid'];}
          if($agentrepid>0)
            {
            $sql = "SELECT /*aro.*,*/
                      (select GROUP_CONCAT(
                                         concat('<a href=# onclick=formCtgList(145,0,0,0,',i.f_id,');>',
                                         concat(
                                         case when i.f_parenttype=4 then
                                           (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl
                                            where cl.f_id=d.f_contrid and d.f_id=i.f_specid)
                                         else
                                           (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname)
                                            from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl
                                            where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=i.f_specid)
                                         end,
                                         '/',substr(i.f_dttmcr,1,10),'/',i.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=i.f_idoper),'/',i.f_sum,
                                         ' ',(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=i.f_val),'/',i.f_id)
                                         ,'</a>&nbsp;')
                                         SEPARATOR '\n')
                                from ".DBPref."spec_invoices i
                                where i.f_id=aro.f_operid) oper,
                      si.f_num_oper,
                      DATE(si.f_dttmcr) f_dttmcr,
                      (select f_name from veda_typeopers where f_id=si.f_idoper) opername,
                      concat(FORMAT(si.f_sum,2),' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val)) sum_val,
                      si.f_id operid,
                      (SELECT f_name FROM veda_spr where f_type=27 and f_num=si.f_tfd) tfd,
                      ifnull((select lc.f_num from veda_corrects lc,veda_corrects_opers lco 
                         where lco.f_correctid=lc.f_id and lco.f_correctoperid=si.f_id),0) correctid,
                      si.f_invcom f_invcom,
                      si.f_com f_com,
                      $sqlfindocs
                      ,si.f_id as f_id
                    FROM veda_agentreps_opers aro, veda_spec_invoices si 
                   WHERE si.f_id=aro.f_operid 
                         #$itemcalcrpstr
                     and aro.f_agentrepid=(select f_agentrepid from veda_agentreps_opers where f_operid=$id) 
                     and si.f_itemcalcrp=(select f_itemcalcrp from veda_spec_invoices where f_id=$id)
                  ";
            }
          else
            {
            $sql = "select concat('<a href=# onclick=formCtgList(145,0,0,0,',si.f_id,');>',
                              concat(
                                case when si.f_parenttype=4 then
                                   (select f_dogname from veda_dogs d,veda_clients cl
                                    where cl.f_id=d.f_contrid and d.f_id=si.f_specid)
                                 else
                                   (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname)
                                    from veda_specs s,veda_dogs d,veda_clients cl
                                    where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=si.f_specid)
                                 end,
                                 '/',substr(si.f_dttmcr,1,10),'/',si.f_num_oper,'/',(select f_name from veda_typeopers where f_id=si.f_idoper),'/',si.f_sum,
                                 ' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val),'/',si.f_id)
                                 ,'</a>&nbsp;') as oper,
                      si.f_num_oper,
                      DATE(si.f_dttmcr) f_dttmcr,
                      (select f_name from veda_typeopers where f_id=si.f_idoper) opername,
                      concat(FORMAT(si.f_sum,2),' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val)) sum_val,
                      si.f_id operid,
                      (SELECT f_name FROM veda_spr where f_type=27 and f_num=si.f_tfd) tfd,
                      ifnull((select lc.f_num from veda_corrects lc,veda_corrects_opers lco 
                         where lco.f_correctid=lc.f_id and lco.f_correctoperid=si.f_id),0) correctid,
                      si.f_invcom f_invcom,
                      si.f_com f_com, 
                      $sqlfindocs
                       ,si.f_id as f_id
                     from veda_spec_invoices si 
                    where si.f_specid=(select f_specid from veda_spec_invoices where f_parenttype=2 and f_id=$id)           
                          #$itemcalcrpstr
                      and si.f_itemcalcrp=(select f_itemcalcrp from veda_spec_invoices where f_id=$id)
                      and si.f_id not in (select ago.f_operid 
                                            from veda_agentreps ag, veda_agentreps_opers ago 
                                           where ag.f_specid=si.f_specid and ago.f_agentrepid=ag.f_id)
                   ";
            }
          $res = $this->dbh->query($sql);
          $i=0;
          while($row = $res->fetch(PDO::FETCH_ASSOC))
            {
              //собираем счета по операции
              $schets = "";
              $schets = $row['schets'].$row['schets1'];
              if(strlen($schets)>0){$schets = "Счета:\n".$schets;}
              //собираем наличные операции
              $сschets = "";
              $сschets = $row['cashs'];
              if(strlen($сschets)>0){$schets = $schets."Наличные:\n".$сschets;}
              $сschets = $row['cashdets'];
              if(strlen($сschets)>0){$schets = $schets."Наличные:\n".$сschets;}
              //собираем расходы по карте
              $сschets = "";
              //собираем соглашения о зачете взаимных требований
              $сschets = $row['nettingagreements'];
              if(strlen($сschets)>0){$schets = $schets."\nСоглашения о зачете:\n".$сschets."\n";}
              $сschets = "";
              $сschets = $row['crds'];
              if(strlen($сschets)>0){$schets = $schets."Расход по карте:\n".$сschets;}
              //собираем прочие платежи
              $сschets = "";
              $сschets = $row['lpays'];
              if(strlen($сschets)>0){$schets = $schets."Прочие:\n".$сschets;}
              $сschets = "";
              $сschets = $row['transferlpays'];
              $сschets.= $row['transferlpaysout'];
              if(strlen($сschets)>0){$schets = $schets."Переносы:\n".$сschets;}
              //собираем закрывающие документы
              $сschets = "";
              $lсschets = "";
              $сschets = $row['akts'].$row['akts1'];
              if(strlen($сschets)>0){$schets = $schets."Закрывающие документы:\n".$сschets.$lсschets;}
              //собираем закрывающие документы по сторнирующим операциям
              if(strlen($row['akts2'])>0){$schets = $schets."Закрывающие документы по сторнирующим:\n".$row['akts2'];}
              //собираем привязанные документы по выписке
              $сschets = "";
              $сschets = $row['acchist'];
              if(strlen($сschets)>0){$schets = $schets."\nДанные выписки:\n".$сschets;}
              $сschets = $row['invfakt'];
              if(strlen($сschets)>0){$schets = $schets."\nСчета-фактуры выданные:\n".$сschets;}
              //sdid3319
              $сschets = $row['difrates'];
              if(strlen($сschets)>0){$schets = $schets."\nКурсовая разница:\n".$сschets;}
              $сschets = $row['difrates1'];
              if(strlen($сschets)>0){$schets = $schets."\nКурсовая разница:\n".$сschets;}
       
              $response->rows[$i]['cell']=array(
                  "<input type=\"checkbox\" id=\"cb351_".$row['f_id']."\">",
                  $row['oper'],
                  $row['f_num_oper'],
                  $row['f_dttmcr'],
                  $row['opername'],
                  $row['sum_val'],
                  $row['operid'],
                  $schets,
                  $row['tfd'],
                  $row['correctid'],
                  $row['f_invcom'],
                  $row['f_com'],
                  $row['f_id']
              );
              $i++;
            }
          }
        return $response;
        }
      catch (\Throwable $e)
        {
        return [false, 'Error (module getAgentRepsSubView351): '.$e->getMessage()];
        }
      }
    //~sdid 3339
    //sdid 3339
    public function doMoveAgentRepsOpers($inpar)
      {
      $answ = [false,"Неверные входные параметры. Модуль agentReps->doMoveAgentRepsOpers()"];
      $msg = "";
      $errcnt = 0;
      if(isset($inpar))
        {
        //error_log("\n\ninpar = ".var_export($inpar,true)."\n\n");
        if(!isset($this->dbh)) {$this->dbh = dbconnect();}
        $tableid = 0;
        if(isset($inpar['tableid'])) {$tableid = $inpar['tableid'];}
        $selstr = ""; // перечень операций либо veda_agentreps_opers.f_id
        if(isset($inpar['selstr'])) {$selstr = $inpar['selstr'];}
        $operSelect = 0;
        if(isset($inpar['operSelect'])) {$operSelect = $inpar['operSelect'];}
        $agentrepid = 0; //id в какой отчет перенести операции из selstr
        if(isset($inpar['getSelAgentRepsVal'])) {$agentrepid = $inpar['getSelAgentRepsVal'];}
        $isnewagentreport = 0;
        if(isset($inpar['createNewAgentReport'])) 
          {
          if(strcmp($inpar['createNewAgentReport'],"true")==0) {$isnewagentreport = 1;}
          elseif(strcmp($inpar['createNewAgentReport'],"false")==0) {$isnewagentreport = 0;}
          }
        $isopers = 0; // признак: если 3, то selstr это id операций; если 2, то selstr это veda_agentreps_opers.f_id
        if(isset($inpar['ctidDiv'])) {$isopers = $inpar['ctidDiv'];}
        $isopers = 3; // новый способ - работаем только по operid

        if($tableid==287 && $operSelect==35 /*&& $agentrepid>0*/)
          {
          $ar_opers = explode(",",$selstr);
          if(count($ar_opers)>0)
            {
            if($isopers==2)
              {
              // определим id старого отчета и запомним все f_operid из veda_agentreps_opers, которые будем переносить, и найдем f_specid, номер отчета, максимальный номер отчета 
              $oldagentrepid = 0;
              $operids = "";
              $curspecid = 0;
              $sql = "select aro.f_agentrepid, GROUP_CONCAT(aro.f_operid) operids, ar.f_specid, ar.f_num,
                             (select MAX(ar1.f_num) from veda_agentreps ar1 where ar1.f_specid=ar.f_specid) rep_num_max,
                             DATE(MAX(si.f_dttmcr)) agentrep_dt	
                       from veda_agentreps_opers aro, veda_agentreps ar, veda_spec_invoices si 
                      where ar.f_id=aro.f_agentrepid
                        and si.f_id=aro.f_operid 
                        and aro.f_id in (".$selstr.")";
              $res = $this->dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC)) 
                {
                $oldagentrepid = $row['f_agentrepid'];
                $operids       = $row['operids'];
                $ar_operids    = explode(",",$operids);
                $curspecid     = $row['f_specid'];
                $rep_num_max   = $row['rep_num_max'];
                $agentrep_dt   = $row['agentrep_dt'];
                }
              // удалим из старого отчета переносимые в новый отчет записи
              $i=0;
              foreach($ar_opers as $val)
                {
                //$ar_del = ['curtbl'=>287, 'curidx' => $ar_opers[$i]];
                $ar_del = ['curtbl'=>287, 'curidx' => $val];
                //$del_res = delRowTbl($this->dbh,$ar_del);
                $del_res = delRowTbl($ar_del);
                $i++;
                }
              //если требуется создать новый отчет
              if($isnewagentreport==1)
                {
                $create_new_rep = [
                    'curtbl'   => 286,
                    'f_specid' => $curspecid,
                    'f_num'    => $rep_num_max+1,
                    'f_dttm'   => $agentrep_dt,
                    'f_status' => 0
                ];
                $res_ar_add = json_decode(addRowTbl($create_new_rep));
                if($res_ar_add[0])
                  {
                  $agentrepid = $res_ar_add[2];
                  $msg = $msg."\nУспешно создан отчет агента ИД ".$res_ar_add[2];
                  }
                else
                  {
                  $msg = $msg."\nОшибка создания отчета агента по ".json_encode($create_new_rep);
                  $errcnt++;
                  }
                }
              // добавляем операции в отчет
              foreach($ar_operids as $val)
                {
                $ar_add = [
                    'curtbl'       => 287, 
                    'f_agentrepid' => $agentrepid,
                    'f_operid'     => $val
                ];
                $add_res = json_decode(addRowTbl($ar_add));
                if(!$add_res[0]) {$errcnt++; $msg = $msg."\nОшибка добавления операции в отчет агента по ".json_encode($ar_add);}
                }
              if($errcnt==0)
                {
                $this->updateAgentReps($curspecid);
                $msg = $msg."\nОперации перенесены в отчет агента ИД ".$agentrepid;
                $answ = [true,$msg,0];
                }
              }
            elseif($isopers==3)
              {
              // определим id старого отчета и запомним все f_operid из veda_agentreps_opers, которые будем переносить, и найдем f_specid, номер отчета, максимальный номер отчета 
              $oldagentrepid = 0;
              $operids = "";
              $curspecid = 0;
              $sql = "select si.f_specid, 
                             (select MAX(ar1.f_num) from veda_agentreps ar1 where ar1.f_specid=si.f_specid) rep_num_max,
                             DATE(MAX(si.f_dttmcr)) agentrep_dt	
                       from veda_spec_invoices si 
                      where si.f_id in (".$selstr.")";
              $res = $this->dbh->query($sql);
              if($row = $res->fetch(PDO::FETCH_ASSOC)) 
                {
                //$oldagentrepid = $row['f_agentrepid'];
                $operids       = $selstr;
                $ar_operids    = explode(",",$operids);
                $curspecid     = $row['f_specid'];
                $rep_num_max   = $row['rep_num_max'];
                $agentrep_dt   = $row['agentrep_dt'];
                }
              // удалим из старого отчета переносимые в новый отчет записи
              $sql = "select f_id from veda_agentreps_opers where f_operid in ($operids)";
              $res1 = $this->dbh->query($sql);
              while($row1 = $res1->fetch(PDO::FETCH_ASSOC)) 
                {
                $ar_del = ['curtbl'=>287, 'curidx' => $row1['f_id']];
                $del_res = delRowTbl($ar_del);
                }
              //если требуется создать новый отчет
              if($isnewagentreport==1)
                {
                $create_new_rep = [
                    'curtbl'   => 286,
                    'f_specid' => $curspecid,
                    'f_num'    => $rep_num_max+1,
                    'f_dttm'   => $agentrep_dt,
                    'f_status' => 0
                ];
                $res_ar_add = json_decode(addRowTbl($create_new_rep));
                if($res_ar_add[0])
                  {
                  $agentrepid = $res_ar_add[2];
                  $msg = $msg."\nУспешно создан отчет агента ИД ".$res_ar_add[2];
                  }
                else
                  {
                  $msg = $msg."\nОшибка создания отчета агента по ".json_encode($create_new_rep);
                  $errcnt++;
                  }
                }
              // добавляем операции в отчет
              error_log("\n\n добавляем операции в отчет errcnt = $errcnt\n\n");
              foreach($ar_operids as $val)
                {
                $ar_add = [
                    'curtbl'       => 287, 
                    'f_agentrepid' => $agentrepid,
                    'f_operid'     => $val
                ];
                $add_res = json_decode(addRowTbl($ar_add));
                if(!$add_res[0]) {$errcnt++; $msg = $msg."\nОшибка добавления операции в отчет агента по ".json_encode($ar_add);}
                }
              if($errcnt==0)
                {
                $this->updateAgentReps($curspecid);
                $msg = $msg."\nОперации перенесены в отчет агента ИД ".$agentrepid;
                $answ = [true,$msg,0];
                }
              }
            }
          }
        }
      //return json_encode($answ);
      return $answ;
      }
    //~sdid 3339
    //    public function getAgentReps($curPage=1, $rowsPerPage=10, $clid=0) sdid 2073
    public function getAgentReps($curPage=1, $rowsPerPage=10, $clid=0, $fid=0) // sdid 2073
    {
        try
        {
            $response = new stdClass();
            // bugfix 30.05.2024
//        if($clid>0)
//          {$clid=" WHERE f_specid=".$clid." ";}
            if($clid>0)
            {
                $this->updateAgentReps($clid);
                $clid=" WHERE f_specid=".$clid." ";
            }
            // ~ bugfix 30.05.2024
            else
            {$clid="";}
            // sdid 2073
            if($fid > 0)
            {
                $and = "";
                if (strlen($clid) > 0)
                {
                    $and = " AND ";
                }
                $fid = $and." WHERE f_id=".$fid." ";
            }
            else
            {$fid = "";}
            // ~ sdid 2073
            $sql = "SELECT COUNT(s.f_id) AS count FROM ".DBPref."agentreps s where s.f_id>0";
            $rows = $this->dbh->query($sql);
            $totalRows = $rows->fetch(PDO::FETCH_ASSOC);
            $firstRowIndex = $curPage * $rowsPerPage - $rowsPerPage;
            $response->page = $curPage;
            $response->total = ceil($totalRows['count'] / $rowsPerPage);
            $response->records = $totalRows['count'];
            // sdid 2073
//        $sql = "SELECT f_id, f_num,
            //       DATE_FORMAT(f_dttm, '%d.%m.%Y %H:%i') dttm,
            //       f_status
            //       FROM veda_agentreps".$clid;
            $sql = "SELECT ar.f_id, ar.f_num,
                   ar.f_dttm dttm,
                   ar.f_specid,
                   ar.f_vozm,
                   ar.f_nvozm,
                   ar.f_total,
                   (SELECT MAX(si.f_outbuhperiod) FROM " . DBPref . "agentreps_opers aro, " . DBPref . "spec_invoices si WHERE aro.f_operid=si.f_id AND aro.f_agentrepid=ar.f_id) f_outbuhperiod,
                   ar.f_status
                   ,case when ar.f_status=1 then 'true' else 'false' end as status
                 FROM veda_agentreps ar " . $clid . $fid;
            // ~ sdid 2073
            $res = $this->dbh->query($sql);
            $i=0;
            $refs = "";
            while($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                $response->rows[$i]['cell']=array(
                    "<a href='#' title='Операции' onclick='formCtgList(287,2,".$row['f_id'].");'>".getSpanStrOpr("list-alt")."</a>&nbsp;",
                    $row['f_num'],
                    $row['dttm'],
                    $row['f_vozm'],
                    $row['f_nvozm'],
                    $row['f_total'],
                    $row['status'],
//                "<a href=# onclick=alert() >Скачать отчет</a>".
//                "<object data='data:application/pdf;base64,".$row['f_flrep']."' type='application/pdf' style='height:200px;width:60%'></object>",
//                "<a href=# onclick=downloadAgentRep(".$row['f_id'].") >Скачать отчет</a>", sdid 2363
//                "<a href='?pgid=305obid=".$row['f_id']."&typeid=1' target='_blank' >Отчет агента</a>", // sdid 2363
                    "<a href='#' onclick=\"showTabsForm('',6,305,".$row['f_id'].",6)\"  >Отчет агента</a>", // sdid 2363
                    // sdid 2073
                    // ~ sdid 2073
                    $row['f_id']
                );
                $i++;
            }
            return $response;
        }
        catch (\Throwable $e)
        {
            echo json_encode([false, 'Error: '.$e->getMessage() . "; " . $e->getFile() . "; " .$e->getLine()]);
        }
    }

    //sdid 3339
    // вывести группы операций по признаку "статья РП"
    public function getAgentRepsOperationsGroup($curPage=1, $rowsPerPage=10, $clid=0, $ctid=0)
    {
        $response = new stdClass();
        $sql = "SELECT COUNT(s.f_id) AS count FROM ".DBPref."agentreps_opers s where s.f_id>0";
        $rows = $this->dbh->query($sql);
        $totalRows = $rows->fetch(PDO::FETCH_ASSOC);
        $firstRowIndex = $curPage * $rowsPerPage - $rowsPerPage;
        $response->page = $curPage;
        $response->total = ceil($totalRows['count'] / $rowsPerPage);
        $response->records = $totalRows['count'];
        if($ctid != 3)
          {
          $sql = "SELECT /*aro.*,*/ si.f_itemcalcrp, si.f_id,
                         (select f_name from veda_spr where f_type=166 and f_num=si.f_itemcalcrp) itemcalcrp #sdid 3496 #,
                         #(SELECT IFNULL(MAX(c.f_num), 0) FROM ".DBPref."corrects c, ".DBPref."corrects_opers co WHERE c.f_id=co.f_correctid AND co.f_correctoperid=si.f_id AND c.f_specid=si.f_specid) correct_num #sdid 3496
                    FROM veda_agentreps_opers aro, veda_spec_invoices si WHERE si.f_id=aro.f_operid and aro.f_agentrepid=$clid
                   #sdid 3496
                   #group by correct_num, si.f_itemcalcrp ORDER BY si.f_itemcalcrp ASC
                   group by si.f_itemcalcrp ORDER BY si.f_itemcalcrp ASC
                   #~sdid 3496
                  ";
          }
        else
          {
          $sql = "select  (select f_name from veda_spr where f_type=166 and f_num=si.f_itemcalcrp) itemcalcrp,
                     #(SELECT IFNULL(MAX(c.f_num), 0) FROM ".DBPref."corrects c, ".DBPref."corrects_opers co WHERE c.f_id=co.f_correctid AND co.f_correctoperid=si.f_id AND c.f_specid=si.f_specid) correct_num, #sdid 3496 
                     si.f_itemcalcrp, si.f_id
                   from veda_spec_invoices si 
                  where si.f_specid=$clid
                    and si.f_id not in (select ago.f_operid 
                                          from veda_agentreps ag, veda_agentreps_opers ago 
                                         where ag.f_specid=si.f_specid and ago.f_agentrepid=ag.f_id)
                  #sdid 3496
                  #group by correct_num, si.f_itemcalcrp ORDER BY si.f_itemcalcrp ASC 		
                  group by si.f_itemcalcrp ORDER BY si.f_itemcalcrp ASC
                  #~sdid 3496
                 ";
          }
        $res = $this->dbh->query($sql);
        $i=0;
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $response->rows[$i]['cell']=array(
            $row['itemcalcrp'],
            $row['f_id']
            );
          $i++;
          }
        #1###############################################################################
       return $response;
       }
    //~sdid 3339
    //public function getAgentRepsOperations($curPage=1, $rowsPerPage=10, $clid=0) //sdid 3339
    public function getAgentRepsOperations($curPage=1, $rowsPerPage=10, $clid=0, $ctid=0, $itemcalcrp=-1) //sdid 3339
      {
      $response = new stdClass();
      $sql = "SELECT COUNT(s.f_id) AS count FROM ".DBPref."agentreps_opers s where s.f_id>0";
      $rows = $this->dbh->query($sql);
      $totalRows = $rows->fetch(PDO::FETCH_ASSOC);
      $firstRowIndex = $curPage * $rowsPerPage - $rowsPerPage;
      $response->page = $curPage;
      $response->total = ceil($totalRows['count'] / $rowsPerPage);
      $response->records = $totalRows['count'];
      //sdid 3339
      $itemcalcrpstr = "";
      if($itemcalcrp>-1) { $itemcalcrpstr = " and si.f_itemcalcrp=$itemcalcrp ";}
      $sqlfindocs = "
              ifnull((select 
                        GROUP_CONCAT(
                        case when s.f_maininv>0 then concat(concat('<a href=# title=\'\' style=\'color:blue;\' onclick=formCtgList(17,0,0,0,',s.f_id,')>',round(cast(s.f_sum as decimal(15,3)),2),' ',
                        ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                        (select f_name from veda_spr where f_type=27 and f_num=s.f_type),'/',
                        (select f_name from veda_spr where f_type=12 and f_num=s.f_status),
                        '/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')(входит в агр);</a>\n'),
                        (select concat('<a href=# title=\'\' 
                        onclick=formCtgList(17,0,0,0,',ss.f_id,')>',round(cast(ss.f_sum as decimal(15,3)),2),' ',
                        (select f_uslstr from veda_spr where f_type=4 and f_num=ss.f_val),' (',
                        (select f_name from veda_spr where f_type=27 and f_num=ss.f_type),'/',
                        (select f_name from veda_spr where f_type=12 and f_num=ss.f_status),
                        '/',ss.f_num,'/',DATE_FORMAT(ss.f_dt,'%d.%m.%Y'),');</a>\n')
                        from veda_schets ss where ss.f_id=s.f_maininv) 
                        ) else concat('<a href=# title=\'\' 
                        onclick=formCtgList(17,0,0,0,',s.f_id,')>',round(cast(s.f_sum as decimal(15,3)),2),' ',
                        ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                        (select f_name from veda_spr where f_type=27 and f_num=s.f_type),'/',
                        (select f_name from veda_spr where f_type=12 and f_num=s.f_status),
                        '/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),');</a>\n') end 
                        separator '') 
                      from veda_schets s where s.f_operid=si.f_id),'') schets,
              ifnull((select 
                        GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_maininv>0 then 'style=\'color:blue;\'' else '' end,
                          ' onclick=formCtgList(17,0,0,0,',s.f_id,')>',s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                          case when s.f_maininv>0 then '(входит в агр)' else '' end,';</a>',
                          case when s.f_maininv>0 then 
                            (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(17,0,0,0,',a.f_id,')>',a.f_sum,' ',
                              (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                              (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                              DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_schets a where a.f_id=s.f_maininv) 
                          else '' end
                        ) SEPARATOR '\n')
                      from veda_schets s,veda_schets_details ad,veda_schets_details_opers ado 
                      where ad.f_schetid=s.f_id and ado.f_schets_detailsid=ad.f_id and ado.f_operid=si.f_id),'') schets1,
              #########################################################################
              ifnull((select 
                        GROUP_CONCAT(concat('<a href=# ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                          ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                          case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>\n',
                          case when s.f_mainakt>0 then 
                            (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                              (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                              (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                              DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                          else '' end
                        ) SEPARATOR '')
                        from veda_akts s where s.f_operid=si.f_id),'') akts,
              #########################################################################											
              ifnull((select 
                        GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                          ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',ado.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),'/',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                          case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>',
                          case when s.f_mainakt>0 then 
                            (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                              (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                              (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                              DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                          else '' end
                        ) SEPARATOR '\n')
                        from veda_akts s,veda_akts_details ad,veda_akts_details_opers ado 
                        where ad.f_aktid=s.f_id and ado.f_akts_detailsid=ad.f_id and ado.f_operid=si.f_id),'') akts1,
              #########################################################################											
              ifnull((select 
                        GROUP_CONCAT(concat('<a href=\'#\' title=\'\' ',case when s.f_mainakt>0 then 'style=\'color:blue;\'' else '' end,
                          ' onclick=formCtgList(83,0,0,0,',s.f_id,')>',ado.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',
                          s.f_sum,' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),'/',
                          (select f_name from veda_spr where f_type=12 and f_num=s.f_status),'/',s.f_num,'/',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),')',
                          case when s.f_mainakt>0 then '(входит в агр)' else '' end,';</a>',
                          case when s.f_mainakt>0 then 
                            (select concat('<a href=\'#\' title=\'\' onclick=formCtgList(83,0,0,0,',a.f_id,')>',a.f_sum,' ',
                              (select f_uslstr from veda_spr where f_type=4 and f_num=a.f_val),' (',
                              (select f_name from veda_spr where f_type=12 and f_num=a.f_status),'/',a.f_num,'/',
                              DATE_FORMAT(a.f_dt,'%d.%m.%Y'),');</a>\n') from veda_akts a where a.f_id=s.f_mainakt) 
                          else '' end
                        ) SEPARATOR '\n')
                        from veda_akts s,veda_akts_details ad,veda_akts_details_opers ado 
                        where ad.f_aktid=s.f_id and ado.f_akts_detailsid=ad.f_id and 
                              ado.f_operid in (select f_operstornoid from veda_spec_invoices_storno where f_operid=si.f_id and f_stornotype=3)),'') akts2, 
              #########################################################################											
              ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(67,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                       ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' / -',s.f_outsum,' ',
                       ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),'/',
                       s.f_operdesc,');</a>)') SEPARATOR '\n') from veda_cash s where s.f_idoper=si.f_id),'') cashs, 

              ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(67,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                       ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' / -',s.f_outsum,' ',
                       ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_dt,'%d.%m.%Y'),'/',
                       s.f_com,');</a>') SEPARATOR '\n') from veda_cash_details s where s.f_idoper=si.f_id),'') cashdets, 
              #########################################################################											
              ifnull((select GROUP_CONCAT(concat('<a href=?pgid=283&obid=',na.f_id,'&typeid=4 title=\'\' target=\'_blank\'>',
                                (select sum(f_sum) from veda_netting_agr_details where f_nettingagrid=na.f_id and (f_operid=nad.f_operid or f_operid2=nad.f_operid2)), 
				' ', (select f_uslstr from veda_spr where f_type=4 and f_num=na.f_val),
                                ' № ',na.f_num, '/', DATE_FORMAT(na.f_dt,'%d.%m.%Y'),'</a>') SEPARATOR '\n') 
                         from veda_netting_agr na, veda_netting_agr_details nad 
                        where (nad.f_operid=si.f_id or nad.f_operid2=si.f_id) 
                          and na.f_id=nad.f_nettingagrid ),'') nettingagreements, 
              #########################################################################											
              ifnull((SELECT GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(255,0,0,0,',ndr.f_id,')>',
                        (select f_valstr from veda_categs where f_ctgtype=28 and f_objecttype=2 and f_objectid=ndr.f_orgid),'П',
                        lpad(ndr.f_numdoc,6,'0'),'/',DATE_FORMAT(a.f_dt,'%d.%m.%Y'),'</a>') SEPARATOR  '\n') 
                      FROM veda_numdocreestr ndr,veda_numdocreestr_docs ndrd,veda_akts_details_opers ado,veda_akts a,veda_akts_details ad  
                      where ndrd.f_numdocreestrid=ndr.f_id and ndr.f_typedoc=8 and ndrd.f_docid=ado.f_id and ado.f_operid=si.f_id and 
                            ad.f_id=f_akts_detailsid and a.f_id=ad.f_aktid),'') invfakt, 
              #########################################################################											
              ifnull((SELECT 
                        GROUP_CONCAT(concat(ifnull(concat(dfd.f_grnd,'/'),''),
                                            ifnull(concat(df.f_kod1c,'/'),''),
                                            ifnull((select concat(f_name,'/') from veda_spr where f_type=108 and f_num=df.f_status),''),
                                            ifnull(concat(DATE_FORMAT(df.f_dt,'%d.%m.%Y'),'/'),''),
                                            case 
                                              when dfd.f_ktsum>0 then 
                                                concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_kval),''))
                                              else
                                                concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_dval),''))
                                            end
                                           ,';') SEPARATOR  ';\n') 
                      FROM veda_difrate df,veda_difrate_docs dfd 
                      where df.f_id=dfd.f_difrateid and dfd.f_operid=si.f_id),'') difrates,
              #########################################################################											
              ifnull((SELECT 
                        GROUP_CONCAT(concat(ifnull(concat(dfd.f_grnd,'/'),''),
                                            ifnull(concat(df.f_kod1c,'/'),''),
                                            ifnull((select concat(f_name,'/') from veda_spr where f_type=108 and f_num=df.f_status),''),
                                            ifnull(concat(DATE_FORMAT(df.f_dt,'%d.%m.%Y'),'/'),''),
                                            case 
                                              when dfd.f_ktsum>0 then 
                                                case 
                                                  when dfd.f_kval>0 then 
                                                    concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_kval),''))
                                                  else
                                                    concat(FORMAT(dfd.f_ktsum,2,'ru_RU'),' ₽')
                                                end
                                              else
                                                case 
                                                  when dfd.f_kval>0 then 
                                                    concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ',ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=dfd.f_dval),''))
                                                  else
                                                    concat('-',FORMAT(dfd.f_dtsum,2,'ru_RU'),' ₽')
                                                end
                                            end
                                           ,';') SEPARATOR  '\n') 
                      FROM veda_acchist ah,veda_acchist_docs ahd,veda_difrate df,veda_difrate_docs dfd 
                      where df.f_id=dfd.f_difrateid and case when dfd.f_ktsum>0 then dfd.f_ktacchistid else dfd.f_dtacchistid end=ah.f_id and 
                        ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=si.f_id),'') difrates1,
              #########################################################################											
              ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(100,0,0,0,',s.f_id,')>+',s.f_insum,' ',
                       (select f_uslstr from veda_spr where f_type=4 and f_num=s.f_valcrd),' (',s.f_indt,')/ -',s.f_outsum,' ',
                       (select f_uslstr from veda_spr where f_type=4 and f_num=s.f_valcrd),' (',s.f_outdt,')/',s.f_comment,';</a>') SEPARATOR '\n') 
                      from veda_knrcard s where s.f_operid=si.f_id),'') crds, 
              ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(209,0,0,0,',s.f_id,')>',
                       (select f_name from veda_spr where f_type=27 and f_num=s.f_type),' ',s.f_sum,' ',
                       ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                       s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s where s.f_objtype in (0,35) and s.f_objid=si.f_id and s.f_subtype<>1),'') lpays,
              #########################################################################											
              ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(339,0,0,0,',s.f_id,')>',
                        (select f_name from veda_spr where f_type=27 and f_num=s.f_type),' ',s.f_sum,' ',
                        ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                        s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s 
                      where s.f_subtype=1 and s.f_objtype in (0,35) and 
                        ((s.f_objid=si.f_id and s.f_objidсorrectdest=0) or (s.f_objtypeсorrectdest=35 and s.f_objidсorrectdest=si.f_id))),'') transferlpays,
              ifnull((select concat(GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(340,0,0,0,',s.f_id,')>',
                        'Прочее списание',' ',s.f_sum,' ',
                        ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',DATE_FORMAT(s.f_docdt,'%d.%m.%Y'),'/',s.f_docnum,')',
                        s.f_com,';</a>') SEPARATOR '\n'),'\n') from veda_pays s 
                      where s.f_subtype=1 and s.f_objtype in (0,35) and 
                        ((s.f_objidist=si.f_id and s.f_objidсorrect=0) or (s.f_objtypeсorrect=35 and s.f_objidсorrect=si.f_id))),'') transferlpaysout,

              ifnull((select GROUP_CONCAT(concat('<a href=# title=\'\' onclick=formCtgList(84,0,0,0,',s.f_id,')>',d.f_clssum,'/',ifnull(d.f_curs,1),' ',
                               ifnull((select f_uslstr from veda_spr where f_type=4 and f_num=s.f_val),''),' (',s.f_sum,'/',s.f_ppnum,'/',
                               DATE_FORMAT(s.f_ppdt,'%d.%m.%Y'),'/',s.f_grnd,');</a>') SEPARATOR '\n') from veda_acchist s,veda_acchist_docs d 
                      where s.f_id=d.f_acchistid and d.f_doctype=3 and d.f_docid=si.f_id),'') acchist  
                      ";
        if($ctid != 3)
          {
        $sql = "SELECT aro.*,
       (select GROUP_CONCAT(
                          concat('<a href=# onclick=formCtgList(145,0,0,0,',i.f_id,');>',
                          concat(
                          case when i.f_parenttype=4 then
                            (select f_dogname from ".DBPref."dogs d,".DBPref."clients cl
                             where cl.f_id=d.f_contrid and d.f_id=i.f_specid)
                          else
                            (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname)
                             from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients cl
                             where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=i.f_specid)
                          end,
                          '/',substr(i.f_dttmcr,1,10),'/',i.f_num_oper,'/',(select f_name from ".DBPref."typeopers where f_id=i.f_idoper),'/',i.f_sum,
                          ' ',(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=i.f_val),'/',i.f_id)
                          ,'</a>&nbsp;')
                          SEPARATOR '\n')
                 from ".DBPref."spec_invoices i
                              where i.f_id=aro.f_operid) oper,
                    si.f_num_oper,
                    DATE(si.f_dttmcr) f_dttmcr,
                    (select f_name from veda_typeopers where f_id=si.f_idoper) opername,
                    concat(FORMAT(si.f_sum,2),' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val)) sum_val,
                    si.f_id operid,
                    (SELECT f_name FROM veda_spr where f_type=27 and f_num=si.f_tfd) tfd,
                    ifnull((select lc.f_num from veda_corrects lc,veda_corrects_opers lco 
                       where lco.f_correctid=lc.f_id and lco.f_correctoperid=si.f_id),0) correctid,
                    si.f_invcom f_invcom,
                    si.f_com f_com,
                    $sqlfindocs
                  FROM veda_agentreps_opers aro, veda_spec_invoices si 
                 WHERE si.f_id=aro.f_operid 
                       $itemcalcrpstr
                   and aro.f_agentrepid=".$clid;
          }
        else
          {
          $sql = "select concat('<a href=# onclick=formCtgList(145,0,0,0,',si.f_id,');>',
                            concat(
                              case when si.f_parenttype=4 then
                                 (select f_dogname from veda_dogs d,veda_clients cl
                                  where cl.f_id=d.f_contrid and d.f_id=si.f_specid)
                               else
                                 (select concat(d.f_dogname,'/',s.f_num,'/',cl.f_cname)
                                  from veda_specs s,veda_dogs d,veda_clients cl
                                  where cl.f_id=d.f_contrid and s.f_dogid=d.f_id and s.f_id=si.f_specid)
                               end,
                               '/',substr(si.f_dttmcr,1,10),'/',si.f_num_oper,'/',(select f_name from veda_typeopers where f_id=si.f_idoper),'/',si.f_sum,
                               ' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val),'/',si.f_id)
                               ,'</a>&nbsp;') as oper,
                    si.f_num_oper,
                    DATE(si.f_dttmcr) f_dttmcr,
                    (select f_name from veda_typeopers where f_id=si.f_idoper) opername,
                    concat(FORMAT(si.f_sum,2),' ',(select f_uslstr from veda_spr where f_type=4 and f_num=si.f_val)) sum_val,
                    si.f_id operid,
                    (SELECT f_name FROM veda_spr where f_type=27 and f_num=si.f_tfd) tfd,
                    ifnull((select lc.f_num from veda_corrects lc,veda_corrects_opers lco 
                       where lco.f_correctid=lc.f_id and lco.f_correctoperid=si.f_id),0) correctid,
                    si.f_invcom f_invcom,
                    si.f_com f_com,
                    $sqlfindocs
                     ,si.f_id as f_id
                   from veda_spec_invoices si 
                  where si.f_specid=$clid
                        $itemcalcrpstr
                    and si.f_id not in (select ago.f_operid 
                                          from veda_agentreps ag, veda_agentreps_opers ago 
                                         where ag.f_specid=si.f_specid and ago.f_agentrepid=ag.f_id)
                 ";

          }
        //~sdid 3339
        $res = $this->dbh->query($sql);
        $i=0;
        while($row = $res->fetch(PDO::FETCH_ASSOC))
        {
          //sdid 3339
          //собираем счета по операции
          $schets = "";
          $schets = $row['schets'].$row['schets1'];
          if(strlen($schets)>0){$schets = "Счета:\n".$schets;}
          //собираем наличные операции
          $сschets = "";
          $сschets = $row['cashs'];
          if(strlen($сschets)>0){$schets = $schets."Наличные:\n".$сschets;}
          $сschets = $row['cashdets'];
          if(strlen($сschets)>0){$schets = $schets."Наличные:\n".$сschets;}
          //собираем расходы по карте
          $сschets = "";
          //собираем соглашения о зачете взаимных требований
          $сschets = $row['nettingagreements'];
          if(strlen($сschets)>0){$schets = $schets."\nСоглашения о зачете:\n".$сschets."\n";}
          $сschets = "";
          $сschets = $row['crds'];
          if(strlen($сschets)>0){$schets = $schets."Расход по карте:\n".$сschets;}
          //собираем прочие платежи
          $сschets = "";
          $сschets = $row['lpays'];
          if(strlen($сschets)>0){$schets = $schets."Прочие:\n".$сschets;}
          $сschets = "";
          $сschets = $row['transferlpays'];
          $сschets.= $row['transferlpaysout'];
          if(strlen($сschets)>0){$schets = $schets."Переносы:\n".$сschets;}
          //собираем закрывающие документы
          $сschets = "";
          $lсschets = "";
          $сschets = $row['akts'].$row['akts1'];
          if(strlen($сschets)>0){$schets = $schets."Закрывающие документы:\n".$сschets.$lсschets;}
          //собираем закрывающие документы по сторнирующим операциям
          if(strlen($row['akts2'])>0){$schets = $schets."Закрывающие документы по сторнирующим:\n".$row['akts2'];}
          //собираем привязанные документы по выписке
          $сschets = "";
          $сschets = $row['acchist'];
          if(strlen($сschets)>0){$schets = $schets."\nДанные выписки:\n".$сschets;}
          $сschets = $row['invfakt'];
          if(strlen($сschets)>0){$schets = $schets."\nСчета-фактуры выданные:\n".$сschets;}
          //sdid3319
          $сschets = $row['difrates'];
          if(strlen($сschets)>0){$schets = $schets."\nКурсовая разница:\n".$сschets;}
          $сschets = $row['difrates1'];
          if(strlen($сschets)>0){$schets = $schets."\nКурсовая разница:\n".$сschets;}
            $response->rows[$i]['cell']=array(
                $row['oper'],
                $row['f_num_oper'],
                $row['f_dttmcr'],
                $row['opername'],
                $row['sum_val'],
                $row['operid'],
                $schets,
                $row['tfd'],
                $row['correctid'],
                $row['f_invcom'],
                $row['f_com'],
                $row['f_id']
            );
          //~sdid 3339
            $i++;
        }
        return $response;
    }

    //public function createAgentRepByOpers($oprst=0, $oprid=0) sdid 2109
    public function createAgentRepByOpers($oprst=0, $oprid=0, $do_download=true) // sdid 2109
    {
        $messages = [];
        $response = [true, ""];

        $data = [];

        if ($oprst > 0 && $oprid > 0)
        {
//            $sql = "SELECT
//                        (CASE si.f_parenttype
//                            WHEN 4 THEN
//                                (select
//                                    (select lsp.f_id
//                                    from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
//                                             veda_clients o,veda_contacts ct
//                                    where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
//                                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 and
//                                        lct.f_objectid=si.f_id)
//                                from veda_dogs dd,veda_clients oo,veda_clients cc
//                                where oo.f_id=dd.f_orgid and cc.f_id=dd.f_contrid and dd.f_id=si.f_specid)
//                            ELSE
//                                (SELECT f_id FROM veda_specs WHERE f_id=si.f_specid)
//                        END) specid,
//                        (SELECT f_id FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_id,
//                        (SELECT f_num FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_num,
//                        (SELECT MAX(f_num) FROM veda_agentreps WHERE f_specid=si.f_specid) rep_num_max,
//                        si.f_id
//                        FROM veda_spec_invoices si
//                        WHERE si.f_id IN (".$oprid.")";

            $sql = "SELECT g.*,
                    (SELECT GROUP_CONCAT(aro.f_id) FROM veda_agentreps ar, veda_agentreps_opers aro
                            WHERE aro.f_agentrepid=ar.f_id AND ar.f_specid=g.specid AND aro.f_operid=g.f_id) is_present
                     FROM
                        (SELECT
                            (CASE si.f_parenttype
                                    WHEN 4 THEN
                                            (select
                                                    (select lsp.f_id
                                                    from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
                                                                     veda_clients o,veda_contacts ct
                                                    where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
                                                            o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 and
                                                            lct.f_objectid=si.f_id)
                                            from veda_dogs dd,veda_clients oo,veda_clients cc
                                            where oo.f_id=dd.f_orgid and cc.f_id=dd.f_contrid and dd.f_id=si.f_specid)
                                    ELSE
                                            (SELECT f_id FROM veda_specs WHERE f_id=si.f_specid)
                            END) specid,
                            (SELECT f_id FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_id,
                            (SELECT f_num FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_num,
                            (SELECT MAX(f_num) FROM veda_agentreps WHERE f_specid=si.f_specid) rep_num_max,
                            MAX(si.f_dttmcr) agentrep_dt, -- 01.05.2024
                            si.f_id
                            FROM veda_spec_invoices si
                            WHERE si.f_id IN (".$oprid.")
                        ) g";
            $conn = $this->dbh->query($sql);
            while ($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
                $rep_id = -1;
                $rep_num = -1;
                error_log("\n\nrep_num0:$rep_num\n\n");
                if (strlen($row['specid']) > 0)
//                if (strlen($row['specid']) > 0 && strlen($row['is_present']) === 0)
                {
                    $spec_id = $row['specid'];
                    if(!key_exists($spec_id, $data))
                    {
                        $data[$spec_id] = ['opers' => [$row['f_id']]];

                        if (strlen($row['rep_id']) > 0 && strlen($row['rep_num']) > 0)
                        {
                            $rep_id = $row['rep_id'];
                            $rep_num = $row['rep_num'];
                            error_log("\n\nrep_num1:$rep_num\n\n");
                            $data[$spec_id]['rep_id'] = $rep_id;
                            $data[$spec_id]['rep_num'] = $rep_num;

                            $messages[] = "Успешно найден отчет ид ". $rep_id;
                        }
                        else
                        {
                            if (strlen($row['rep_num_max']) > 0)
                            {
                                $rep_num = $row['rep_num_max'] + 1;
                                error_log("\n\nrep_num2:$rep_num\n\n");
                            }
                            else
                            {
                                $rep_num = 1;
                                error_log("\n\nrep_num3:$rep_num\n\n");
                            }

                            $data[$spec_id]['rep_num'] = $rep_num;

                            $create_new_rep = [
                                'curtbl' => 286,
                                'f_specid' => $spec_id,
                                'f_num' => $rep_num,
                                //'f_dttm' => date('Y-m-d H:i:s'), 01.05.2024
                                'f_dttm' => $row['agentrep_dt'], // 01.05.2024
                                'f_status' => 0
                            ];

                            $result = json_decode(addRowTbl($create_new_rep));
                            if ($result[0])
                            {
                                $data[$spec_id]['rep_id'] = $result[2];

                                $messages[] = "Успешно создан отчет ид ". $result[2];
                            }
                            else
                            {
                                $messages[] = "Ошибка создания счет-операции по ".json_encode($create_new_rep);
                            }
                        }
                    }
                    else
                    {
                        $data[$spec_id]['opers'][] = $row['f_id'];
                    }
                }
            }

            foreach($data as $spec_id => $dataArray)
            {
                foreach ($dataArray['opers'] as $operid)
                {
                    $create_new_rep_oper = [
                        'curtbl' => 287,
                        'f_agentrepid' => $dataArray['rep_id'],
                        'f_operid' => $operid
                    ];

                    $result = json_decode(addRowTbl($create_new_rep_oper));

                    if ($result[0])
                    {
                        $messages[] = "Успешно создан отчет-операция ид ". $result[2];
                    }
                    else
                    {
                        $messages[] = "Ошибка создания счет-операции по f_agentrepid=".$dataArray['rep_id']." f_operid=".$operid;
                    }
                }

                $messages[] = " -> " . $oprst . "; " . implode(',', $dataArray['opers']) . "; " . $dataArray['rep_id'] . " <- ";

//                $result = $this->createAgentRepFile($oprst, implode(',', $dataArray['opers']), $dataArray['rep_id'], $dataArray['rep_num']);
                //$result = $this->createAgentRepFile(15, $spec_id, $dataArray['rep_id'], $dataArray['rep_num']); sdid 2109
                //$result = $this->createAgentRepFile(15, $spec_id, $dataArray['rep_id'], $dataArray['rep_num'], 0, $do_download); // sdid 2109 //sdid 3496
                $result = $this->createAgentRepFile1(15, $spec_id, $dataArray['rep_id'], $dataArray['rep_num'], 0, $do_download, 1); // sdid 2109 //sdid 3496
                $messages[] = $result;
            }
        }
        else
        {
            $response = [false, "Неверные параметры: " . $oprst . "; $oprid"];
        }

        if ($response[0])
        {
            $response[1] = $messages;
        }

        return $response;
    }

    //public function createAgentRepFile($oprst, $oprid, $rep_id, $rep_num, $old_new_flag=0) sdid 2109
    public function createAgentRepFile($oprst, $oprid, $rep_id, $rep_num, $old_new_flag=0, $do_download=true) // sdid 2109
      {
      if(!isset($this->dbh)) {$this->dbh = dbconnect();}
      // sdid 2363
      $oa = new oa('oa', $this->dbh);
      if ($rep_id > 0)
        {
        $sql_ = "SELECT GROUP_CONCAT(f_id) ids FROM ".DBPref."oa WHERE f_agentrep=".$rep_id;
        $conn_ = $this->dbh->query($sql_);
        if($row_ = $conn_->fetch(PDO::FETCH_ASSOC))
          {
          $ids_ = $row_['ids'];
          if(strlen($ids_) > 0)
            {
            $sql_ = "DELETE FROM ".DBPref."oa WHERE f_id IN (".$ids_.")";
            $this->dbh->exec($sql_);
            }
          }
        }
        // ~ sdid 2363
        // sdid 2073
        $f_vozm = 0;
        $f_nvozm = 0;
        $f_total = 0;
        // ~ sdid 2073
        $opers = "0";

        //$sql = "SELECT GROUP_CONCAT(aro.f_operid) opers FROM veda_agentreps_opers aro WHERE aro.f_agentrepid=".$rep_id; sdid 2109
        $sql = "SELECT IFNULL(GROUP_CONCAT(aro.f_operid),0) opers FROM veda_agentreps_opers aro WHERE aro.f_agentrepid=".$rep_id; // sdid 2109
        $conn = $this->dbh->query($sql);
        if ($row = $conn->fetch(PDO::FETCH_ASSOC))
        {
            $opers = $row['opers'];
        }



        $old_new = "";
        $and_sql = "";
        if ($old_new_flag == 1)
        {
            $old_new = "f_outbuhperiod=0 ";
            $and_sql = " AND ";
        } elseif ($old_new_flag == 2)
        {
            $old_new = "f_outbuhperiod=1 ";
            $and_sql = " AND ";
        }

        $isdrep = "";
        if(($oprst==35)||($oprst==145))
        {$isdrep = $oprid;}

        $wphpword=1;

        $dtformat = "%d.%m.%Y";

        $bordero = array(
            'borders'=>array(
                'outline' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                ),
            )
        );
        $borderi = array(
            'borders'=>array(
                'inside' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                ),
            )
        );
        $borderb = array(
            'borders'=>array(
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN		),
            )
        );

        $table_settings = array($dtformat, $bordero, $borderi, $borderb);

        $trade_secret_path = ""; // 1825

        if($wphpword==1)
        {
            PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
            $xls = new PHPExcel();
            $xls->setActiveSheetIndex(0);
            $sheet = $xls->getActiveSheet();
            $sheet->getDefaultStyle()->getFont()->setName('Times New Roman');
            $sheet->getDefaultStyle()->getFont()->setSize(12);
            //Ориентация страницы и  размер листа
            $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
            $sheet->getPageSetup()->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageMargins()->setRight(0.1);
            $sheet->getPageMargins()->setLeft(0.1);
            $sheet->setTitle('Отчет об исполнении');
            //$sheet->getColumnDimensionByColumn("A")->setAutoSize(true);
            $sheet->getColumnDimension("A")->setWidth(40);
            $sheet->getColumnDimension("B")->setWidth(15);
            $sheet->getColumnDimension("C")->setWidth(20);
            $sheet->getColumnDimension("D")->setWidth(20);
            $sheet->getColumnDimension("E")->setWidth(15);
            $sheet->getColumnDimension("F")->setWidth(30);
            $sheet->getColumnDimension("G")->setWidth(20);
            $sheet->getColumnDimension("H")->setWidth(15);
            $sheet->getColumnDimension("I")->setWidth(15);
            $sheet->getStyle("A1:A1:")->getFont()->setBold(true);
            $sheet->getStyle("A1")->getFont()->setSize(12);
            $sheet->getStyle("A2")->getFont()->setSize(12);
            $sheet->getStyle("A3")->getFont()->setSize(12);
            $sheet->getStyle("A4")->getFont()->setSize(12);
            $sheet->getStyle("A5")->getFont()->setSize(12);
            $sheet->getStyle("A6")->getFont()->setSize(12);
            $sheet->getStyle("A4:A4:")->getFont()->setBold(true);
            $sheet->getStyle("A8:I8:")->getFont()->setBold(true);
            $sheet->mergeCells("A8:I8");
            $sheet->getStyle("A8")->getFont()->setSize(12);
            $sheet->getStyle("A8")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        if(($oprst==35)||($oprst==145))
        {$oprid = "select distinct(f_specid) from ".DBPref."spec_invoices where f_id in (".$oprid.") ";}
        $sql ="select s.f_subtype ssubtype,d.f_subtype dogsubtype,d.f_dogname,d.f_dogdate,d.f_contrid,d.f_orgid,s.f_num,s.f_dt,s.f_typez, ".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_weco=2),0) weco,".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_status<>17 AND dt.f_zayavnum IS NOT NULL AND dt.f_zayavnum != ''),0) shipment_before_dt,
                 d.f_onestrdocs onestrdocs,".
            "  (select f_name from ".DBPref."spr where f_type=39 and f_num=d.f_city) dogcity, ".
            "  c.f_addname contrname,c.f_inn contrinn,c.f_kpp contrkpp,".
            "  case when s.f_dtreptocl is null or s.f_dtreptocl='0000-00-00' then ifnull(DATE_FORMAT(s.f_dttoclnt,'%d.%m.%Y'),'') else ifnull(DATE_FORMAT(s.f_dtreptocl,'%d.%m.%Y'),'') end dttoclnt,".
            " (SELECT ctgs.f_valstr FROM ".DBPref."categs ctgs WHERE ctgs.f_ctgtype=34 AND ctgs.f_objectid=o.f_id AND ctgs.f_objecttype=2 LIMIT 1) trade_secret_logo, " . // sdid 1825
            "  concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=o.f_opf),' ',o.f_cname) orgFname,o.f_opf,o.f_addname orgname,o.f_inn orginn,o.f_kpp orgkpp,o.f_sno osno ".
            "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c  ".
            "where d.f_id=s.f_dogid and o.f_id=d.f_orgid and c.f_id=d.f_contrid and s.f_id in (".$oprid.")";
        //echo $sql;
        $res = $this->dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {

            // sdid 1825
            if (strlen($row['trade_secret_logo']) > 0)
            {
                $trade_secret_path = $row['trade_secret_logo'];
            }
            // ~ sdid 1825

            $zayav   = "спецификация";
            if($row['ssubtype']==3)
            {$zayav   = "Поручение ТП";}
            elseif($row['f_typez']==3)
            {$zayav   = "заявка";}
            $opodpd  = "";
            $isppodp = "";
            $cpodpd  = "";
            $clnpodp = "";
            $opodpbd = "";
            $opodpbn = "";
            $dogsubtype = $row["dogsubtype"];
            $agent = "Агент";
            $agenta = "Агента";
            $princ = "Принципал";
            error_log("\n\nrep_num4:$rep_num\n\n");
            $agentrep_type = "Дополнительный";
            if ($rep_num == 1)
            {
                $agentrep_type = "Основной";
            }
            $repsname = $agentrep_type." отчет об исполнении поручения";
            if(strlen($isdrep)>0){$repsname = $agentrep_type." отчет об исполнении поручения";}
            $repdop = "засчитывается в счет исполнения агентского договора";
            if($dogsubtype==1)
            {$agent = "Исполнитель";
                $agenta = "Исполнителя";
                $princ = "Заказчик";
                $repsname = "Отчет экспедитора";
                if(strlen($isdrep)>0){$repsname = $agentrep_type." отчет экспедитора";}
                $repdop = "возмещаемых услуг";}
            if(strlen($isdrep)>0){$isdrep = " and s.f_id in (".$isdrep.") ";}

            // sdid 2347
            $orgid = $row['f_orgid'];
            $orgname = $row['orgname'];
            $orgFname = "";
            if ($orgid > 0)
            {
                $valid_dt = "";

                if ($rep_id>0)
                {
                    $sql_dt = "select ifnull(SUBSTRING(f_dttm,1,10),'') dt from ".DBPref."agentreps where f_id=$rep_id";
                    $conn = $this->dbh->query($sql_dt);
                    if($row_dt = $conn->fetch(PDO::FETCH_ASSOC)){$valid_dt = $row_dt['dt'];}
                }
                else
                {
                    if(strlen($isdrep)>0)
                    {
                        $sql = "select ifnull(SUBSTRING(s.f_dttmcr,1,10),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id IN (".$opers.") order by s.f_dttmcr desc";
                        $res1 = $this->dbh->query($sql);
                        if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$valid_dt = $row1['dttoclnt'];}
                    }
                }

                if (strlen($valid_dt)  > 0)
                {
                    $result = get_client_contact_data($orgid, 17, $valid_dt);
                    if ($result[0]) { $orgFname = $result[1]; }

                    $result = get_client_contact_data($orgid, 18, $valid_dt);
                    if ($result[0]) { $orgname = $result[1]; }

                    $result = get_client_contact_data($orgid, 22, $valid_dt);
                    if ($result[0]) { $trade_secret_path = $result[1]; }

                }
            }
            // ~ sdid 2347

            if($wphpword==1)
            {
//                $sheet->setCellValue("A1", mb_strtoupper($agent).": ".$row["orgname"]." ИНН ".$row["orginn"]." КПП ".$row["orgkpp"]); sdid 2347
                $sheet->setCellValue("A1", mb_strtoupper($agent).": ".$orgname." ИНН ".$row["orginn"]." КПП ".$row["orgkpp"]); // sdid 2347
            }
            $orgc = new mClient();
            $orgc->f_id = $row['f_orgid'];
            //$orgc->initClient();
            if($orgc->initForPrint(0)==1)
            {
                if(isset($orgc->ofcontacts['uradr']))
                {
                    //if(isset($orgc->ofcontacts['dolz'])){$isppodp = $orgc->ofcontacts['dolz']." ";}
                    if(isset($orgc->ofcontacts['fio']))    {$isppodp = "/".$isppodp.$orgc->ofcontacts['fio']."/";}
                    // sdid 2347
//                    if(isset($orgc->ofcontacts['dolzip']))   {$opodpd  = $opodpd.$orgc->ofcontacts['dolzip']." ".$row['orgFname'];}
//                    if(isset($orgc->ofcontacts['buhdolzip'])){$opodpbd = $opodpbd.$orgc->ofcontacts['buhdolzip']." ".$row['orgFname'];}
                    if (strlen($orgFname) === 0) { $orgFname = $row['orgFname']; }
                    if(isset($orgc->ofcontacts['dolzip']))   {$opodpd  = $opodpd.$orgc->ofcontacts['dolzip']." ".$orgFname;}
                    if(isset($orgc->ofcontacts['buhdolzip'])){$opodpbd = $opodpbd.$orgc->ofcontacts['buhdolzip']." ".$orgFname;}
                    // ~ sdid 2347
                    if(isset($orgc->ofcontacts['buhfio'])) {$opodpbn = "/".$opodpbn.$orgc->ofcontacts['buhfio']."/";}

                    $cstr = $orgc->ofcontacts['uradr'];
                    if(isset($orgc->ofcontacts['mphone'])){$cstr = $cstr.", ".$orgc->ofcontacts['mphone'];}
                    if($wphpword==1){$sheet->setCellValue("A2",$cstr);}
                }
                if(isset($orgc->ofcontacts['mbnkacc']))
                    if($wphpword==1){{$sheet->setCellValue("A3",$orgc->ofcontacts['mbnkacc']);}}
            }
            if($wphpword==1){$sheet->setCellValue("A4", mb_strtoupper($princ).": ".$row["contrname"]." ИНН ".$row["contrinn"]." КПП ".$row["contrkpp"]);}
            $contr = new mClient();
            $contr->f_id = $row['f_contrid'];
            //$contr->initClient();
            if($contr->initForPrint(0)==1)
            {
                //if(isset($contr->ofcontacts['dolz'])){$clnpodp = $contr->ofcontacts['dolz']." ";}
                if(isset($contr->ofcontacts['fio'])) {$clnpodp = $contr->ofcontacts['fio'];}
                if(isset($contr->ofcontacts['dolz'])){$cpodpd  = $cpodpd.$contr->ofcontacts['dolzip'];}
                if(isset($contr->ofcontacts['uradr']))
                {
                    $cstr = $contr->ofcontacts['uradr'];
                    if(isset($contr->ofcontacts['mphone'])){$cstr = $cstr.", ".$contr->ofcontacts['mphone'];}
                    if($wphpword==1){$sheet->setCellValue("A5",$cstr);}
                }
                if(isset($contr->ofcontacts['mbnkacc']))
                    if($wphpword==1){{$sheet->setCellValue("A6",$contr->ofcontacts['mbnkacc']);}}
            }
            if($wphpword==1){
                $sheet->setCellValue("A8", $repsname);}
            //error_log("\n\nrep_id = $rep_id\n\n");
            if($rep_id>0)
            {
                //$sql = "select ifnull(DATE_FORMAT(f_dttm,'%d.%m.%Y'),'') dttoclnt from ".DBPref."agentreps s where s.f_id=$rep_id"; //sdid 3339
                $sql = "select ifnull(DATE_FORMAT(f_dtrep,'%d.%m.%Y'),'') dttoclnt from ".DBPref."agentreps s where s.f_id=$rep_id"; //sdid 3339
                $res1 = $this->dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$dtsost = $row1['dttoclnt'];}
            }
            else
            {
                $dtsost = $row['dttoclnt'];
                if(strlen($isdrep)>0)
                {
                    $letter = "";
                    if ($old_new_flag > 0)
                    {
                        $letter = " s.";
                    }
                    //$sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id>0 ".$isdrep." order by s.f_id desc";
//                $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id>0 ".$isdrep.$and_sql.$letter.$old_new." order by s.f_dttmcr desc";
                    $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id IN (".$opers.") order by s.f_dttmcr desc";
                    //echo $sql."<br>";
                    $res1 = $this->dbh->query($sql);
                    if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$dtsost = $row1['dttoclnt'];}
                }
            }
            if($wphpword==1){
                $sheet->setCellValue("A10", "Дата составления: ".$dtsost." г.");
                $sheet->setCellValue("A11", "Место составления: г. ".$row['dogcity']);
                //$sheet->setCellValue("A13", "Представляем отчет об исполнении поручения ".$row['contrname']."  по агентскому договору № ".$row['f_dogname']." от ".format_dt($row['f_dogdate'],2,1)." г., спецификация  № ".$row['f_num']." от ".format_dt($row['f_dt'],0,1)." г.");
                $sheet->setCellValue("A13", "Представляем отчет об исполнении поручения ".$row['contrname']."  по договору № ".$row['f_dogname']." от ".format_dt($row['f_dogdate'],2,1)." г., ".$zayav."  № ".$row['f_num']." от ".format_dt($row['f_dt'],0,1)." г.");
                $sheet->setCellValue("A14", "Получено от ".$princ."а в счет исполнения поручения: ");}
            $cstr = 15;
            //echo $dtsost."<br>";
            //собираем оступившие платежные поурчения
            $sdtsost = "";
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
            {$sdtsost = " and h.f_ppdt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
            $letter = "";
            if ($old_new_flag > 0)
            {
                $letter = " si.";
            }
//            $sql1 = "select h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,
//                     (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,
//                     (select sum(f_clssum) from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
//                       (select f_id from ".DBPref."spec_invoices where f_parenttype=2 ".$and_sql.$old_new." and f_specid in (".$oprid.") ) and f_acchistid=h.f_id) clssum
//                   from ".DBPref."acchist h where f_type=0 and f_contrid=".$row['f_contrid']." and f_id in
//                     (select f_acchistid from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
//                       (select s.f_id from ".DBPref."spec_invoices s where s.f_parenttype=2 ".$isdrep.$and_sql.$letter.$old_new." and s.f_specid in (".$oprid."))) ".$sdtsost;

            $sql1 = "select h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,
                     (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,
                     (select sum(f_clssum) from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                       (".$opers.") and f_acchistid=h.f_id) clssum
                   from ".DBPref."acchist h where f_type=0 and f_contrid=".$row['f_contrid']." and f_id in
                     (select f_acchistid from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                       (".$opers.")) ".$sdtsost;
            //echo $sql1."<br>";
            //and s.f_dttmcr<='".$dtsost."_".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'
            $res1 = $this->dbh->query($sql1);
            $ps = 0;$psv="";
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
                if($wphpword==1)
                {
                    $sheet->setCellValue("C".$cstr,"п/п ".$row1['f_ppnum']." от ".format_dt($row1['f_ppdt'],0,1));
                    $sheet->getStyle("D".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    //sdid - 777 - вернул clssum
                    //$sheet->setCellValue("D".$cstr,number_format($row1['f_sum'],2, ',', ' ')." ".$row1['val']);}
                    $cval = $row1['val'];
                    if($row1['f_val']==643)
                    {$csum = $row1['clssum'];}
                    else
                    {$csum = $row1['f_sum'];}
                    $sheet->setCellValue("D".$cstr,number_format($csum,2, ',', ' ')." ".$cval);
                }
                $ps=$ps+$row1['clssum'];
                //$ps=$ps+$row1['f_sum'];
                //~ sdid - 777
                $psv=$row1['val'];
                $cstr++;
            }
            if($wphpword==1)
            {
                $sheet->getStyle("B".$cstr.":D".$cstr)->getFont()->setBold(true);
                $sheet->setCellValue("B".$cstr,"Итого");
                $sheet->getStyle("D".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue("D".$cstr,number_format($ps,2, ',', ' ')." ".$psv);
                $cstr++;
                $stl = $cstr;
                $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue("A".$cstr,"Вид расхода");
                $sheet->mergeCells("A".$cstr.":A".($cstr+1));
                $sheet->setCellValue("B".$cstr,"Израсходовано (в руб.)");
                $sheet->mergeCells("B".$cstr.":B".($cstr+1));
                $sheet->mergeCells("C".$cstr.":F".$cstr);
                $sheet->setCellValue("C".$cstr,"Документ, подтверждающий расход");
                $sheet->mergeCells("G".$cstr.":I".$cstr);
                $sheet->setCellValue("G".$cstr,"Документ, подтверждающий оплату");
                $cstr++;
                $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue("C".$cstr,"Наименование");
                $sheet->setCellValue("D".$cstr,"Контрагент");
                $sheet->setCellValue("E".$cstr,"Дата");
                $sheet->setCellValue("F".$cstr,"Номер");
                $sheet->setCellValue("G".$cstr,"Наименование");
                $sheet->setCellValue("H".$cstr,"Дата");
                $sheet->setCellValue("I".$cstr,"Номер");}
            $cstr++;
            $vozmsum = 0;
            $vozmkol = 0;
            //echo $dtsost."<br>";
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
            {$sdtsost = " and s.f_dttmcr<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}

            $letter = "";
            $letter2 = "";
            if ($old_new_flag > 0)
            {
                $letter = " vssi.";
                $letter2 = " s.";
            }

//            $sql1 = "select
//                     get_rrsum(s.f_id,0) rrsum,
//                     (select f_name from veda_spr where f_type=4 and f_num=h.f_val) hvaln,
//                     (select f_curs from veda_acchist_docs where f_acchistid=h.f_id and f_doctype=3 and f_docid=s.f_id limit 1) ddcurs,
//                     ifnull((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
//                      where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id ".$and_sql.$letter.$old_new." and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
//                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') salevaldt,
//                     ifnull((select lah.f_dt1C
//                      from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
//                      where vsto.f_c1doctype=4 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid ".$and_sql.$letter.$old_new." and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
//                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') bayvaldt,
//                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
//                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0) valpaysellsum,
//                     case
//                       when t.f_c1doctype=1 and s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
//                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
//                                         where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0))>0 then #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
//                         case
//                           when DATE_FORMAT(h.f_dt1C,'%d.%m.%Y')=DATE_FORMAT((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'%d.%m.%Y') then -999999999.999
//                           else round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-getcbrate(h.f_val,(select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and  vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1))) AS DECIMAL(15,2)),2)
//                         end
//                       else -999999999.999
//                     end vozmoplinvalwsaleval,
//                     case
//                       when t.f_c1doctype=18 and s.f_bdrarticle>0 and s.f_isvozm=1 and h.f_val<>643
//                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
//                                         where vsto.f_c1doctype=1 ".$and_sql.$letter.$old_new." and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid),0))>0 then #операции возмещаемой продажи валюты с наличием связанной оплаты в валюте
//                         round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-h.f_cursoper) AS DECIMAL(15,2)),2)
//                       else -999999999.999
//                     end vozmsalevalwoplinval,
//                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
//                     (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) zdoctype, ".
//                //"  '' zdoctype,".
//                "  case ".
//                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then ".
//                "      concat((select f_name from ".DBPref."nomenk where f_id=t.f_nomenkid),'. ',ifnull(s.f_invcom,'')) ".
//                "    when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs=1 then ".
//                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
//                "      case when s.f_sum=0 then ".
//                "        ifnull((select round(cast(f_rate as decimal(15,5)),4) from ".DBPref."cbrates where f_val=s.f_val and f_dt=(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),1) else round(cast(h.f_cursoper as decimal(15,5)),4) end) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
//                "    when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs>1 then ".
//                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
//                "                round(cast(d.f_curs as decimal(15,5)),4) ) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
//                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum=0 then ".
//                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
//                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum>0 then ".
//                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
//                "    else '' ".
//                "  end dopn, ".
//                "  case ".
//                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then '' ".
//                "    when (t.f_c1doctype=4 or t.f_c1doctype=5 or t.f_c1doctype=18) then ".
//                "      (select f_name from ".DBPref."spr where f_type=27 and f_num=t.f_c1doctype)
//                       else t.f_name ".
//                "  end nop,".
//                "  s.f_sum,s.f_val,s86.f_name,1 tp,".
//                "  case ".
//                "    when t.f_zdoctype=3 then ".
//                "      dt.f_TDnum ".
//                "    when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
//                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
//                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
//                "      ),0)>0 then ".
//                "      (select f_ppnum from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
//                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
//                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
//                "      ) ".
//                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
//                "      h.f_ppnum ".
//                "    else '' ".
//                "  end ppnum,".
//                "  case ".
//                "    when t.f_zdoctype=3 then ".
//                "      dt.f_TDdt ".
//                "    when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
//                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
//                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59'".
//                "        ),0)>0 then ".
//                "      (select f_ppdt from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
//                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
//                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
//                "      ) ".
//                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
//                "      h.f_ppdt ".
//                "    else '' ".
//                "  end ppdt,".
//                "  case ".
//                "    when t.f_zdoctype=3 then 'ФТС' ".
//                //"    when t.f_zdoctype=4 then ".
//                //"      (select f_cname from ".DBPref."clients where f_id=s.f_contrid) ".
//                //"    when t.f_c1doctype=4 then ".
//                //"      (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) ".
//                "    when t.f_c1doctype in (4,5,18) then ".
//                "      (select lc.f_cname from ".DBPref."dogs ld,".DBPref."clients lc where lc.f_id=ld.f_contrid and ld.f_dogtype=10 ".
//                "      and ld.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=5 and f_valstr=h.f_orgbic1C) limit 1) ".
//                "    when t.f_c1doctype=6 then ".
//                "      (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) ".
//                "    else h.f_name ".
//                "  end contrn,".
//                "  case
//                       when t.f_c1doctype=4 and s.f_sum=0 then 0
//                       when t.f_c1doctype=4 and d.f_curs>1 and (select count(*) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."')>0 then
//                         CAST((IFNULL((select sum(f_sum) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'),0)*d.f_curs) AS DECIMAL(15,2))
//                       when t.f_c1doctype=4 and d.f_curs>1 and (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
//                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
//                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>1
//                         then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
//                       when (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
//                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
//                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>0 then
//                         (select sum(ahd.f_clssum) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
//                          where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
//                            ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)
//                       when d.f_curs>1 then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
//                       else d.f_clssum
//                     end clssum
//                     #sdid 1552
//                     ,'' na_name
//                     ,'' na_dt
//                     ,'' na_num
//                     ,1 blockid
//                     #~sdid 1552
//                   from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."acchist_docs d,".DBPref."acchist h,".DBPref."typeopers t,".DBPref."spec_invoices s
//                     left join ".DBPref."dt as dt on dt.f_specid=s.f_specid
//                   where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 ".$and_sql.$letter2.$old_new." and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and
//                     s.f_parenttype=2 and s.f_specid in (".$oprid.") and h.f_ahtype<>4 and
//                     ((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1)
//                      or (t.f_c1doctype in (4,5) and s.f_isvozm=1)
//                      or (s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
//                          and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
//                                       where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0) #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
//                      or (t.f_c1doctype=18 and s.f_isvozm=1) #возмещаемая продажа валюты
//                     ) ".$sdtsost.
//                //"  s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 ".$sdtsost.
//                "  and d.f_docid=s.f_id and d.f_doctype=3 and h.f_id=d.f_acchistid ".//and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
//                //!!!не привязанные к банковским выпискам с расходными статьями бюджета
//                "union ".
//                "select
//                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
//                     '' salevaldt,
//                     '' bayvaldt,
//                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
//                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0) valpaysellsum,
//                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
//                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,".
//                "  case ".
//                //отбираем покупки валюты, по отпущенным в долг товарам
//                "    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then '-' ".
//                "    else ".
//                "      (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) ".
//                "  end zdoctype, ".
//                //"  '' zdoctype,".
//                "  case ".
//                //отбираем покупки валюты, по закрытым в долг услугам - акты от иностранного поставщика
//                "
//                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then
//                         concat('Неоплаченная сумма за услуги ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
//                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
//                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
//                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
//                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
//                  ".
//                //отбираем покупки валюты, по отпущенным в долг товарам по дате ППС
//                "    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then ".
//                "      concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),".
//                "             ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',".
//                "             (select DATE_FORMAT(f_perpravdt,'%d.%m.%Y') from ".DBPref."specs where f_id=s.f_specid),' г. - ',".
//                "             getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),'(',".
//                "             (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') ".
//                //отбираем покупки валюты, по закрытым в долг товарам по документу - Товар от иностранного поставщика
//                "
//                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=25)>0 then
//                         concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
//                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
//                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=25 limit 1),' г. - ',
//                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
//                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
//                  ".
//                //отбираем по детализации актов
//                "    when (select count(*) from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' ".
//                "          and ad.f_operid=s.f_id )>0 then ".
//                "      (select f_grnd from ".DBPref."akts_details where f_id in (select f_akts_detailsid from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak ".
//                "        where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' and ad.f_operid=s.f_id) limit 1) ".
//                "    else concat(t.f_name,'. ',ifnull(s.f_invcom,'')) ".
//                "  end nop, ".
//                "  case ".
//                "    when t.f_c1doctype=4 and s.f_invoiceid>0 then '' ".
//                "    else '' ".
//                "  end dopn,".
//                "  s.f_sum,s.f_val,s86.f_name,0 tp,".
//                "  case when t.f_zdoctype=3 then ".
//                //"    dt.f_TDnum ".
//                "    ifnull((select f_TDnum from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') ".
//                "  else '' end ppnum,".
//                "  case when t.f_zdoctype=3 then ".
//                //"    dt.f_TDdt ".
//                "    ifnull((select f_TDdt from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') ".
//                "  else '' end ppdt,".
//                "  case when t.f_zdoctype=3 then 'ФТС' ".
//                "    when t.f_zdoctype in (4,6) then ".
//                "      concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) ".
//                "  else '' end contrn,".
//                //отбираем покупки валюты, по отпущенным в долг товарам
//                "  case ".
//                "    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then ".
//                "      cast((s.f_sum*getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid))) AS DECIMAL(15,2)) ".
//                "    else s.f_sum ".
//                "  end clssum
//                  #sdid 1552
//                  ,'' na_name
//                  ,'' na_dt
//                  ,'' na_num
//                  ,2 blockid
//                  #~sdid 1552
//                  ".
//                "from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s ".
//                //"left join ".DBPref."dt as dt on dt.f_specid=s.f_specid ".
//                //"  left join ".DBPref."dt as dt on dt.f_specid=s.f_specid ".
//                "where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and  s85.f_type=85 ".$and_sql.$letter2.$old_new." and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and ".
//                "  s.f_parenttype=2 and s.f_specid in (".$oprid.") and ".
//                //"  ((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1) or t.f_c1doctype in (1)) ".$sdtsost.
//                "    s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 ".$sdtsost.
//                "  and (select count(*) from ".DBPref."acchist_docs where f_docid=s.f_id and f_doctype=3)=0 ".
//                //sdid 1552
//                "
//                  union
//                  select
//                  ################################################
//                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
//                     '' salevaldt,
//                     '' bayvaldt,
//                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
//                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
//                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper ".$and_sql.$letter.$old_new." and  vssi.f_parentid=s.f_id),0) valpaysellsum,
//                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
//                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
//                  ################################################
//                     'Инвойс' zdoctype,
//                     #s.f_id,
//                     #nad.f_nettingagrid,
//                     (concat(t.f_name, ' (', (select f_cname from veda_clients where f_id=na.f_contrid), '), инвойс № ',
//                             ifnull((select concat (sch.f_num, ' от ', DATE_FORMAT(sch.f_dt,'%d.%m.%Y'), ' на сумму ',
//                                                                round(cast(sch.f_sum AS DECIMAL(15,2)),2), ' ',
//                     				                (select f_dopprstr from veda_spr where f_type=4 and f_num=sch.f_val))
//                     				   from veda_schets sch
//                     					where sch.f_id=s.f_invoiceid
//                     				),''),
//                     				', курс на ', DATE_FORMAT(na.f_dt,'%d.%m.%Y'), ' ', nad.f_curs2
//
//                            )
//                     ) nop,
//                  ###############################################
//                    case
//                      when t.f_c1doctype=4 and s.f_invoiceid>0 then ''
//                      else ''
//                    end dopn,
//                    s.f_sum,s.f_val,s86.f_name,0 tp,
//                  ###############################################
//                    (select sch.f_num from veda_schets sch where sch.f_id=s.f_invoiceid) ppnum,
//                    (select DATE_FORMAT(sch.f_dt,'%d.%m.%Y') from veda_schets sch where sch.f_id=s.f_invoiceid) ppdt,
//                    (select f_cname from veda_clients where f_id=na.f_contrid) contrn,
//                    case na.f_val
//                      when 643 then nad.f_sum
//                    	else round(cast(nad.f_sum*getcbrate(na.f_val, na.f_dt) AS DECIMAL(15,2)),2)
//                    end clssum,
//
//                     'Соглашение о зачете взаимных требований' na_name,
//                     DATE_FORMAT(na.f_dt,'%d.%m.%Y') na_dt,
//                     na.f_num na_num
//                     ,3 blockid
//                    from veda_spr s86,veda_spr s85,veda_typeopers t,veda_spec_invoices s, veda_netting_agr_details nad, veda_netting_agr na
//                    where t.f_id=s.f_idoper
//                    and s86.f_type=86
//                    and s85.f_type=85
//                    and s85.f_num=s86.f_uslint
//                    and s86.f_num=s.f_bdrarticle
//                    and s.f_parenttype=2
//                    and s.f_specid in (".$oprid.")
//                    and s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1
//                    and nad.f_operid2=s.f_id
//                    ".$and_sql.$letter2.$old_new."
//                    and na.f_id=nad.f_nettingagrid
//                  ";

            $sql1 = "select
                     get_rrsum(s.f_id,0) rrsum,
                     (select f_name from veda_spr where f_type=4 and f_num=h.f_val) hvaln,
                     (select f_curs from veda_acchist_docs where f_acchistid=h.f_id and f_doctype=3 and f_docid=s.f_id limit 1) ddcurs,
                     ifnull((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
                      where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') salevaldt,
                     ifnull((select lah.f_dt1C
                      from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
                      where vsto.f_c1doctype=4 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     case
                       when t.f_c1doctype=1 and s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                         where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0 then #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты~#
                         case
                           when DATE_FORMAT(h.f_dt1C,'%d.%m.%Y')=DATE_FORMAT((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'%d.%m.%Y') then -999999999.999
                           else round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-getcbrate(h.f_val,(select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and  vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1))) AS DECIMAL(15,2)),2)
                         end
                       else -999999999.999
                     end vozmoplinvalwsaleval,
                     case
                       when t.f_c1doctype=18 and s.f_bdrarticle>0 and s.f_isvozm=1 and h.f_val<>643
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                         where vsto.f_c1doctype=1 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid),0))>0 then #операции возмещаемой продажи валюты с наличием связанной оплаты в валюте~#
                         round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-h.f_cursoper) AS DECIMAL(15,2)),2)
                       else -999999999.999
                     end vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     #sdid2295~#
                     case
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         'Акт выполненных работ'
                       else
                         (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype)
                     end
                     #~sdid2295~#
                     zdoctype, ".
                //"  '' zdoctype,".
                "  case ".
                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then ".
                "      concat((select f_name from ".DBPref."nomenk where f_id=t.f_nomenkid),'. ',ifnull(s.f_invcom,'')) ".
                //sdid2295
                "
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and
                          (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                       ''
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs=1 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
                "      case when s.f_sum=0 then ".
                "        ifnull((select round(cast(f_rate as decimal(15,5)),4) from ".DBPref."cbrates where f_val=s.f_val and f_dt=(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),1) else round(cast(h.f_cursoper as decimal(15,5)),4) end) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                //~sdid2295
                "    when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs>1 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
                "                round(cast(d.f_curs as decimal(15,5)),4) ) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum=0 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum>0 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    #sdid1583~#
                     when s.f_idoper in (386,388) then
                       ifnull((select concat('(',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',
                                             i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'))
                               from ".DBPref."schets i where i.f_id=(select f_invoiceid from ".DBPref."spec_invoices where f_id=s.f_parentid)),'')
                     #~sdid1583~#
                     else '' ".
                "  end dopn, ".
                "  case ".
                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then '' ".
                //sdid2295
                "
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and
                          (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                       concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' ',
                              (select f_name from ".DBPref."spr where f_type=4 and
                                 f_num=(select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),
                              ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                              (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' г. - ',
                              getcbrate((select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),
                                        (select f_dt from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),'(',
                              (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                ".
                //~sdid2295
                "    when (t.f_c1doctype=4 or t.f_c1doctype=5 or t.f_c1doctype=18) then ".
                "      (select f_name from ".DBPref."spr where f_type=27 and f_num=t.f_c1doctype)
                       else t.f_name ".
                "  end nop,".
                "  s.f_sum,s.f_val,s86.f_name,1 tp,".
                "  case
                     #sdid2295 услуга в долг~#
                         when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                           (select la.f_num from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295~#
                ".
                "    when t.f_zdoctype=3 then 
                       #sdid3300
                       case
                         when s.f_dtid>0 then 
                           (select f_TDnum from veda_dt where f_id=s.f_dtid)
                         else dt.f_TDnum 
                       end 
                       #~sdid3300
                     when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
                "      ),0)>0 then ".
                "      (select f_ppnum from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
                "      ) ".
                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
                "      h.f_ppnum ".
                "    else '' ".
                "  end ppnum,".
                "  case 
                    #sdid2295 услуга в долг~#
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                       (select la.f_dt from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295~#
                ".
                "    when t.f_zdoctype=3 then 
                       #sdid3300
                       case
                         when s.f_dtid>0 then 
                           (select f_TDdt from veda_dt where f_id=s.f_dtid)
                         else dt.f_TDdt 
                       end 
                       #~sdid3300
                     when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59'".
                "        ),0)>0 then ".
                "      (select f_ppdt from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
                "      ) ".
                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
                "      h.f_ppdt ".
                "    else '' ".
                "  end ppdt,".
                "  case ".
                "    when t.f_zdoctype=3 then 'ФТС' ".
                //"    when t.f_zdoctype=4 then ".
                //"      (select f_cname from ".DBPref."clients where f_id=s.f_contrid) ".
                //"    when t.f_c1doctype=4 then ".
                //"      (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) ".
                "   #sdid2295 услуга в долг~#
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                       (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295~#
                     when t.f_c1doctype in (4,5,18) then ".
                "      (select lc.f_cname from ".DBPref."dogs ld,".DBPref."clients lc where lc.f_id=ld.f_contrid and ld.f_dogtype=10 ".
                "      and ld.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=5 and f_valstr=h.f_orgbic1C) limit 1) ".
                "    when t.f_c1doctype=6 then ".
                "      (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) ".
                "    else h.f_name ".
                "  end contrn,".
                "  case
                       #sdid2295 услуга в долг~#
                         when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                            (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                        #~sdid2295~#
                       when t.f_c1doctype=4 and s.f_sum=0 then 0
                       when t.f_c1doctype=4 and d.f_curs>1 and (select count(*) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."')>0 then
                         CAST((IFNULL((select sum(f_sum) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'),0)*d.f_curs) AS DECIMAL(15,2))
                       when t.f_c1doctype=4 and d.f_curs>1 and (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>1
                         then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
                       when (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>0 then
                         (select sum(ahd.f_clssum) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                          where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                            ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)
                       when d.f_curs>1 then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
                       else d.f_clssum
                     end clssum
                     ,-1 korrazn
                     ,'' na_name
                     ,'' na_dt
                     ,'' na_num
                     ,1 blockid
                     #sdid 1583~#
 		             ,s.f_idoper
                     ,'' nop1
                     ,'' nop2
                     ,'' sumnop1
                     ,'' sumnop2
                     ,ifnull(s.f_com,'') sicom
		     #~sdid 1583~#
                   from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."acchist_docs d,".DBPref."acchist h,".DBPref."typeopers t,".DBPref."spec_invoices s
                     left join ".DBPref."dt as dt on dt.f_specid=s.f_specid
                   where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and
                     s.f_parenttype=2 and s.f_id in (".$opers.") and h.f_ahtype<>4 and
                     #sdid2442
                     s.f_idoper<>387 and #исключаем Товар. В ДОЛГ. Оплата~#
                     #~sdid2442~#
                     ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) and #нет услуги в долг~#
                     #sdid1583 - нет порожденных операций - Курсовая разница при переводе~#
                     (select count(*) from ".DBPref."spec_invoices
                      where f_idoper=388 and
                        (f_parentid=(select f_id from veda_spec_invoices where f_parentid=s.f_parentid and f_idoper=54 limit 1) or
                         f_parentid=(select f_id from veda_spec_invoices where f_parentid=s.f_id and f_idoper=54 limit 1)))=0 and
                     #~sdid1583~#
                     ((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1)
                      #sdid2295
                      #or (t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0) #услуга в долг
                      #~sdid2295~#
                      or (t.f_c1doctype in (4,5) and s.f_isvozm=1)
                      or (s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
                          and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                       where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0) #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты~#
                      or (t.f_c1doctype=18 and s.f_isvozm=1) #возмещаемая продажа валюты~#
                     ) ".$sdtsost.
                //"  s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 ".$sdtsost.
                "  and d.f_docid=s.f_id and d.f_doctype=3 and h.f_id=d.f_acchistid ".
                //sdid2295 Услуга в долг
                "
                 union
                 select
                   get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                   '' salevaldt,
                   '' bayvaldt,
                   ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                          where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                   ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                          where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                   -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                   s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                   case
                     when s.f_idoper=389 then 'Акт выполненных работ'
                     else ''
                   end zdoctype,
                   case
                     when s.f_idoper=389 then
                       concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' ',
                              (select f_name from ".DBPref."spr where f_type=4 and
                                 f_num=(select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),
                              ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                              (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                              getcbrate((select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),
                                        (select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                              (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                     else ''
                   end nop,
                   case
                     when s.f_idoper=389 then ''
                     else ''
                   end dopn,
                   s.f_sum,s.f_val,s86.f_name,0 tp,
                   case
                     when s.f_idoper=389 then
                       (select la.f_num from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end ppnum,
                   case
                     when s.f_idoper=389 then
                       (select la.f_dt from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end ppdt,
                   case
                     when s.f_idoper=389 then
                       (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end contrn,
                   case
                     when s.f_idoper=389 then
                       (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else 0
                   end clssum
                   ,-1 korrazn
                   ,'' na_name
                   ,'' na_dt
                   ,'' na_num
                   ,4 blockid
                   #sdid 1583~#
 		            ,s.f_idoper
                    ,'' nop1
                    ,'' nop2
                    ,'' sumnop1
                    ,'' sumnop2
                    ,ifnull(s.f_com,'') sicom
	                #~sdid 1583~#
                 from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s
                 where t.f_id=s.f_idoper
                   and s86.f_type=86
                   and s85.f_type=85
                   and s85.f_dopprint=2
                   and s85.f_num=s86.f_uslint
                   and s86.f_num=s.f_bdrarticle
                   and s.f_parenttype=2
                   and s.f_specid in (".$oprid.") and s.f_id in (".$opers.")
                   and s.f_bdrarticle>0
                   and s.f_idoper=389
                ".
                //~sdid2295
                //and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
                //!!!не привязанные к банковским выпискам с расходными статьями бюджета
                "union 
                 select
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                   s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                   case 
                   #отбираем покупки валюты, по отпущенным в долг товарам
                   when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then '-' 
                     else 
                       (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) 
                   end zdoctype, 
                   case  
                     #отбираем покупки валюты, по закрытым в долг услугам - акты от иностранного поставщика
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then
                         concat('Неоплаченная сумма за услуги ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                     #отбираем покупки валюты, по отпущенным в долг товарам по дате ППС
                     when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then 
                       concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                              ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
                              (select DATE_FORMAT(f_perpravdt,'%d.%m.%Y') from ".DBPref."specs where f_id=s.f_specid),' г. - ',
                              getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),'(',
                              (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') 
                     #отбираем покупки валюты, по закрытым в долг товарам по документу - Товар от иностранного поставщика
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=25)>0 then
                         concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=25 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                     #отбираем по детализации актов
                     when (select count(*) from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' 
                             and ad.f_operid=s.f_id )>0 then 
                       (select f_grnd from ".DBPref."akts_details where f_id in (select f_akts_detailsid from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak 
                        where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' and ad.f_operid=s.f_id) limit 1) 
                     else concat(t.f_name,'. ',ifnull(s.f_invcom,'')) 
                   end nop, 
                   case
                     #sdid1583~#
                     when s.f_idoper in (386,388) then
                       ifnull((select concat('(',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',
                                             i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),')')
                               from ".DBPref."schets i where i.f_id=(select f_invoiceid from ".DBPref."spec_invoices where f_id=s.f_parentid)),'')
                     #~sdid1583~#
                     when t.f_c1doctype=4 and s.f_invoiceid>0 then '' 
                     else '' 
                   end dopn,
                   s.f_sum,s.f_val,s86.f_name,0 tp,
                   case when t.f_zdoctype=3 then 
                     #sdid3300
                     case
                       when s.f_dtid>0 then 
                         (select f_TDnum from veda_dt where f_id=s.f_dtid)
                       else ifnull((select f_TDnum from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                     end 
                     #~sdid3300
                     #sdid1583
                     when s.f_idoper in (386,388) then
                       ifnull((select lah.f_ppnum from ".DBPref."acchist lah,".DBPref."acchist_docs lahd,".DBPref."spec_invoices lsi
                               where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_id=s.f_parentid limit 1),'')
                     #~sdid1583
                   else '' end ppnum,
                   case when t.f_zdoctype=3 then 
                     #sdid3300
                     case
                       when s.f_dtid>0 then 
                         (select f_TDdt from veda_dt where f_id=s.f_dtid)
                       else ifnull((select f_TDdt from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                     end 
                     #~sdid3300
                     #sdid1583
                     when s.f_idoper in (386,388) then
                       ifnull((select DATE_FORMAT(lah.f_ppdt,'%d.%m.%Y') from ".DBPref."acchist lah,".DBPref."acchist_docs lahd,".DBPref."spec_invoices lsi
                               where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_id=s.f_parentid limit 1),'')
                     #~sdid1583
                     else '' 
                   end ppdt,
                   case when t.f_zdoctype=3 then 'ФТС' 
                     when t.f_zdoctype in (4,6) then 
                       concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) 
                   else '' end contrn,
                   #отбираем покупки валюты, по отпущенным в долг товарам
                   case
                     when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then
                         cast((s.f_sum*getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1))) AS DECIMAL(15,2))
                     when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then 
                       cast((s.f_sum*getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid))) AS DECIMAL(15,2)) 
                     else s.f_sum 
                   end clssum
                  ,(ifnull((select sum(nd.f_sum) from veda_netting_agr_details nd where nd.f_operid2=s.f_id),0)-s.f_sum) korrazn
                  ,'' na_name
                  ,'' na_dt
                  ,'' na_num
                  ,2 blockid
                  #sdid 1583~#
 		  ,s.f_idoper,
		  case
                     #Курсовая разница при приобретении валюты для расчетов с поставщиком товара~#
                    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
		         and (select ifnull(si0.f_invoiceid,0)
			        from veda_spec_invoices si1, veda_spec_invoices si0
			       where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
                         and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
		      then
			 (select concat('Курсовая разница при приобретении валюты для расчетов с поставщиком товара ',
                                        #s.f_id,'|',si0.f_idoper,'|',si1.f_idoper,'|',si0.f_id,'|',si1.f_id,'|-|',
			                s.f_sum, ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
			                ' курс покупки валюты ',
			                case 
			                  when si0.f_idoper=387 then
			                    (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si0.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                  when si1.f_idoper=387 then
			                    (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si1.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                  else s.f_cursoper
			                end,
			                ' курс ЦБ на дату покупки валюты ',
					(getcbrate(s.f_val, s.f_dttmcr)),
					' Расчет курсовой разницы: (',
					#s.f_cursoper,
			                case 
			                  when si0.f_idoper=387 then
			                    (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si0.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                  when si1.f_idoper=387 then
			                    (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si1.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                  else s.f_cursoper
			                end,
					' - ',
					(getcbrate(s.f_val, s.f_dttmcr)),
					') * ',
					s.f_sum,
					' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					' = ',
					#ROUND(CAST(((s.f_cursoper-getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2),
                                        ROUND(CAST(((
                                          #s.f_cursoper
                                          case 
			                    when si0.f_idoper=387 then
			                      (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si0.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                    when si1.f_idoper=387 then
			                      (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si1.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			                    else s.f_cursoper
			                  end
                                          -getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2),
					' руб. (',
					cl2.f_cname, ' инвойс № ', i.f_num, ' от ', DATE_FORMAT(i.f_dt,'%d.%m.%Y'),
					')'
			               )
			    from veda_schets i, veda_spec_invoices si1, veda_spec_invoices si0, veda_dogs d2, veda_clients cl2
				 where i.f_id=si0.f_invoiceid
				   and si1.f_id=s.f_parentid
				   and si0.f_id=si1.f_parentid
				   and d2.f_id=i.f_dogid
				   and cl2.f_id=d2.f_contrid
			 )
		      else ''
		    end nop1,
                    case
                     #Курсовая разница полученная в следствии изменения курса ЦБ РФ с даты приобретения валюты на дату осуществления платежа~#
                        when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
		             and (select ifnull(si0.f_invoiceid,0)
		                    from veda_spec_invoices si1, veda_spec_invoices si0
			           where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
			  	     and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
                        then
			 (select ifnull(concat('Курсовая разница полученная в следствии изменения курса ЦБ РФ',
		                               ' с даты приобретения валюты на дату осуществления платежа ',
					       s.f_sum, ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					       ' курс ЦБ на дату покупки валюты ',
					       getcbrate(s.f_val, s.f_dttmcr),
					       ' курс ЦБ на дату осуществления платежа ',
					       getcbrate(sis.f_val,sis.f_dttmcr),
					       ' Расчет курсовой разницы: (',
					       getcbrate(s.f_val, s.f_dttmcr),
					       ' - ',
					       getcbrate(sis.f_val,sis.f_dttmcr),
					       ') * ',
					       s.f_sum,
					       ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					       ' = ',
					       ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2),
					      ' руб.'
					      ),'')
			    from veda_spec_invoices si1, veda_spec_invoices si0, veda_spec_invoices sis
				 where si1.f_id=s.f_parentid
					 and si0.f_id=si1.f_parentid
					 and si1.f_idoper in (385,387)
					 and sis.f_parentid=s.f_parentid
					 and sis.f_dttmcr>s.f_dttmcr
					 and sis.f_sum=s.f_sum
					 and sis.f_idoper=54
					 and ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)<>0 LIMIT 1)
                      else ''
                    end nop2,
		    case
                    #Сумма Курсовой разницы при приобретении валюты для расчетов с поставщиком товара~#
                      when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
			   and (select ifnull(si0.f_invoiceid,0)
			          from veda_spec_invoices si1, veda_spec_invoices si0
				 where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
                           and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
			then
			  #ROUND(CAST(((s.f_cursoper-getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2)
                          ROUND(CAST(((
                            #s.f_cursoper
                            (select
                               case 
			         when si0.f_idoper=387 then
			           (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si0.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			         when si1.f_idoper=387 then
			           (select lah.f_cursoper from veda_acchist lah,veda_acchist_docs lahd where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=si1.f_id and lah.f_val<>643 and lah.f_ahtype=3 LIMIT 1)
			         else s.f_cursoper
			       end
                             from veda_spec_invoices si1, veda_spec_invoices si0
				 where si1.f_id=s.f_parentid
				   and si0.f_id=si1.f_parentid)
                            -getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2)
		        else ''
		    end sumnop1,
                    case
                      # Сумма курсовй разницы полученная в следствии изменения курса ЦБ РФ с даты приобретения валюты на дату осуществления платежа~#
                      when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
                           and (select ifnull(si0.f_invoiceid,0)
	                          from veda_spec_invoices si1, veda_spec_invoices si0
	                         where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
		           and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
                        then
                          (select ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)
		            from veda_spec_invoices si1, veda_spec_invoices si0, veda_spec_invoices sis
			   where si1.f_id=s.f_parentid
                             and si0.f_id=si1.f_parentid
                             and si1.f_idoper in (385,387)
                             and sis.f_parentid=s.f_parentid
                             and sis.f_dttmcr>s.f_dttmcr
                             and sis.f_sum=s.f_sum
                             and sis.f_idoper=54
                             and ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)<>0 LIMIT 1)

                        else ''
                    end sumnop2
                   ,ifnull(s.f_com,'') sicom
		#~sdid 1583~#
                 from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s 
                 where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 ".$and_sql.$letter2.$old_new." and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and 
                   s.f_parenttype=2 and s.f_id  IN (".$opers.")  and 
                   s.f_bdrarticle>=0 and s85.f_dopprint=2 and s.f_isvozm=1 
                 #~sdid 1583 2023-12-18
                   and (select count(*) from ".DBPref."acchist_docs where f_docid=s.f_id and f_doctype=3)=0 
                   #sdid2295~#
                   and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг
                   #~sdid2295~#
                ".
                //sdid 1552
                "
                  union
                  select
                  ################################################~#
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and  vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                  ################################################~#
                     'Инвойс' zdoctype,
                     #s.f_id,~#
                     #nad.f_nettingagrid,~#
                     (concat(t.f_name, ' (', (select f_cname from veda_clients where f_id=na.f_contrid), '), инвойс № ',
                             ifnull((select concat (sch.f_num, ' от ', DATE_FORMAT(sch.f_dt,'%d.%m.%Y'), ' на сумму ',
                                                                round(cast(sch.f_sum AS DECIMAL(15,2)),2), ' ',
                     				                (select f_dopprstr from veda_spr where f_type=4 and f_num=sch.f_val))
                     				   from veda_schets sch
                     					where sch.f_id=s.f_invoiceid
                     				),''),
                                                ', курс ', case when nad.f_curs2=1 then getcbrate(s.f_val,na.f_dt) else nad.f_curs2 end,' на ', DATE_FORMAT(na.f_dt,'%d.%m.%Y')

                            )
                     ) nop,
                     case
                       #sdid1583~#
                       when s.f_idoper in (386,388) then
                         ifnull((select concat('(',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',
                                               i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'))
                                 from ".DBPref."schets i where i.f_id=(select f_invoiceid from ".DBPref."spec_invoices where f_id=s.f_parentid)),'')
                       #~sdid1583~#
                      when t.f_c1doctype=4 and s.f_invoiceid>0 then ''
                      else ''
                    end dopn,
                    s.f_sum,s.f_val,s86.f_name,0 tp,
                  ###############################################~#
                    (select sch.f_num from veda_schets sch where sch.f_id=s.f_invoiceid) ppnum,
                    (select DATE_FORMAT(sch.f_dt,'%d.%m.%Y') from veda_schets sch where sch.f_id=s.f_invoiceid) ppdt,
                    (select f_cname from veda_clients where f_id=na.f_contrid) contrn,
                    case na.f_val
                      when 643 then nad.f_sum
                    	else round(cast(nad.f_sum*getcbrate(na.f_val, na.f_dt) AS DECIMAL(15,2)),2)
                    end clssum
                    ,-1 korrazn
                    ,'Соглашение о зачете взаимных требований' na_name
                    ,DATE_FORMAT(na.f_dt,'%d.%m.%Y') na_dt
                    ,na.f_num na_num
                    ,3 blockid
                    #sdid 1583~#
 		    ,s.f_idoper
                    ,'' nop1
                    ,'' nop2
                    ,'' sumnop1
                    ,'' sumnop2
                    ,ifnull(s.f_com,'') sicom
	            #~sdid 1583~#
                    from veda_spr s86,veda_spr s85,veda_typeopers t,veda_spec_invoices s, veda_netting_agr_details nad, veda_netting_agr na
                    where t.f_id=s.f_idoper
                    and s86.f_type=86
                    and s85.f_type=85
                    and s85.f_num=s86.f_uslint
                    and s86.f_num=s.f_bdrarticle
                    and s.f_parenttype=2
                    AND s.f_id  IN (".$opers.")
                    and s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1
                    #sdid2295~#
                    and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг
                    #~sdid2295~#
                    and nad.f_operid2=s.f_id
                    and na.f_id=nad.f_nettingagrid
                #sdid1583 - собираем курсовую ращницу по покупке валюты для операции - Товар. В ДОЛГ. Оплата~#
                union
                select
                  get_rrsum(s.f_id,0) rrsum,'' hvaln,ahd.f_curs ddcurs,
                  '' salevaldt,
                  '' bayvaldt,
                  0 valpaysellsumoper,
                  0 valpaysellsum,
                  -999999999.999 vozmoplinvalwsaleval,
                  -999999999.999 vozmsalevalwoplinval,
                  s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                  'Мемориальный ордер' zdoctype,
                  concat('Курсовая разница при приобретении валюты для расчетов с поставщиком товара ',round(ahd.f_clssum,2),' ',
                         (select f_uslstr from veda_spr where f_type=4 and f_num=ah.f_val),'\nКурс покупки валюты ',ah.f_cursoper,
                         ',\nКурс ЦБ на дату покупки ',getcbrate(ah.f_val, ah.f_ppdt),'\nРасчет курсовой разницы: ',
                         round(ahd.f_clssum,2),'*(',ah.f_cursoper,'-',getcbrate(ah.f_val, ah.f_ppdt),')=',round(ahd.f_clssum*(ah.f_cursoper-getcbrate(ah.f_val, ah.f_ppdt)),2),' руб.\n',
                         (select f_cname from veda_clients where f_id=s.f_contrid),' инвойс № ',ifnull((select concat(trim(f_num),' от ',
                         DATE_FORMAT(f_dt,'%d.%m.%Y')) from veda_schets where f_id=s.f_invoiceid),'')) nop,
                  '' dopn,
                  s.f_sum,s.f_val,s86.f_name,0 tp,
                  ah.f_ppnum ppnum,
                  ah.f_ppdt ppdt,
                  (select f_bankname from ".DBPref."banks where f_bic=ah.f_contrbic1C limit 1) contrn,
                  round(ahd.f_clssum*(ah.f_cursoper-getcbrate(ah.f_val, ah.f_ppdt)),2) clssum
                  ,-1 korrazn
                  ,'' na_name
                  ,case
                     when ifnull((select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1),0)>0 then
                       ifnull((select DATE_FORMAT(lah.f_ppdt,'%d.%m.%Y') from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                               where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and
                                 (lsi.f_id=s.f_id or lsi.f_parentid=s.f_parentid or lsi.f_parentid=s.f_id) and lsi.f_idoper=54 limit 1),'')
                     else '' end na_dt
                  ,case
                     when ifnull((select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1),0)>0 then
                       ifnull((select lah.f_ppnum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                               where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and
                                 (lsi.f_id=s.f_id or lsi.f_parentid=s.f_parentid or lsi.f_parentid=s.f_id) and lsi.f_idoper=54 limit 1),'')
                     else '' end na_num
                  ,4 blockid
 		  ,s.f_idoper
                  ,'' nop1
                  ,case
                     when ifnull((select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1),0)>0 then
                       concat('Курсовая разница полученная в следствии изменения курса ЦБ РФ с даты приобретения валюты на дату осуществления платежа.\nКурс ЦБ на дату покупки валюты ',
                              getcbrate(ah.f_val,ah.f_ppdt),'\nКурс ЦБ на дату оплаты ',
                              getcbrate(ah.f_val,(select lah.f_ppdt from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1)),
                              '\nРасчет курсовой разницы: (',getcbrate(ah.f_val,ah.f_ppdt),'-',
                              getcbrate(ah.f_val,(select lah.f_ppdt from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1)),')*',
                              round(ifnull((select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                            where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                              lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1),0),2),'=',
                                              round(((getcbrate(ah.f_val,ah.f_ppdt)-
                                                      getcbrate(ah.f_val,(select lah.f_ppdt from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                                                          where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and
                                                                            lsi.f_parentid=s.f_id and lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and
                                                                            lah.f_ppdt>ah.f_ppdt limit 1)))*
                                                     (select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                                      where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and
                                                        lsi.f_parentid=s.f_id and lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1)),2))
                     else ''
                   end  nop2
                  ,'' sumnop1
                  ,case
                     when ifnull((select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                  where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                    lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1),0)>0 then
                       round(((getcbrate(ah.f_val,ah.f_ppdt)-
                               getcbrate(ah.f_val,(select lah.f_ppdt from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                                                   where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and
                                                     lsi.f_sum=s.f_sum and lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1)))*
                              (select lahd.f_clssum from veda_spec_invoices lsi,veda_acchist lah,veda_acchist_docs lahd
                               where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lsi.f_parentid=s.f_id and lsi.f_sum=s.f_sum and
                                 lsi.f_val=s.f_val and lah.f_ppdt>ah.f_ppdt limit 1)),2)
                     else ''
                   end  sumnop2
                  ,ifnull(s.f_com,'') sicom
                from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s,".DBPref."acchist ah,".DBPref."acchist_docs ahd
                where t.f_id=s.f_idoper
                  and s86.f_type=86
                  and s85.f_type=85
                  and s85.f_num=s86.f_uslint
                  and s86.f_num=s.f_bdrarticle
                  and s.f_parenttype=2
                  AND s.f_id  IN (".$opers.")
                  and s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 
                  #sdid2295~#
                  and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг
                  #~sdid2295~#
                  and (select count(*) from ".DBPref."spec_invoices
                   where f_idoper=388 and
                     (f_parentid=(select f_id from ".DBPref."spec_invoices where f_parentid=s.f_parentid and f_idoper=54 limit 1) or
                      f_parentid=(select f_id from ".DBPref."spec_invoices where f_parentid=s.f_id and f_idoper=54 limit 1)))>0
                  and ahd.f_doctype=3 and ahd.f_docid=s.f_id and ah.f_id=ahd.f_acchistid and ah.f_ahtype=3
                #~sdid1583~#
                  ";
            //~sdid 1552
            //echo $sql1."<br>";
            //if($_SESSION['loginid']==2)
            //  {echo $sql1."<br>";}
            $res1 = $this->dbh->query($sql1);
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
                $rrvalsum   = 0;
                $wdoprow    = 0;
                $needaddrow = 1;
                $nop = $row1['nop'].$row1['dopn'];
                $clssum = number_format($row1['clssum'],2, ',', ' ');
                //sdid1552
                /*if(($row1['f_idoper']==385)||($row1['f_idoper']==386))
                  {
                  //$sheet->setCellValue("B"."15",$row1['f_idoper']);
                  $sheet->setCellValue("B"."15",$sdtsost);
                  //            {$sdtsost = " and s.f_dttmcr<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
                  }*/
                //sdid 1583
                if(($row1['f_idoper']==386)or($row1['f_idoper']==388))
                {
                    $nop = "Курсовая разница при переоценке задолженности за товар ".$row1['sicom']." ".$row1['nop'];
                }
                //~sdid 1583
                if(($row1['blockid']==2)&&($row1['korrazn']==0))
                {$needaddrow = 0;$clssum = 0;}
                //sdid 1583
                //elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']==-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']==-999999999.999)&&($row1['f_idoper']!=386)&&($row1['f_idoper']!=388))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                    //~sdid 1583
                    //~sdid1552
                {$needaddrow = 0;$clssum = 0;}
                elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                    $clssum = number_format($row1['vozmoplinvalwsaleval'],2, ',', ' ');
                    $ccbrate = getcbrate($row1['f_val'],$row1['salevaldt']);
                    $nop = "Курсовая разница при переоценке валюты принципала на расчетном счете ".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                        "курс ЦБ на ".format_dt($row1['ppdt'],0,1)." - ".number_format($row1['ddcurs'],4, ',', ' ')." руб.\n".
                        "курс ЦБ на дату продажи валюты ".format_dt($row1['salevaldt'],0,1)." - ".$ccbrate." руб.\n".
                        "Расчет курсовой разницы: \n(".number_format($row1['ddcurs'],4, ',', ' ')."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".number_format($row1['vozmoplinvalwsaleval'],2, ',', ' ')."р.";
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['f_val']<>643)&&($row1['valpaysellsumoper']>0)&&
                    ($row1['valpaysellsum']!=0)&&($row1['vozmoplinvalwsaleval']==-999999999.999))#операции покупки валюты не в день продажи
                {
                    $ccbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                    $clssum  = number_format(($row1['ddcurs']-$ccbrate)*$row1['f_sum'],2, ',', ' ');
                    $nop = "Курсовая разница при приобретении валюты\n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                        "курс покупки валюты ".number_format($row1['ddcurs'],4, ',', ' ')." руб.\n".
                        "курс ЦБ на дату покупки валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                        "Расчет курсовой разницы: \n(".number_format($row1['ddcurs'],4, ',', ' ')."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $clssum."р.";
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['clssum']==0)&&($row1['f_invoiceid']>0)&&
                    ($row1['valpaysellsumoper']<$row1['f_sum']))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше суммы операции
                {
                    if($row1['f_parenttype']==2)
                    {
                        $sql2 = "select f_perpravdt from ".DBPref."specs where f_id=".$row1['f_specid'];
                        //echo $sql2."<br>";
                        $res2 = $this->dbh->query($sql2);
                        if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                            $cbdt = $row2['f_perpravdt'];
                            if(strcmp($cbdt,"0000-00-00")==0){}
                            else
                            {
                                $sql2 = "select f_sum,f_val from ".DBPref."schets where f_id=".$row1['f_invoiceid'];
                                $res2 = $this->dbh->query($sql2);
                                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                                {
                                    $row1['clssum']=round(($row2['f_sum']*getCBRate($row2['f_val'],$cbdt)),2);
                                    $clssum = number_format($row1['clssum'],2, ',', ' ');
                                }
                            }
                        }
                    }
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['f_invoiceid']>0)&&
                    ($row1['valpaysellsumoper']>=$row1['f_sum']))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше = сумме операции
                {$needaddrow = 0;$clssum = 0;}
                elseif(($row1['f_c1doctype']==4)&&($row1['clssum']>0)&&($row1['f_invoiceid']>0))
                {$clssum = number_format($row1['clssum'],2, ',', ' ');}
                elseif($row1['f_c1doctype']==5)
                {$clssum = "";$row1['clssum']=0;}
                //elseif(($row1['f_c1doctype']==18)&&($row1['rrsum']>0))//продажа валюты
                //  {$clssum = number_format($row1['rrsum'],2, ',', ' ');}
                elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                    $clssum = number_format($row1['vozmsalevalwoplinval'],2, ',', ' ');
                    $ccbrate = number_format(getcbrate($row1['f_val'],$row1['ppdt']),4, ',', ' ');
                    $nop = "Курсовая разница при продаже валюты принципала ".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                        "курс продажи валюты ".number_format($row1['f_cursoper'],4, ',', ' ')." руб.;\n".
                        "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                        "Расчет курсовой разницы: \n(".$ccbrate."-".number_format($row1['f_cursoper'],4, ',', ' ').")*".number_format($row1['f_sum'],2, ',', ' ')."=".number_format($row1['vozmsalevalwoplinval'],2, ',', ' ')."р.";
                }
                elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']==-999999999.999)&&(strlen($row1['bayvaldt'])>0))#операции продажи валюты не в день покупки
                {
                    $row1['f_kindzdoc'] = 2;
                    $wdoprow = 1;
                    //echo substr($row1['bayvaldt'],0,10)."<br>";
                    $vbbrate = getcbrate($row1['f_val'],substr($row1['bayvaldt'],0,10));
                    //echo $vbbrate."<br>";
                    $cbbrate = number_format($vbbrate,4, ',', ' ');
                    //echo $cbbrate."<br>";
                    //$clssum = number_format($row1['vozmsalevalwoplinval'],2, ',', ' ');
                    $vcbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                    $ccbrate = number_format($vcbrate,4, ',', ' ');
                    $clssum  = number_format(($vcbrate-$row1['ddcurs'])*$row1['f_sum'],2, ',', ' ');
                    $rrvalsum = round(($vbbrate-$vcbrate)*$row1['f_sum'],2);
                    $rvalsum = number_format($rrvalsum,2, ',', ' ');
                    $nopd = "Курсовая разница при переоценке валюты \n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                        "курс ЦБ на дату покупки валюты ".format_dt($row1['bayvaldt'],0,1)." - ".$cbbrate." руб.\n".
                        "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                        "Расчет курсовой разницы: \n(".$cbbrate."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $rvalsum."р.";
                    $nop = "Курсовая разница при продаже валюты \n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                        "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                        "курс продажи валюты ".number_format($row1['ddcurs'],4, ',', ' ')." руб.;\n".
                        "Расчет курсовой разницы: \n(".$ccbrate."-".number_format($row1['ddcurs'],4, ',', ' ').")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $clssum."р.";
                    if($needaddrow==1)
                    {
                        if($wphpword==1){
                            $sheet->setCellValue("A".$cstr,$nopd);
                            $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                            $sheet->setCellValue("B".$cstr,$rvalsum);
                            $sheet->setCellValue("C".$cstr,"-");
                            $sheet->setCellValue("D".$cstr,$row1['contrn']);
                            $sheet->setCellValue("E".$cstr,"-");
                            $sheet->setCellValue("G".$cstr,"Платежное поручение");
                            $sheet->setCellValue("H".$cstr,format_dt($row1['ppdt'],0,1));
                            $sheet->setCellValue("I".$cstr,$row1['ppnum']);
                            $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                            $sheet->getStyle("C".$cstr.":C".$cstr)->getAlignment()->setWrapText(true);
                            $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
                            $sheet->getStyle("G".$cstr.":G".$cstr)->getAlignment()->setWrapText(true);
                            // sdid 2363
                            $oa_add = [
                                'curtbl' => 305,
                                'f_agentrep' => $rep_id,
                                'f_spending' => $nopd,
                                'f_spent' => $rvalsum,
                                'f_spending_proof_name' => '-',
                                'f_spending_proof_client' => $row1['contrn'],
                                'f_spending_proof_date' => '-',
                                'f_payment_proof_name' => 'Платежное поручение',
                                'f_payment_proof_date' => format_dt($row1['ppdt'],0,1),
                                'f_payment_proof_num' => $row1['ppnum']
                                ];
                            $oa->addRowTbl($this->dbh, $oa_add, "");
                            // ~ sdid 2363
                        }
                        $cstr++;
                    }
                }
              $oa_add = ['curtbl' => 305, 'f_agentrep' => $rep_id]; // sdid 2363
              if($needaddrow==1)
                {
                if($wphpword==1)
                  {
                  $sheet->setCellValue("A".$cstr,$nop);
                  $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                  $sheet->setCellValue("B".$cstr,$clssum);
                  $sheet->setCellValue("C".$cstr,"-");
                  $sheet->setCellValue("D".$cstr,"-");
                  $sheet->setCellValue("E".$cstr,"-");
                  // sdid 2363
                  $oa_add['f_spending'] = $nop;
                  $oa_add['f_spent'] = $clssum;
                  $oa_add['f_spending_proof_name'] = '-';
                  $oa_add['f_spending_proof_client'] = '-';
                  $oa_add['f_spending_proof_date'] = '-';
                  // ~ sdid 2363
                  if($row1['f_idoper']==388)
                    {
                    $sheet->setCellValue("G".$cstr,"Валютный перевод");
                    $sheet->setCellValue("H".$cstr,$row1['ppdt']);
                    $sheet->setCellValue("I".$cstr,$row1['ppnum']);
                    // sdid 2363
                    $oa_add['f_payment_proof_name'] = 'Валютный перевод';
                    $oa_add['f_payment_proof_date'] = $row1['ppdt'];
                    $oa_add['f_payment_proof_num'] = $row1['ppnum'];
                    // ~ sdid 2363
                    }
                  else
                    {
                    $sheet->setCellValue("G".$cstr,"-");
                    $sheet->setCellValue("H".$cstr,"-");
                    $sheet->setCellValue("I".$cstr,"-");
                    // sdid 2363
                    $oa_add['f_payment_proof_name'] = '-';
                    $oa_add['f_payment_proof_date'] = '-';
                    $oa_add['f_payment_proof_num'] = '-';
                    // ~ sdid 2363
                    }
                  }
                }
              if($wdoprow==1)
                {
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    {
                    $oa_add['f_spending_proof_client'] = $row1['contrn']; // sdid 2363
                    $sheet->setCellValue("D".$cstr,$row1['contrn']);
                    }
                  }
                }
              //echo $row1['f_kindzdoc']."<br>";
              if(($row1['f_kindzdoc']==1)||($row1['f_kindzdoc']==3))
                {
                //echo $row1['contrn']."<br>";
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    // sdid 2363
                    //{$sheet->setCellValue("D".$cstr,$row1['contrn']);}
                    {
                    $oa_add['f_spending_proof_client'] = $row1['contrn'];
                    $sheet->setCellValue("D".$cstr,$row1['contrn']);
                    }
                    // ~ sdid 2363
                  }
                $zdoct = $row1['zdoctype'];
                //echo $row1['f_zdoctype']."<br>";
                //sdid1552
                if((($row1['f_zdoctype']==6)||//Закрывающий документ
                    ($row1['f_zdoctype']==4))  //Заявление на страхование
                    &&($row1['blockid']!=3)//соглашение о зачете
                  )
                //~sdid1552
                  {
                  $zD = "";$zE="";$zF="";
                  $sql = "select s.f_sum,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val) val,s.f_val,s.f_id,".
                            "  concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) clname,".
                            "  (select f_name from ".DBPref."spr where f_type=104 and f_num=s.f_tdocb) tdocb,s.f_status,s.f_num,s.f_dt ".
                            "from ".DBPref."akts s ".
                            "where (s.f_operid=".$row1['sfid']." or ".
                            "  s.f_id in (select f_aktid from ".DBPref."akts_details where f_id in ".
                            "  (select f_akts_detailsid from ".DBPref."akts_details_opers where f_operid=".$row1['sfid']."))) and s.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'";
                  //echo $sql."<br>";
                  $zD = "";$zE="";$zF="";
                  $res2 = $this->dbh->query($sql);
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $zdoct = $row2['tdocb'];
                    if(strlen($zD)>0){$zD=$zD.";";}$zD=$zD.$row2['clname'];
                    if(strlen($zE)>0){$zE=$zE.";";}$zE=$zE.format_dt($row2['f_dt'],0,1);
                    if(strlen($zF)>0){$zF=$zF.";";}$zF=$zF.$row2['f_num'];
                    }
                  if($needaddrow==1)
                    {
                    if($wphpword==1)
                      {
                      $sheet->setCellValue("D".$cstr,$zD);
                      $sheet->setCellValue("E".$cstr,$zE);
                      $sheet->setCellValue("F".$cstr,$zF);
                      // sdid 2363
                      $oa_add['f_spending_proof_client'] = $zD;
                      $oa_add['f_spending_proof_date'] = $zE;
                      $oa_add['f_spending_proof_num'] = $zF;
                      // ~ sdid 2363
                      }
                    //echo $zD."<br>";
                    }
                  }
                //echo $zdoct."<br>";
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    {
                    $oa_add['f_spending_proof_name'] = $zdoct; // sdid 2363
                    $sheet->setCellValue("C".$cstr,$zdoct);
                            //$sheet->setCellValue("C".$cstr,123);
                            //if(strcmp($row1['zdoctype'],"Заявление на страхование")==0)
                            //  {
                            //  $sql = "select f_ensnum,f_ensdt from ".DBPref."ensures where f_objtype=2 and f_objid=".$row1['f_specid'];
                            //  $res2 = $dbh->query($sql);
                            //  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                            //    {
                            //    if(strlen($row2['f_ensdt'])>0)
                            //      {$sheet->setCellValue("E".$cstr,format_dt($row2['f_ensdt'],0,1));}
                            //    else
                            //      {$sheet->setCellValue("E".$cstr,"");}
                            //    $sheet->setCellValue("F".$cstr,$row2['f_ensnum']);
                            //    }
                            //  }
                            //else
                            //sdid1552
                            if((($row1['f_zdoctype']!=6)&&($row1['f_zdoctype']!=4))||($row1['blockid']==3))
                              //Ёsdid1552
                              {
                              if(strlen($row1['ppdt'])>0)
                                // sdid 2363
                                //{$sheet->setCellValue("E".$cstr,format_dt($row1['ppdt'],0,1));}
                                {
                                $oa_add['f_spending_proof_date'] = format_dt($row1['ppdt'],0,1);
                                $sheet->setCellValue("E".$cstr,format_dt($row1['ppdt'],0,1));
                                }
                                // ~ sdid 2363
                              else
                                //sdid1583
                                // sdid 2363
                                //{$sheet->setCellValue("E".$cstr,"-");}
                                {
                                $oa_add['f_spending_proof_date'] = '-';
                                $sheet->setCellValue("E".$cstr,"-");
                                }
                                // ~ sdid 2363
                                //~sdid1583
                              $oa_add['f_spending_proof_num'] = $row1['ppnum']; // sdid 2363
                              $sheet->setCellValue("F".$cstr,$row1['ppnum']);
                              }
                            }
                          }
                        }
                      if($needaddrow==1)
                        {
                        //echo $row1['clssum']."|$clssum<br>";
                        if($wphpword==1)
                          {
                          if(($row1['f_kindzdoc']==2)||($row1['f_kindzdoc']==3))
                            {
                            if($row1['f_c1doctype']==5)
                              {
                              $oa_add['f_spending_proof_client'] = $row1['contrn']; // sdid 2363
                              $sheet->setCellValue("D".$cstr,$row1['contrn']);
                              }
                            if(strcmp($row1['zdoctype'],"Заявление на страхование")==0)
                              {if(strlen($row1['ppnum'])>0)
                                {
                                $oa_add['f_payment_proof_name'] = 'Платежное поручение'; // sdid 2363
                                $sheet->setCellValue("G".$cstr,"Платежное поручение");
                                }}
                              else
                                {
                                $oa_add['f_payment_proof_name'] = $row1['zdoctype']; // sdid 2363
                                $sheet->setCellValue("G".$cstr,$row1['zdoctype']);
                                }
                              if(strlen($row1['ppdt'])>0)
                                {
                                $oa_add['f_payment_proof_date'] = format_dt($row1['ppdt'],0,1); // sdid 2363
                                $sheet->setCellValue("H".$cstr,format_dt($row1['ppdt'],0,1));
                                }
                              else
                                {
                                $oa_add['f_payment_proof_date'] = ""; // sdid 2363
                                $sheet->setCellValue("H".$cstr,"");
                                }
                              $oa_add['f_payment_proof_num'] = $row1['ppnum']; // sdid 2363
                              $sheet->setCellValue("I".$cstr,$row1['ppnum']);
                              }
                        //sdid 1552
                        if(strcmp($row1['na_name'],"Соглашение о зачете взаимных требований")==0)
                          {
                          $sheet->setCellValue("G".$cstr,$row1['na_name']);
                          $sheet->setCellValue("H".$cstr,format_dt($row1['na_dt'],0,1));
                          $sheet->setCellValue("I".$cstr,$row1['na_num']);
                          // sdid 2363
                          $oa_add['f_payment_proof_name'] = $row1['na_name'];
                          $oa_add['f_payment_proof_date'] = format_dt($row1['na_dt'],0,1);
                          $oa_add['f_payment_proof_num'] = $row1['na_num'];
                          // ~ sdid 2363
                          }
                        //~sdid 1552
                        $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                        $sheet->getStyle("C".$cstr.":C".$cstr)->getAlignment()->setWrapText(true);
                        $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
                        $sheet->getStyle("G".$cstr.":G".$cstr)->getAlignment()->setWrapText(true);}
                    //$vozmsum=$vozmsum+$row1['clssum'];
                    //echo $clssum."<br>";
                    //$klclssum = (float)number_format($clssum,2, ',', ' ');
                    $klclssum = str_replace(' ','',str_replace(',','.',$clssum));
                    //echo $klclssum."<br>";
                    $vozmsum=$vozmsum+$rrvalsum;
                    if(is_numeric($klclssum))
                    {$vozmsum=$vozmsum+$klclssum;}
                    //echo $vozmsum."<br>";
                    $cstr++;
                    $oa->addRowTbl($this->dbh, $oa_add, ""); // sdid 2363
                    $vozmkol++;
                    //sdid 1583
                    if(($row1['f_idoper']==53)&&(strlen($row1['nop1'])>0)&&($row1['f_c1doctype']==4)&&($row1['f_val']<>643))
                      {
                      if($needaddrow==1)
                        {
                        if($wphpword==1)
                          {
                          $sheet->setCellValue("A".$cstr,$row1['nop1']);
                          $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                          $sheet->setCellValue("B".$cstr,number_format($row1['sumnop1'],2, ',', ' '));
                          //$sheet->setCellValue("C".$cstr,"-");
                          //$sheet->setCellValue("D".$cstr,$row1['contrn']);
                          //$sheet->setCellValue("E".$cstr,"-");
                          //$sheet->setCellValue("G".$cstr,"Платежное поручение");
                          //$sheet->setCellValue("H".$cstr,format_dt($row1['ppdt'],0,1));
                          //$sheet->setCellValue("I".$cstr,$row1['ppnum']);
                          $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                          //$sheet->getStyle("C".$cstr.":C".$cstr)->getAlignment()->setWrapText(true);
                          //$sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
                          //$sheet->getStyle("G".$cstr.":G".$cstr)->getAlignment()->setWrapText(true);
                          }
                        if(is_numeric($row1['sumnop1']))
                          {$vozmsum=$vozmsum+$row1['sumnop1'];}
                        $oa_add = ['curtbl' => 305, 'f_agentrep' => $rep_id, 'f_spending' => $row1['nop1'], 'f_spent' => number_format($row1['sumnop1'],2, ',', ' ')]; // sdid 2363
                        $oa->addRowTbl($this->dbh, $oa_add, ""); // sdid 2363
                        $cstr++;
                        $vozmkol++;
                        }
                      }
                    if(($row1['f_idoper']==53)&&(strlen($row1['nop2'])>0)&&($row1['f_c1doctype']==4)&&($row1['f_val']<>643))
                      {
                      if($needaddrow==1)
                        {
                        if($wphpword==1)
                          {
                          $sheet->setCellValue("A".$cstr,$row1['nop2']);
                          $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                          $sheet->setCellValue("B".$cstr,number_format($row1['sumnop2'],2, ',', ' '));
                          $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                          $oa_add = ['curtbl' => 305, 'f_agentrep' => $rep_id, 'f_spending' => $row1['nop2'], 'f_spent' => number_format($row1['sumnop2'],2, ',', ' ')]; // sdid 2363
                          if($row1['blockid']==4)
                            {
                            if(strlen($row1['na_num'])>1)
                              {
                              $sheet->setCellValue("G".$cstr,"Валютный перевод");
                              $sheet->setCellValue("I".$cstr,$row1['na_num']);
                              $oa_add['f_payment_proof_name'] = 'Валютный перевод'; // sdid 2363
                              $oa_add['f_payment_proof_num'] = $row1['na_num']; // sdid 2363
                              }
                            if(strlen($row1['na_dt'])>1)
                              {
                              $oa_add['f_payment_proof_date'] = $row1['na_dt']; // sdid 2363
                              $sheet->setCellValue("H".$cstr,$row1['na_dt']);
                              }
                            }
                          $oa->addRowTbl($this->dbh, $oa_add, ""); // sdid 2363
                          }
                        if(is_numeric($row1['sumnop2']))
                          {$vozmsum=$vozmsum+$row1['sumnop2'];}
                        $cstr++;
                        $vozmkol++;
                        }
                    }
                    if(($row1['f_idoper']==386)||($row1['f_idoper']==388))
                      {
                      if($needaddrow==1)
                        {
                        if($wphpword==1)
                          {
                          //$sheet->setCellValue("A".$cstr,$nop." ".$row1['sicom']);
                          //$sheet->setCellValue("A15",$nop." ".$row1['sicom']);
                          $sql = "
                                  select #sum(sis.f_sum),
                                    sis.f_sum, sis.f_val, spr4.f_namedop valname, sis.f_dttmcr,
				          getcbrate(sis.f_val, sis.f_dttmcr) curs_send,
  		                    (getcbrate(sis.f_val,(select f_perpravdt from veda_specs where f_id=s.f_specid))) curs_pps,
				          ROUND(CAST((
				            (getcbrate(sis.f_val, sis.f_dttmcr)-
				            (getcbrate(sis.f_val,(select f_perpravdt from veda_specs where f_id=s.f_specid))))*
					     sis.f_sum) AS DECIMAL(15,3)),2) sum_delta,
				          (select concat(cl.f_cname, ' инвойс № ', i.f_num, ' от ', DATE_FORMAT(i.f_dt,'%d.%m.%Y'))
				             from veda_schets i,veda_dogs d,veda_clients cl
					    where i.f_id=si0.f_invoiceid and d.f_id=i.f_dogid and cl.f_id=d.f_contrid) invname
                                  from veda_spec_invoices sis,
	                               veda_spec_invoices s,
				             veda_spec_invoices si1,
				             veda_spec_invoices si0,
				             veda_spr spr4
                                 where sis.f_parentid=s.f_parentid
                                   and s.f_id=".$row1['sfid']."    #114876  #114872
	                           and sis.f_idoper=54
	                           and si1.f_id=s.f_parentid
	                           and si1.f_idoper in (385,387)
	                           and si0.f_id=si1.f_parentid
	                           and spr4.f_type=4 and spr4.f_num=sis.f_val
	                           and (case when (select count(f_dttmcr)
						           from veda_spec_invoices
							  where f_id<>s.f_id and f_idoper in (386,388) and f_parentid=s.f_parentid and f_dttmcr<s.f_dttmcr)>0 #is not null
						   then (select max(f_dttmcr)
							   from veda_spec_invoices
						          where f_id<>s.f_id and f_idoper in (386,388) and f_parentid=s.f_parentid and f_dttmcr<s.f_dttmcr)
						   else (select f_perpravdt from veda_specs where f_id=s.f_specid)
					       end
				             )<sis.f_dttmcr
                                   and sis.f_dttmcr <= s.f_dttmcr
                                 ";

                          $res2 = $this->dbh->query($sql);
                          while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                            {
                            $colA = "Курсовая разница при переоценке задолженности за товар ".$row2['f_sum']." ".$row2['valname']." курс ЦБ на дату оплаты ";
                            $colA = $colA.$row2['curs_send']."  курс ЦБ на дату ППС ".$row2['curs_pps']." Расчет курсовой разницы: (".$row2['curs_send']."-";
                            $colA = $colA.$row2['curs_pps']."*".$row2['f_sum']."=".$row2['sum_delta']." руб. (".$row2['invname'].")";

                            $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                            $sheet->setCellValue("A".$cstr,$colA);
                            $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                            $sheet->setCellValue("B".$cstr,number_format($row2['sum_delta'],2, ',', ' '));
                            if(is_numeric($row2['sum_delta']))
                              {$vozmsum=$vozmsum+$row2['sum_delta'];}
                            // sdid 2363
                            $oa_add = [
                                'curtbl' => 305,
                                'f_agentrep' => $rep_id,
                                'f_spending' => $colA,
                                'f_spent' => number_format($row2['sum_delta'],2, ',', ' ')
                                ];
                            $oa->addRowTbl($this->dbh, $oa_add, "");
                            // ~ sdid 2363
                            $cstr++;
                            $vozmkol++;
                            }
                          }
                        }
                    }
                    //~sdid 1583
                }
            }
            if($vozmkol>0)//пишем невозмещаемые расходы
            {
                if($wphpword==1){
                    $sheet->getStyle("A".$cstr.":B".$cstr)->getFont()->setBold(true);
                    $sheet->setCellValue("A".$cstr,"ИТОГО ".$repdop);
                    $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $sheet->setCellValue("B".$cstr,number_format($vozmsum,2, ',', ' '));
                    $f_vozm = $vozmsum; // sdid 2073
                    $sheet->setCellValue("C".$cstr,"***");
                    $sheet->setCellValue("D".$cstr,"***");
                    $sheet->setCellValue("E".$cstr,"***");
                    $sheet->setCellValue("F".$cstr,"***");
                    $sheet->setCellValue("G".$cstr,"***");
                    $sheet->setCellValue("H".$cstr,"***");
                    $sheet->setCellValue("I".$cstr,"***");
                    // sdid 2363
                    $oa_add = [
                        'curtbl' => 305,
                        'f_agentrep' => $rep_id,
                        'f_spending' => "ИТОГО ".$repdop,
                        'f_spent' => number_format($vozmsum,2, ',', ' '),
                        'f_spending_proof_name' => "***",
                        'f_spending_proof_client' => "***",
                        'f_spending_proof_date' => "***",
                        'f_spending_proof_num' => "***",
                        'f_payment_proof_name' => "***",
                        'f_payment_proof_date' => "***",
                        'f_payment_proof_num' => "***",
                        'f_bold' => 1
                        ];
                    $oa->addRowTbl($this->dbh, $oa_add, "");
                    // ~ sdid 2363
                    }
                $cstr++;
            }
            $nvozmsum = 0;
            $ndss     = 0;
            //$sql1 = "select s.f_sum,".
            //        "  (select concat(f_name,'. ',ifnull(s.f_invcom,'')) from ".DBPref."typeopers where f_id=s.f_idoper) nop ".
            //        "from ".DBPref."spec_invoices s where s.f_parenttype=2 ".$isdrep." and s.f_specid in (".$oprid.") and ".
            //        "  (s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2";

            $letter = "";
            if ($old_new_flag > 0)
            {
                $letter = " s.";
            }
            $sql1 = "select s.f_sum,s.f_id,
                     concat(t.f_name,'. ',ifnull(s.f_invcom,'')) nop
                   from ".DBPref."spec_invoices s,".DBPref."typeopers t
                   where t.f_id=s.f_idoper and s.f_parenttype=2 ".$isdrep." and s.f_id  IN (".$opers.")  and
                     #sdid 637
                     #(s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298)
                     (s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298,237,285)
                     #~sdid 637";
            //echo $sql1."<br>";
            $res1 = $this->dbh->query($sql1);
            if($row['onestrdocs'] == 0)
            {
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                    $sheet->setCellValue("A".$cstr,$row1['nop']);
                    $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $sheet->setCellValue("B".$cstr,number_format($row1['f_sum'],2, ',', ' '));
                    $sheet->setCellValue("C".$cstr,"***");
                    $sheet->setCellValue("D".$cstr,"***");
                    $sheet->setCellValue("E".$cstr,"***");
                    $sheet->setCellValue("F".$cstr,"***");
                    $sheet->setCellValue("G".$cstr,"***");
                    $sheet->setCellValue("H".$cstr,"***");
                    $sheet->setCellValue("I".$cstr,"***");
                    // sdid 2363
                    $oa_add = [
                        'curtbl' => 305,
                        'f_agentrep' => $rep_id,
                        'f_spending' => $row1['nop'],
                        'f_spent' => number_format($row1['f_sum'],2, ',', ' '),
                        'f_spending_proof_name' => "***",
                        'f_spending_proof_client' => "***",
                        'f_spending_proof_date' => "***",
                        'f_spending_proof_num' => "***",
                        'f_payment_proof_name' => "***",
                        'f_payment_proof_date' => "***",
                        'f_payment_proof_num' => "***"
                        ];
                    $oa->addRowTbl($this->dbh, $oa_add, "");
                    // ~ sdid 2363
                    $nvozmsum=$nvozmsum+$row1['f_sum'];
                    //sdid3272
                    //$ndss = $ndss+round(($row1['f_sum']*20/120),2);
                    $lnds = getNDS(["dt"=>date('Y-m-d',strtotime($dtsost)),"specinvid"=>$row1['f_id']]);
                    $lnds = $lnds[0];//sdid3342
                    if($lnds>-1)
                      {
                      $sql  = "select f_uslint from ".DBPref."spr where f_type=10 and f_num=$lnds";
                      $res2 = $this->dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {$ndss = $ndss+round(($row1['f_sum']*$row2['f_uslint']/(100+$row2['f_uslint'])),2);}
                      }
                    //~sdid3272
                    $cstr++;
                }
            }
            elseif($row['onestrdocs'] == 1)
            {
                $services_names = ""; // Список наименования услуг
                $is_first_loop_passed = 0;
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                    if($is_first_loop_passed)
                    {$services_names = $services_names . "; " . $row1['nop'];}
                    else
                    {
                        $services_names = $row1['nop'];
                        $is_first_loop_passed = 1;
                    }
                    $nvozmsum += $row1['f_sum'];
                    //sdid3272
                    //$ndss = $ndss+round(($row1['f_sum']*20/120),2);
                    $lnds = getNDS(["dt"=>date('Y-m-d',strtotime($dtsost)),"specinvid"=>$row1['f_id']]);
                    $lnds = $lnds[0];//sdid3342
                    if($lnds>(-1))
                      {
                      $sql  = "select f_uslint from ".DBPref."spr where f_type=10 and f_num=$lnds";
                      $res2 = $this->dbh->query($sql);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {$ndss = $ndss+round(($row1['f_sum']*$row2['f_uslint']/(100+$row2['f_uslint'])),2);}
                      }
                    //~sdid3272
                }
                $services_names = "Вознаграждение агента ($services_names)";
                $sheet->setCellValue("A".$cstr, $services_names);
                $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue("B".$cstr,number_format($nvozmsum,2, ',', ' '));
                $sheet->setCellValue("C".$cstr,"***");
                $sheet->setCellValue("D".$cstr,"***");
                $sheet->setCellValue("E".$cstr,"***");
                $sheet->setCellValue("F".$cstr,"***");
                $sheet->setCellValue("G".$cstr,"***");
                $sheet->setCellValue("H".$cstr,"***");
                $sheet->setCellValue("I".$cstr,"***");
                // sdid 2363
                $oa_add = [
                    'curtbl' => 305,
                    'f_agentrep' => $rep_id,
                    'f_spending' => $services_names,
                    'f_spent' => number_format($nvozmsum,2, ',', ' '),
                    'f_spending_proof_name' => "***",
                    'f_spending_proof_client' => "***",
                    'f_spending_proof_date' => "***",
                    'f_spending_proof_num' => "***",
                    'f_payment_proof_name' => "***",
                    'f_payment_proof_date' => "***",
                    'f_payment_proof_num' => "***"
                    ];
                $oa->addRowTbl($this->dbh, $oa_add, "");
                // ~ sdid 2363
                $cstr++;
            }
            $sheet->getStyle("A".$cstr.":B".$cstr)->getFont()->setBold(true);
            $sheet->setCellValue("A".$cstr,"Всего выставлено ".mb_strtolower($princ)."у к оплате");
            $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("B".$cstr,number_format(($vozmsum+$nvozmsum),2, ',', ' '));
            $f_nvozm = $nvozmsum;
            $f_total = $vozmsum + $nvozmsum;
            $sheet->setCellValue("C".$cstr,"***");
            $sheet->setCellValue("D".$cstr,"***");
            $sheet->setCellValue("E".$cstr,"***");
            $sheet->setCellValue("F".$cstr,"***");
            $sheet->setCellValue("G".$cstr,"***");
            $sheet->setCellValue("H".$cstr,"***");
            $sheet->setCellValue("I".$cstr,"***");
            // sdid 2363
            $oa_add = [
                'curtbl' => 305,
                'f_agentrep' => $rep_id,
                'f_spending' => "Всего выставлено ".mb_strtolower($princ)."у к оплате",
                'f_spent' => number_format(($vozmsum+$nvozmsum),2, ',', ' '),
                'f_spending_proof_name' => "***",
                'f_spending_proof_client' => "***",
                'f_spending_proof_date' => "***",
                'f_spending_proof_num' => "***",
                'f_payment_proof_name' => "***",
                'f_payment_proof_date' => "***",
                'f_payment_proof_num' => "***",
                'f_bold' => 1
                ];
            $oa->addRowTbl($this->dbh, $oa_add, "");
            // ~ sdid 2363
            $etl = $cstr;
            $sheet->getStyle("A".$stl.":I".$etl)->applyFromArray($borderi);
            $sheet->getStyle("A".$stl.":I".$etl)->applyFromArray($bordero);
            $sheet->getStyle("A".$stl.":A".$etl)->getAlignment()->setWrapText(true);
            $sheet->getStyle("B".$stl.":B".$etl)->getAlignment()->setWrapText(true);
            $cstr++;
            $sheet->setCellValue("A".$cstr,"* Копии документов, подтверждающих суммы расходов и оплату расходов, прилагаем. Оригиналы хранятся у ".$agenta.".");
            $cstr=$cstr+2;
            $sheet->setCellValue("A".$cstr,"Отчет ".mb_strtolower($agenta)." утвержден в сумме ".number_format(($vozmsum+$nvozmsum),2, ',', ' ')." (".sum2words(($vozmsum+$nvozmsum),643).")");
            $cstr++;
            $cnds = ", без НДС";
            //sdid3272
            //if($row['osno']==1)
            //{
            //    $cnds = ", в т.ч. НДС ".number_format(($nvozmsum*20/120),2, ',', ' ');
            //    $cnds = ", в т.ч. НДС ".number_format($ndss,2, ',', ' ');
            //}
            if($ndss>0)
              {
              $cnds = ", в т.ч. НДС ".number_format($ndss,2, ',', ' ');
              }
            //~sdid3272
            $sheet->setCellValue("A".$cstr,"в том числе вознаграждение ".mb_strtolower($agenta)." в сумме ".number_format(($nvozmsum),2, ',', ' ')." (".sum2words(($nvozmsum),643).")".$cnds);
            $is_weco = $row['weco'] > 0 ? 1 : 0;
            $is_shipment_before_dt = $row['shipment_before_dt'];
            if($is_weco || $is_shipment_before_dt)
            {
                $cstr += 2;
                //$sheet->setCellValue("A".$cstr, "Примечания:");
                if($is_weco)
                {
                    $sheet->getStyle("A".$cstr)->getFont()->setItalic(true);
                    //$sheet->setCellValue("A".$cstr, "Внимание! Есть товары, включенные в Перечень товаров, упаковки товаров (утв.  Распоряжением Правительства РФ от 31.12.2020  N 3721-р), подлежащих утилизации и/или уплате экологического сбора в Роспотребнадзор.");
                    $sheet->setCellValue("A".$cstr, "Внимание! В списке импортируемых товаров содержатся товары, по которым согласно Постановлению Правительства РФ от 30.12.2024 N 1990 оплачивается экологический сбор. Обязанность по уплате экологического сбора возложена на Принципала. С подробностями о порядке, сроках и размерах оплаты Вы можете ознакомиться на сайте Росприроднадзора https://rpn.gov.ru/activity/rop/ecological-fee/.");
                    $cstr++;
                }
                if($is_shipment_before_dt)
                {
                    //sdid 3539
                    $sheet->mergeCells("A".$cstr.":J".($cstr+1));
                    $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                    $sheet->getStyle("A".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    //~sdid 3539
                    $sheet->getStyle("A".$cstr)->getFont()->setItalic(true);
                    $sheet->setCellValue("A".$cstr, "Внимание! Товар выпущен по Заявлению до подачи ДТ. Расчет таможенных платежей не является документом, подтверждающим уплату таможенных платежей. После выпуска ДТ будет предоставлен доп. отчет на сумму фактически уплаченных таможенных платежей с приложением копии ДТ.");
                    //$cstr++;  //sdid 3539
                    $cstr += 2; //sdid 3539
                }
                $cstr++;
            }
            else{$cstr += 2;}
            $sheet->setCellValue("A".$cstr,"От имени ".$agenta.":");
            $sheet->setCellValue("D".$cstr,"От имени ".$princ."а ".$row["contrname"].":");
            $cstr=$cstr+2;
            $sheet->setCellValue("A".$cstr,$opodpd);
            $sheet->setCellValue("C".$cstr,$isppodp);
            $sheet->setCellValue("D".$cstr,$cpodpd);
            $sheet->setCellValue("E".$cstr,"/");
            $sheet->setCellValue("F".$cstr,"/".$clnpodp);
            $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
            $sheet->getStyle("B".$cstr.":B".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("D".$cstr.":D".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("E".$cstr.":E".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("F".$cstr.":F".$cstr)->applyFromArray($borderb);
            $cstr=$cstr+1;
            $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D".$cstr)->getFont()->setSize(10);
            $sheet->getStyle("E".$cstr)->getFont()->setSize(10);
            $sheet->getStyle("F".$cstr)->getFont()->setSize(10);
            $sheet->setCellValue("D".$cstr,"должность");
            $sheet->setCellValue("E".$cstr,"подпись");
            $sheet->setCellValue("F".$cstr,"расшифровка подписи");
            $cstr=$cstr+1;
            $sheet->setCellValue("A".$cstr,$opodpbd);
            $sheet->setCellValue("C".$cstr,$opodpbn);
            $sheet->getStyle("B".$cstr.":B".$cstr)->applyFromArray($borderb);
            $cstr=$cstr+2;
            $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue("B".$cstr,"М.П.");
            $sheet->setCellValue("D".$cstr,"М.П.");
        }

        // sdid 1825
        if (strlen($trade_secret_path) > 0)
        {
            $trade_secret_img = imagecreatefrompng(__DIR__."/../../templates/".$trade_secret_path);
            $trade_secret_obj = new PHPExcel_Worksheet_MemoryDrawing();
            $trade_secret_obj->setName('Trade Secret Logo');
            $trade_secret_obj->setDescription('Trade Secret Logo');
            $trade_secret_obj->setImageResource($trade_secret_img);
            $trade_secret_obj->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
            $trade_secret_obj->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $trade_secret_obj->setHeight(70);
            $trade_secret_obj->setWorksheet($xls->getActiveSheet());
            $trade_secret_obj->setCoordinates('F9');
            $trade_secret_obj->setOffsetY(10);
        }
        // ~ sdid 1825

        $objWriter = new PHPExcel_Writer_Excel2007($xls);
        $fname = __DIR__ . "/../../download/agrep".$_SESSION['loginid']."_".date("His").".xlsx";
        $objWriter->save($fname);
        $file = file_get_contents($fname);
        //        $update_rep = ['curtbl' => 286, 'curidx' => $rep_id, 'f_flrep' => base64_encode($file)]; sdid 2073
        $update_rep = ['curtbl' => 286, 'curidx' => $rep_id, 'f_flrep' => base64_encode($file), 'f_vozm' => $f_vozm, 'f_nvozm' => $f_nvozm, 'f_total' => $f_total]; // sdid 2073
        $result = editRowTbl($update_rep);

        //if ($old_new_flag === 0) sdid 2109
        if($old_new_flag === 0 && $do_download) // sdid 2109
        {
            file_force_download($fname);
        }

        return $result . "; " . $rep_id;
    }
    //sdid 3496
    // istorep=1 - флаг, указывающий что данные необходимо складывать в базу istorep=0 - прост вывести отчет в Excel
    public function createAgentRepFile1($oprst, $oprid, $rep_id, $rep_num, $old_new_flag=0, $do_download=true, $istorep=1) 
      {
      error_log("\noprst = $oprst, oprid = $oprid, rep_id = $rep_id, rep_num = $rep_num, old_new_flag = $old_new_flag=0, do_download = $do_download, istorep = $istorep\n",0);
      $oa = new oa('oa', $this->dbh);
      if($rep_id>0)
        {
        $sql_ = "SELECT GROUP_CONCAT(f_id) ids FROM ".DBPref."oa WHERE f_agentrep=".$rep_id;
        $conn_ = $this->dbh->query($sql_);
        if($row_ = $conn_->fetch(PDO::FETCH_ASSOC))
          {
          $ids_ = $row_['ids'];
          if(strlen($ids_) > 0)
            {
            $sql_ = "DELETE FROM ".DBPref."oa WHERE f_id IN (".$ids_.")";
            $this->dbh->exec($sql_);
            }
          }
        }
        $f_vozm = 0;
        $f_nvozm = 0;
        $f_total = 0;
        $opers = "0";
        if($istorep==1)
          {
          $sql = "SELECT IFNULL(GROUP_CONCAT(aro.f_operid),0) opers FROM veda_agentreps_opers aro WHERE aro.f_agentrepid=".$rep_id; 
          $conn = $this->dbh->query($sql);
          if($row = $conn->fetch(PDO::FETCH_ASSOC)) {$opers = $row['opers'];}
          }
        $old_new = "";
        $and_sql = "";
        if($old_new_flag == 1)
          {
          $old_new = "f_outbuhperiod=0 ";
          $and_sql = " AND ";
          } 
        elseif($old_new_flag == 2)
          {
          $old_new = "f_outbuhperiod=1 ";
          $and_sql = " AND ";
          }
        $isdrep = "";
        $isdrep552 = ""; 
        if((($oprst==35)||($oprst==145))&& $istorep==0) {$isdrep = $oprid; $isdrep = str_replace("undefined,","",$isdrep);}
        if((($oprst==35)||($oprst==145))&& $istorep==0) {$opers = $oprid;}
        $wphpword=1;
        $dtformat = "%d.%m.%Y";
        $bordero = array(
            'borders'=>array(
                'outline' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                ),
            )
          );
        $borderi = array(
            'borders'=>array(
                'inside' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                ),
            )
          );
        $borderb = array(
            'borders'=>array(
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN		),
            )
          );
        $table_settings = array($dtformat, $bordero, $borderi, $borderb);
        $trade_secret_path = ""; // 1825
        if($wphpword==1)
          {
          PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
          $xls = new PHPExcel();
          $xls->setActiveSheetIndex(0);
          $sheet = $xls->getActiveSheet();
          $sheet->getDefaultStyle()->getFont()->setName('Times New Roman');
          $sheet->getDefaultStyle()->getFont()->setSize(12);
          //Ориентация страницы и  размер листа
          $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
          $sheet->getPageSetup()->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
          $sheet->getPageSetup()->setFitToWidth(1);
          $sheet->getPageMargins()->setRight(0.1);
          $sheet->getPageMargins()->setLeft(0.1);
          $sheet->setTitle('Отчет об исполнении - AgentReps');
          //$sheet->getColumnDimensionByColumn("A")->setAutoSize(true);
          $sheet->getColumnDimension("A")->setWidth(40);
          $sheet->getColumnDimension("B")->setWidth(15);
          $sheet->getColumnDimension("C")->setWidth(20);
          $sheet->getColumnDimension("D")->setWidth(20);
          $sheet->getColumnDimension("E")->setWidth(15);
          $sheet->getColumnDimension("F")->setWidth(30);
          $sheet->getColumnDimension("G")->setWidth(20);
          $sheet->getColumnDimension("H")->setWidth(15);
          $sheet->getColumnDimension("I")->setWidth(15);
          $sheet->getStyle("A1:A1:")->getFont()->setBold(true);
          $sheet->getStyle("A1")->getFont()->setSize(12);
          $sheet->getStyle("A2")->getFont()->setSize(12);
          $sheet->getStyle("A3")->getFont()->setSize(12);
          $sheet->getStyle("A4")->getFont()->setSize(12);
          $sheet->getStyle("A5")->getFont()->setSize(12);
          $sheet->getStyle("A6")->getFont()->setSize(12);
          $sheet->getStyle("A4:A4:")->getFont()->setBold(true);
          $sheet->getStyle("A8:I8:")->getFont()->setBold(true);
          $sheet->mergeCells("A8:I8");
          $sheet->getStyle("A8")->getFont()->setSize(12);
          $sheet->getStyle("A8")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          }
        $sioprids = "";$sioprids1 = " and s.f_id=0 ";//sdid2865
        if(($oprst==35)||($oprst==145))
          {
          //$oprid = "select distinct(f_specid) from ".DBPref."spec_invoices where f_id in (".$oprid.") ";
          $sioprids  =" and s.f_id in ($oprid) ";
          $sioprids1 = $sioprids;
          $oprid = "select distinct(f_specid) from ".DBPref."spec_invoices where f_id in (".$oprid.") ";
          }
        $sql ="select s.f_subtype ssubtype,d.f_subtype dogsubtype,d.f_dogname,d.f_dogdate,d.f_contrid,d.f_orgid,s.f_num,s.f_dt,s.f_typez, ".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_weco=2),0) weco,".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_status<>17 AND dt.f_zayavnum IS NOT NULL AND dt.f_zayavnum != ''),0) shipment_before_dt,
                 d.f_onestrdocs onestrdocs,".
            "  (select f_name from ".DBPref."spr where f_type=39 and f_num=d.f_city) dogcity, ".
            "  c.f_addname contrname,c.f_inn contrinn,c.f_kpp contrkpp, s.f_dttoclnt,".
            "  case when s.f_dtreptocl is null or s.f_dtreptocl='0000-00-00' then ifnull(DATE_FORMAT(s.f_dttoclnt,'%d.%m.%Y'),'') else ifnull(DATE_FORMAT(s.f_dtreptocl,'%d.%m.%Y'),'') end dttoclnt,".
            " (SELECT ctgs.f_valstr FROM ".DBPref."categs ctgs WHERE ctgs.f_ctgtype=34 AND ctgs.f_objectid=o.f_id AND ctgs.f_objecttype=2 LIMIT 1) trade_secret_logo, " . // sdid 1825
            "  concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=o.f_opf),' ',o.f_cname) orgFname,o.f_opf,o.f_addname orgname,o.f_inn orginn,o.f_kpp orgkpp,o.f_sno osno ".
            "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c  ".
            "where d.f_id=s.f_dogid and o.f_id=d.f_orgid and c.f_id=d.f_contrid and s.f_id in (".$oprid.")";
        $res = $this->dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          $dtsost = $row['dttoclnt'];
          $dtsost_base = $dtsost;
          if(strlen($isdrep)>0)
            {
            $isdrep552 = " and si552.f_id in (".$isdrep.") "; 
            $isdrep = " and s.f_id in (".$isdrep.") ";
            $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt, SUBSTR(s.f_dttmcr,1,10) dtsost_base from ".DBPref."spec_invoices s where s.f_id>0 ".$isdrep." order by s.f_dttmcr desc";
            $res1 = $this->dbh->query($sql);
            if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $dtsost = $row1['dttoclnt'];
              $dtsost_base = $row1['dtsost_base'];
              }
            }
            if(strlen($row['trade_secret_logo'])>0) {$trade_secret_path = $row['trade_secret_logo'];}

            $zayav   = "спецификация";
            if($row['ssubtype']==3) {$zayav = "Поручение ТП";}
            elseif($row['f_typez']==3) {$zayav   = "заявка";}
            $opodpd  = "";
            $isppodp = "";
            $cpodpd  = "";
            $clnpodp = "";
            $opodpbd = "";
            $opodpbn = "";
            $dogsubtype = $row["dogsubtype"];
            $agent = "Агент";
            $agenta = "Агента";
            $princ = "Принципал";

            $agentrep_type = "Дополнительный";
            if($rep_num == 1) {$agentrep_type = "Основной";}
            $repsname = $agentrep_type." отчет об исполнении поручения";
            if(strlen($isdrep)>0){$repsname = $agentrep_type." отчет об исполнении поручения";}
            $repdop = "засчитывается в счет исполнения агентского договора";
            if($dogsubtype==1)
              {
              $agent = "Исполнитель";
              $agenta = "Исполнителя";
              $princ = "Заказчик";
              $repsname = "Отчет экспедитора";
              if(strlen($isdrep)>0){$repsname = $agentrep_type." отчет экспедитора";}
              $repdop = "возмещаемых услуг";
              }
            $orgid = $row['f_orgid'];
            $orgname = $row['orgname'];
            //$orgFname = "";
            $orgFname = $row['orgFname'];
            if($orgid>0)
              {
              $valid_dt = "";
              $dt = $dtsost_base;
              if($rep_id>0)
                {
                $sql_dt = "select ifnull(SUBSTRING(f_dttm,1,10),'') dt from ".DBPref."agentreps where f_id=$rep_id";
                $conn = $this->dbh->query($sql_dt);
                if($row_dt = $conn->fetch(PDO::FETCH_ASSOC)){$valid_dt = $row_dt['dt'];}
                }
              else
                {
                if(strlen($isdrep)>0 && $istorep==1)
                  {
                  $sql = "select ifnull(SUBSTRING(s.f_dttmcr,1,10),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id IN (".$opers.") order by s.f_dttmcr desc";
                  $res1 = $this->dbh->query($sql);
                  if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$valid_dt = $row1['dttoclnt'];}
                  }
                else
                  {
                  $valid_dt = $dtsost_base;
                  }
                }
              if(strlen($valid_dt)>0)
                {
                $result = get_client_contact_data($orgid, 17, $valid_dt);
                if($result[0]) {$orgFname = $result[1];}
                $result = get_client_contact_data($orgid, 18, $valid_dt);
                if($result[0]) {$orgname = $result[1];}
                $result = get_client_contact_data($orgid, 22, $valid_dt);
                if($result[0]) {$trade_secret_path = $result[1];}
                }
              }
            if($wphpword==1)
              {
              $sheet->setCellValue("A1", mb_strtoupper($agent).": ".$orgname." ИНН ".$row["orginn"]." КПП ".$row["orgkpp"]); // sdid 2347
              }
            $orgc = new mClient();
            $orgc->f_id = $row['f_orgid'];
            if($orgc->initForPrint(0)==1)
              {
              if(isset($orgc->ofcontacts['uradr']))
                {
                if(isset($orgc->ofcontacts['fio']))       {$isppodp = "/".$isppodp.$orgc->ofcontacts['fio']."/";}
                if (strlen($orgFname) === 0)              {$orgFname = $row['orgFname']; }
                if(isset($orgc->ofcontacts['dolzip']))    {$opodpd  = $opodpd.$orgc->ofcontacts['dolzip']." ".$orgFname;}
                if(isset($orgc->ofcontacts['buhdolzip'])) {$opodpbd = $opodpbd.$orgc->ofcontacts['buhdolzip']." ".$orgFname;}
                if(isset($orgc->ofcontacts['buhfio']))    {$opodpbn = "/".$opodpbn.$orgc->ofcontacts['buhfio']."/";}
                $cstr = $orgc->ofcontacts['uradr'];
                if(isset($orgc->ofcontacts['mphone']))    {$cstr = $cstr.", ".$orgc->ofcontacts['mphone'];}
                if($wphpword==1){$sheet->setCellValue("A2",$cstr);}
                }
              if(isset($orgc->ofcontacts['mbnkacc']))
                {
                if($wphpword==1){$sheet->setCellValue("A3",$orgc->ofcontacts['mbnkacc']);}
                }
              }
            if($wphpword==1) {$sheet->setCellValue("A4", mb_strtoupper($princ).": ".$row["contrname"]." ИНН ".$row["contrinn"]." КПП ".$row["contrkpp"]);}
            $contr = new mClient();
            $contr->f_id = $row['f_contrid'];
            if($contr->initForPrint(0)==1)
              {
              if(isset($contr->ofcontacts['fio'])) {$clnpodp = $contr->ofcontacts['fio'];}
              if(isset($contr->ofcontacts['dolz'])){$cpodpd  = $cpodpd.$contr->ofcontacts['dolzip'];}
              if(isset($contr->ofcontacts['uradr']))
                {
                $cstr = $contr->ofcontacts['uradr'];
                if(isset($contr->ofcontacts['mphone'])){$cstr = $cstr.", ".$contr->ofcontacts['mphone'];}
                if($wphpword==1){$sheet->setCellValue("A5",$cstr);}
                }
              if(isset($contr->ofcontacts['mbnkacc']))
                {
                if($wphpword==1){$sheet->setCellValue("A6",$contr->ofcontacts['mbnkacc']);}
                }
              }
            if($wphpword==1){$sheet->setCellValue("A8", $repsname);}
            if($rep_id>0)
              {
              $sql = "select ifnull(DATE_FORMAT(f_dtrep,'%d.%m.%Y'),'') dttoclnt from ".DBPref."agentreps s where s.f_id=$rep_id"; //sdid 3339
              $res1 = $this->dbh->query($sql);
              if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$dtsost = $row1['dttoclnt'];}
              }
            else
              {
              $dtsost = $row['dttoclnt'];
              if(strlen($opers)>1 && $istorep==1)
                {
                $letter = "";
                if($old_new_flag>0) {$letter = " s.";}
                $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id IN (".$opers.") order by s.f_dttmcr desc";
                $res1 = $this->dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$dtsost = $row1['dttoclnt'];}
                }
              if(strlen($isdrep)>0)
                {
                $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt, SUBSTR(s.f_dttmcr,1,10) dtsost_base from ".DBPref."spec_invoices s where s.f_id>0 ".$isdrep." order by s.f_dttmcr desc";
                $res1 = $this->dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                  {
                  $dtsost = $row1['dttoclnt'];
                  }
                }
              }
            if($wphpword==1){
                $sheet->setCellValue("A10", "Дата составления: ".$dtsost." г.");
                $sheet->setCellValue("A11", "Место составления: г. ".$row['dogcity']);
                //$sheet->setCellValue("A13", "Представляем отчет об исполнении поручения ".$row['contrname']."  по агентскому договору № ".$row['f_dogname']." от ".format_dt($row['f_dogdate'],2,1)." г., спецификация  № ".$row['f_num']." от ".format_dt($row['f_dt'],0,1)." г.");
                $sheet->setCellValue("A13", "Представляем отчет об исполнении поручения ".$row['contrname']."  по договору № ".$row['f_dogname']." от ".format_dt($row['f_dogdate'],2,1)." г., ".$zayav."  № ".$row['f_num']." от ".format_dt($row['f_dt'],0,1)." г.");
                $sheet->setCellValue("A14", "Получено от ".$princ."а в счет исполнения поручения: ");}
            $cstr = 15;
            //собираем оступившие платежные поурчения
            $sdtsost = "";
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
              {$sdtsost = " and h.f_ppdt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
            $letter = "";
            if($old_new_flag>0) {$letter = " si.";}
            if($istorep==1)
              {
              $sql1 = "#sdid3333
                       SELECT h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,s.f_sum clssum
                       FROM ".DBPref."pays s, ".DBPref."spec_invoices ssi,".DBPref."acchist h
                       WHERE s.f_id>0 and ssi.f_id=s.f_objid and s.f_objtype=35 and s.f_subtype=1 and h.f_id=s.f_acchistlink and ssi.f_id in ($opers) $sdtsost
                       union
                       #~sdid3333
                       select h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,
                       (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,
                       (select sum(f_clssum) from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                         (".$opers.") and f_acchistid=h.f_id) clssum
                     from ".DBPref."acchist h where f_type=0 and f_contrid=".$row['f_contrid']." and f_id in
                       (select f_acchistid from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                         (".$opers.")) ".$sdtsost;
              }
            else
              {
              $sql1 = "#sdid3333
                       SELECT h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,s.f_sum clssum
                       FROM ".DBPref."pays s, ".DBPref."spec_invoices ssi,".DBPref."acchist h
                       WHERE s.f_id>0 and ssi.f_id=s.f_objid and s.f_objtype=35 and s.f_subtype=1 and h.f_id=s.f_acchistlink and ssi.f_specid in ($oprid) $sdtsost
                       union
                       #~sdid3333
                       select h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,
                         (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val, 
                         (select sum(f_clssum) from ".DBPref."acchist_docs where f_doctype=3 and f_docid in 
                           (select f_id from ".DBPref."spec_invoices where f_parenttype=2 and f_specid in (".$oprid.") ) and f_acchistid=h.f_id) clssum 
                       from ".DBPref."acchist h where f_type=0 and f_contrid=".$row['f_contrid']." and f_id in 
                         (select f_acchistid from ".DBPref."acchist_docs where f_doctype=3 and f_docid in 
                           (select s.f_id from ".DBPref."spec_invoices s where s.f_parenttype=2 ".$isdrep." and s.f_specid in (".$oprid."))) ".$sdtsost;
              }
            error_log("\n\n1 issql_istorep ($istorep) = $sql1\n\n", 3, "/var/www/html/veda/logs/test3551.log"); //sdid 3551
            $res1 = $this->dbh->query($sql1);
            $ps = 0;$psv="";
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if($wphpword==1)
                {
                $sheet->setCellValue("C".$cstr,"п/п ".$row1['f_ppnum']." от ".format_dt($row1['f_ppdt'],0,1));
                $sheet->getStyle("D".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                //$sheet->setCellValue("D".$cstr,number_format($row1['f_sum'],2, ',', ' ')." ".$row1['val']);}
                $cval = $row1['val'];
                if($row1['f_val']==643)
                  {$csum = $row1['clssum'];}
                else
                  {$csum = $row1['f_sum'];}
                $sheet->setCellValue("D".$cstr,number_format($csum,2, ',', ' ')." ".$cval);
                }
              $ps=$ps+$row1['clssum'];
              $psv=$row1['val'];
              $cstr++;
              }
            if($wphpword==1)
              {
              $sheet->getStyle("B".$cstr.":D".$cstr)->getFont()->setBold(true);
              $sheet->setCellValue("B".$cstr,"Итого");
              $sheet->getStyle("D".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
              $sheet->setCellValue("D".$cstr,number_format($ps,2, ',', ' ')." ".$psv);
              $cstr++;
              $stl = $cstr;
              $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
              $sheet->setCellValue("A".$cstr,"Вид расхода");
              $sheet->mergeCells("A".$cstr.":A".($cstr+1));
              $sheet->setCellValue("B".$cstr,"Израсходовано (в руб.)");
              $sheet->mergeCells("B".$cstr.":B".($cstr+1));
              $sheet->mergeCells("C".$cstr.":F".$cstr);
              $sheet->setCellValue("C".$cstr,"Документ, подтверждающий расход");
              $sheet->mergeCells("G".$cstr.":I".$cstr);
              $sheet->setCellValue("G".$cstr,"Документ, подтверждающий оплату");
              $cstr++;
              $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
              $sheet->setCellValue("C".$cstr,"Наименование");
              $sheet->setCellValue("D".$cstr,"Контрагент");
              $sheet->setCellValue("E".$cstr,"Дата");
              $sheet->setCellValue("F".$cstr,"Номер");
              $sheet->setCellValue("G".$cstr,"Наименование");
              $sheet->setCellValue("H".$cstr,"Дата");
              $sheet->setCellValue("I".$cstr,"Номер");}
              $cstr++;
              $vozmsum = 0;
              $vozmkol = 0;
              if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
                {$sdtsost = " and s.f_dttmcr<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
              $letter = "";
              $letter2 = "";
              if($old_new_flag > 0)
                {
                $letter = " vssi.";
                $letter2 = " s.";
                }
              $spec_or_oper = "";
              if($istorep==1)
                {
                $spec_or_oper = " s.f_id ";
                $sioprids1 =  " and s.f_id in ($opers) ";
                $sioprids =  " and s.f_id in ($opers) ";
                $oprid = $opers;
                }
              else
                {
                $spec_or_oper = " s.f_specid ";
                }  
              //error_log("\n\nsioprids1 = $sioprids1\n\n",0);
              //error_log("\n\nisdrep = $isdrep\noprid = $oprid\nsioprids = $sioprids\nsdtsost = $sdtsost\ndtsost = $dtsost\nopers = $opers\n\n",0);
              $sql1 = "select 
                     get_rrsum(s.f_id,0) rrsum,
                     (select f_name from veda_spr where f_type=4 and f_num=h.f_val) hvaln,
                     (select f_curs from veda_acchist_docs where f_acchistid=h.f_id and f_doctype=3 and f_docid=s.f_id limit 1) ddcurs,
                     ifnull((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto 
                      where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and 
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') salevaldt,
                     ifnull((select lah.f_dt1C 
                      from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto 
                      where vsto.f_c1doctype=4 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and 
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     case 
                       when t.f_c1doctype=1 and s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643 
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto 
                                         where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0 then #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты~#
                         case
                           when DATE_FORMAT(h.f_dt1C,'%d.%m.%Y')=DATE_FORMAT((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'%d.%m.%Y') then -999999999.999
                           else round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-getcbrate(h.f_val,(select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1))) AS DECIMAL(15,2)),2)
                         end
                       else -999999999.999
                     end vozmoplinvalwsaleval,
                     case 
                       when t.f_c1doctype=18 and s.f_bdrarticle>0 and s.f_isvozm=1 and h.f_val<>643 
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto 
                                         where vsto.f_c1doctype=1 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid),0))>0 then #операции возмещаемой продажи валюты с наличием связанной оплаты в валюте~#
                         round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-h.f_cursoper) AS DECIMAL(15,2)),2)
                       else -999999999.999
                     end vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     #sdid2295~#
                     case 
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         (select f_name from veda_spr where f_type=120 and f_num=t.f_zdoctype)
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         (select f_name from veda_spr where f_type=120 and f_num=t.f_zdoctype)
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select spr.f_name from veda_spr spr, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and spr.f_num=a.f_tdocb and spr.f_type=104),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper in (395,454) then 
                         ifnull((select spr.f_name from veda_spr spr, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and spr.f_num=a.f_tdocb and spr.f_type=104),'-')
                       #~sdid 3325
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         'Акт выполненных работ'
                       when PA_ispayspecinv(s.f_id)=1 then 'Акт-отчет от платежного агента' #sdid2865~#
                       else
                         (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) 
                     end
                     #~sdid2295~#
                     zdoctype, 
                     case 
                       when PA_ispayspecinv(s.f_id)=1 then '' #sdid2865~#
                       when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then 
                         concat((select f_name from ".DBPref."nomenk where f_id=t.f_nomenkid),'. ',ifnull(s.f_invcom,'')) 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and 
                            (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         '' 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs=1 then 
                         (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',
                         case when s.f_sum=0 then 
                           ifnull((select round(cast(f_rate as decimal(15,5)),4) from ".DBPref."cbrates where f_val=s.f_val and f_dt=(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),1) else round(cast(h.f_cursoper as decimal(15,5)),4) end) from ".DBPref."schets i where i.f_id=s.f_invoiceid) 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs>1 then 
                         (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',
                                   round(cast(d.f_curs as decimal(15,5)),4) ) from ".DBPref."schets i where i.f_id=s.f_invoiceid) 
                       when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum=0 then 
                         (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) 
                       when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum>0 then 
                         (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) 
                       else '' 
                     end dopn, 
                     case
                       #sdid 3550
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 
                            and (select count(ah1.f_id) from veda_acchist ah1, veda_acchist_docs ahd1 where ah1.f_id=ahd1.f_acchistid and ahd1.f_doctype=3 and ahd1.f_docid=s.f_id)>0 then 
                         ifnull((select ah1.f_grnd from veda_acchist ah1, veda_acchist_docs ahd1 where ah1.f_id=ahd1.f_acchistid and ahd1.f_doctype=3 and ahd1.f_docid=s.f_id LIMIT 1),'')
                       #~sdid 3550
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper in (395,454) then 
                         t.f_name
                       #~sdid 3325
                       when PA_ispayspecinv(s.f_id)=1 then 'Курсовые разницы' #sdid2865~#
                       when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then '' 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and 
                            (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' ',
                                (select f_name from ".DBPref."spr where f_type=4 and 
                                   f_num=(select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' г. - ',
                                getcbrate((select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),
                                          (select f_dt from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                       when (t.f_c1doctype=4 or t.f_c1doctype=5 or t.f_c1doctype=18) then 
                         (select f_name from ".DBPref."spr where f_type=27 and f_num=t.f_c1doctype) 
                       else t.f_name 
                     end nop,
                     s.f_sum,s.f_val,s86.f_name,1 tp,
                     case 
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         ifnull(h.f_ppnum,'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull(h.f_ppnum,'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')
                       #~sdid 3325
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select la.f_num from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                       when t.f_zdoctype=3 then 
                         #sdid3300
                         #sdid 3601
                         #case
                         #  when s.f_dtid>0 then 
                         #    (select f_TDnum from veda_dt where f_id=s.f_dtid)
                         #  else dt.f_TDnum 
                         #end 
                         case
                           when s.f_dtid>0 then 
                             (select f_TDnum from veda_dt where f_id=s.f_dtid)
                           else ifnull((select f_TDnum from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end 
                         #~sdid 3601
                         #~sdid3300
                       when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                         where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' 
                        ),0)>0 then 
                        (select f_ppnum from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                         where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1
                        ) 
                       when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then 
                         h.f_ppnum 
                       else '' 
                     end ppnum,
                     case 
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         ifnull(h.f_ppdt,'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull(h.f_ppdt,'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')
                       #~sdid 3325
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select la.f_dt from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #sdid 3550
                       when t.f_type in (3, 25, 44, 45, 52, 56, 58, 59, 73, 144, 150, 179) then
                       #sdid 3601
                         #case 
                         #  when length(dt.f_TDnum)>3 then
                         #    ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX(dt.f_TDnum,'/',-2),'/',1),'%d%m%y'),'')
                         #  else dt.f_TDdt
                         #end
                       #-------------------
                         case
                           when s.f_dtid>0 then 
                             case 
                               when (select length(f_TDnum) from veda_dt where f_id=s.f_dtid)>3 
                                 then ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX((select f_TDnum from veda_dt where f_id=s.f_dtid),'/',-2),'/',1),'%d%m%y'),'')
                                 else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                               end 
                           else
                             ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end
                         #----------------- 
                       #~sdid 3601

                       #~sdid 3550
                       when t.f_zdoctype=3 then 
                         #sdid3300
                         #sdid 3601
                         #case
                         #  when s.f_dtid>0 then 
                         #    (select f_TDdt from veda_dt where f_id=s.f_dtid)
                         #  else dt.f_TDdt 
                         #end 
                         case
                           when s.f_dtid>0 then 
                             (select f_TDdt from veda_dt where f_id=s.f_dtid)
                           else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end 
                         #~sdid 3601
                         #~sdid3300
                       when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                         where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                            and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59'
                           ),0)>0 then 
                         (select f_ppdt from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                          where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                            and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1
                         ) 
                       when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then 
                        h.f_ppdt 
                      else '' 
                    end ppdt,
                    case 
                      #sdid 3325
                      when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                        ifnull(h.f_name,'-')
                      when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                        ifnull(h.f_name,'-')
                      when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                        ifnull(h.f_name,'-')
                      when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                        ifnull(h.f_name,'-')
                      when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                        ifnull((select cl.f_cname from veda_clients cl, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                 where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and cl.f_id=a.f_contrid),'-')
                      #~sdid 3325
                      when t.f_zdoctype=3 then 'ФТС'                      
                       when s.f_idoper=454 then                                                                                                      #sdid2865~#
                         ifnull((select lcc.f_cname from ".DBPref."clients lcc,".DBPref."acchist lah,".DBPref."acchist_docs lahd                     #sdid2865~#
                                 where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=s.f_id and lcc.f_id=lah.f_contrid limit 1),'')#sdid2865~#
                       when PA_ispayspecinv(s.f_id)=1 then                                          #sdid2865~#
                         ifnull((select lcc.f_cname from ".DBPref."clients lcc,".DBPref."dogs ldd   #sdid2865~#
                                 where lcc.f_id=ldd.f_contrid and ldd.f_id=s.f_dogid),'')           #sdid2865~#
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                       when t.f_c1doctype in (4,5,18) then 
                         (select lc.f_cname from ".DBPref."dogs ld,".DBPref."clients lc where lc.f_id=ld.f_contrid and ld.f_dogtype=10 
                         and ld.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=5 and f_valstr=h.f_orgbic1C) limit 1) 
                       when t.f_c1doctype=6 then 
                         (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) 
                       else h.f_name 
                     end contrn,
                     case 
                       when PA_ispayspecinv(s.f_id)=1 then
                         case
                           when PA_ispayspecinvusldolg(s.f_id)>0 then
                             (PA_getsumusldolgpay(s.f_id)-PA_getsumusldolg(s.f_id))
                           else
                             (PA_getsumusldolgpay(s.f_id)-PA_getsumaktdolg(s.f_id))
                         end
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                          (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       when t.f_c1doctype=4 and s.f_sum=0 then 0 
                       when t.f_c1doctype=4 and d.f_curs>1 and (select count(*) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."')>0 then 
                         CAST((IFNULL((select sum(f_sum) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'),0)*d.f_curs) AS DECIMAL(15,2)) 
                       when t.f_c1doctype=4 and d.f_curs>1 and (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>1
                         then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2) 
                       when (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>0 then 
                         (select sum(ahd.f_clssum) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                          where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                            ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)
                       when d.f_curs>1 then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2) 
                       else d.f_clssum 
                     end clssum 
                     ,-1 korrazn
                     ,'' na_name
                     ,'' na_dt
                     ,'' na_num
                     ,case when PA_ispayspecinv(s.f_id)=1 then 1 else 0 end ispayspecinvPA #sdid2865~#
                     ,s.f_idoper #sdid2865~#
                     ,1 blockid
                   from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."acchist_docs d,".DBPref."acchist h,".DBPref."typeopers t,".DBPref."spec_invoices s 
                     #sdid 3601
                     #left join ".DBPref."dt as dt on dt.f_specid=s.f_specid 
                     left join ".DBPref."dt as dt on dt.f_id=s.f_dtid 
                     #~sdid 3601
                   where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and 
                     s.f_parenttype=2 and $spec_or_oper in (".$oprid.") and h.f_ahtype<>4 and h.f_ahtype<>16 and 
                     ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) and #нет услуги в долг~#
                     ((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1) 
                      or (t.f_c1doctype in (4,5) and s.f_isvozm=1) 
                      or (s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643 
                          and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto 
                                       where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0) #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты~#
                      or (t.f_c1doctype=18 and s.f_isvozm=1) #возмещаемая продажа валюты~#
                     ) $sdtsost
                     and s.f_idoper not in (522,552,553) 
                     and d.f_docid=s.f_id and d.f_doctype=3 and h.f_id=d.f_acchistid 
                   union
                   select
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     case 
                       when s.f_idoper=389 then 'Акт выполненных работ'
                       else ''
                     end zdoctype, 
                     case 
                       when s.f_idoper=389 then
                         concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' ',
                                (select f_name from ".DBPref."spr where f_type=4 and 
                                   f_num=(select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                                getcbrate((select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),
                                          (select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                       else '' 
                     end nop,
                     case 
                       when s.f_idoper=389 then '' 
                       else '' 
                     end dopn,
                     s.f_sum,s.f_val,s86.f_name,0 tp,										
                     case 
                       when s.f_idoper=389 then 
                         (select la.f_num from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                       else ''
                     end ppnum,
                     case 
                       when s.f_idoper=389 then 
                         (select la.f_dt from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                       else ''
                     end ppdt,
                     case 
                       when s.f_idoper=389 then
                         (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=s.f_id and la.f_type=23 limit 1)
                       else ''
                     end contrn,
                     case 
                       when s.f_idoper=389 then
                         (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                       else 0
                     end clssum
                     ,-1 korrazn
                     ,'' na_name
                     ,'' na_dt
                     ,'' na_num
                     ,0 ispayspecinvPA #sdid2865~#
                     ,s.f_idoper #sdid2865~#
                     ,4 blockid
                   from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s 
                   where t.f_id=s.f_idoper 
                     and s86.f_type=86 
                     and s85.f_type=85 
                     and s85.f_dopprint=2
                     and s85.f_num=s86.f_uslint 
                     and s86.f_num=s.f_bdrarticle 
                     and s.f_parenttype=2 
                     and $spec_or_oper in (".$oprid.") 
                     $sioprids
                     and s.f_bdrarticle>0 
                     and s.f_idoper=389
                  #!!!не привязанные к банковским выпискам с расходными статьями бюджета
                  union 
                   select 
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     case 
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         (select f_name from veda_spr where f_type=120 and f_num=t.f_zdoctype)
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull((select f_name from veda_spr where f_type=120 and f_num=t.f_zdoctype),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select spr.f_name from veda_spr spr, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and spr.f_num=a.f_tdocb and spr.f_type=104),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper in (395,454) then 
                         ifnull((select spr.f_name from veda_spr spr, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and spr.f_num=a.f_tdocb and spr.f_type=104),'-')
                       #~sdid 3325 
                       #отбираем покупки валюты, по отпущенным в долг товарам
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then '-' 
                       else 
                         (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) 
                       end zdoctype, 
                       case 
                       #отбираем покупки валюты, по закрытым в долг услугам - акты от иностранного поставщика
                       #sdid 3550
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 
                            and (select count(ah1.f_id) from veda_acchist ah1, veda_acchist_docs ahd1 where ah1.f_id=ahd1.f_acchistid and ahd1.f_doctype=3 and ahd1.f_docid=s.f_id)>0 then 
                         ifnull((select ah1.f_grnd from veda_acchist ah1, veda_acchist_docs ahd1 where ah1.f_id=ahd1.f_acchistid and ahd1.f_doctype=3 and ahd1.f_docid=s.f_id LIMIT 1),'')
                       #~sdid 3550
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         t.f_name
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper in (395,454) then 
                         t.f_name
                       #~sdid 3325
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then 
                         concat('Неоплаченная сумма за услуги ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') 
                       #отбираем покупки валюты, по отпущенным в долг товарам по дате ППС
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then 
                         concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
                                (select DATE_FORMAT(f_perpravdt,'%d.%m.%Y') from ".DBPref."specs where f_id=s.f_specid),' г. - ',
                                getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') 
                       #отбираем покупки валюты, по закрытым в долг товарам по документу - Товар от иностранного поставщика
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=25)>0 then 
                         concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=25 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') 
                        #отбираем по детализации актов
                        when (select count(*) from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' 
                             and ad.f_operid=s.f_id )>0 then 
                         (select f_grnd from ".DBPref."akts_details where f_id in (select f_akts_detailsid from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak 
                           where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' and ad.f_operid=s.f_id) limit 1) 
                       else concat(t.f_name,'. ',ifnull(s.f_invcom,'')) 
                     end nop, 
                     case 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 then '' 
                       else '' 
                     end dopn,
                     s.f_sum,s.f_val,s86.f_name,0 tp,
                     case #when t.f_zdoctype=3 then #sdid 3325
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         ifnull((select ah.f_ppnum from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_docid=s.f_id and ahd.f_doctype=3),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull((select ah.f_ppnum from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_docid=s.f_id and ahd.f_doctype=3),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')									 
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                         ifnull((select a.f_num from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'-')
                       when t.f_zdoctype=3 then
                       #~sdid 3325 
                       #ifnull((select f_TDnum from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                       #sdid3300
                       case
                         when s.f_dtid>0 then 
                           (select f_TDnum from veda_dt where f_id=s.f_dtid)
                         else ifnull((select f_TDnum from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                       end 
                       #~sdid3300
                     else '' end ppnum,
                     case #when t.f_zdoctype=3 then #sdid 3325
                       #sdid 3550
                       when t.f_type in (3, 25, 44, 45, 52, 56, 58, 59, 73, 144, 150, 179) then
                       #sdid 3601
                         #case 
                         #  when length(dt.f_TDnum)>3 then
                         #    ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX(dt.f_TDnum,'/',-2),'/',1),'%d%m%y'),'')
                         #  else dt.f_TDdt
                         #end
                       #-------------------
                         case
                           when s.f_dtid>0 then 
                             case 
                               when (select length(f_TDnum) from veda_dt where f_id=s.f_dtid)>3 
                                 then ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX((select f_TDnum from veda_dt where f_id=s.f_dtid),'/',-2),'/',1),'%d%m%y'),'')
                                 else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                               end 
                           else
                             ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end
                         #----------------- 
                       #~sdid 3601
                       #~sdid 3550
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         ifnull((select ah.f_ppdt from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_docid=s.f_id and ahd.f_doctype=3),'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull((select ah.f_ppdt from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_docid=s.f_id and ahd.f_doctype=3),'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')									 
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                         ifnull((select a.f_dt from veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid),'')
                       when t.f_zdoctype=3 then
                       #~sdid 3325  
                       #ifnull((select f_TDdt from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                       #sdid3300
                       case
                         when s.f_dtid>0 then 
                           (select f_TDdt from veda_dt where f_id=s.f_dtid)
                         else ifnull((select f_TDdt from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                       end 
                       #~sdid3300
                     else '' end ppdt,
                     case #when t.f_zdoctype=3 then 'ФТС' #sdid 3325
                       #sdid 3325
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=73 then 
                         ifnull((select ah.f_name from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=s.f_id),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds=0 and s.f_idoper=125 then 
                         ifnull((select ah.f_name from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=s.f_id),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_nds<>0 and s.f_idoper=125 then 
                         ifnull((select ah.f_name from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=s.f_id),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=395 then 
                         ifnull((select ah.f_name from veda_acchist ah, veda_acchist_docs ahd where ah.f_id=ahd.f_acchistid and ahd.f_doctype=3 and ahd.f_docid=s.f_id),'-')
                       when s.f_itemcalcrp=11 and s.f_isvozm=1 and s.f_idoper=454 then 
                         ifnull((select cl.f_cname from veda_clients cl, veda_akts_details_opers ado, veda_akts_details ad, veda_akts a 
                                  where ado.f_operid=s.f_id and ad.f_id=ado.f_akts_detailsid and a.f_id=ad.f_aktid and cl.f_id=a.f_contrid),'-')
                       when t.f_zdoctype=3 then 'ФТС' 
                       #~sdid 3325 
                       when t.f_zdoctype in (4,6) then 
                         concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) 
                     else '' end contrn,
                     #отбираем покупки валюты, по отпущенным в долг товарам
                     case 
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then 
                         cast((s.f_sum*getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1))) AS DECIMAL(15,2))
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then 
                         cast((s.f_sum*getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid))) AS DECIMAL(15,2)) 
                       else s.f_sum 
                     end clssum 
                  #sdid 1552~#
                  ,(ifnull((select sum(nd.f_sum) from veda_netting_agr_details nd where nd.f_operid2=s.f_id),0)-s.f_sum) korrazn
                  ,'' na_name
                  ,'' na_dt
                  ,'' na_num
                  ,0 ispayspecinvPA #sdid2865~#
                  ,s.f_idoper #sdid2865~#
                  ,2 blockid
                  #~sdid 1552~#
                  from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s 
                  #sdid 3550
                  #left join ".DBPref."dt as dt on dt.f_specid=s.f_specid 
                  #sdid 3601
                  #left join ".DBPref."dt as dt on dt.f_specid=s.f_specid 
                  left join ".DBPref."dt as dt on dt.f_id=s.f_dtid 
                  #~sdid 3601
                  #~sdid 3550
                  #left join ".DBPref."dt as dt on dt.f_specid=s.f_specid 
                  where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and 
                    s.f_parenttype=2 and $spec_or_oper in (".$oprid.") and 
                    #((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1) or t.f_c1doctype in (1)) $sdtsost
                    s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 $sdtsost
                    and (select count(*) from ".DBPref."acchist_docs where f_docid=s.f_id and f_doctype=3)=0 
                    #sdid2295~#
                    and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг~#
                    #~sdid2295~#
                    #sdid 3092
                    and s.f_idoper not in (522,552,553) #Исключаем новые операции: Покупка товара, Покупка валюты, Перевод валюты
                    #~sdid 3092
                  #sdid 1552
                  union
                  select 
                    ################################################~#
                    get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                    '' salevaldt,
                    '' bayvaldt,
                    ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto 
                           where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                    ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto 
                           where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                    -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                    s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                    ################################################~#
                    'Инвойс' zdoctype, 
                    #s.f_id,~#
                    #nad.f_nettingagrid,~#
                    (concat(t.f_name, ' (', (select f_cname from veda_clients where f_id=na.f_contrid), '), инвойс № ',
                            ifnull((select concat (sch.f_num, ' от ', DATE_FORMAT(sch.f_dt,'%d.%m.%Y'), ' на сумму ', 
                                                                round(cast(sch.f_sum AS DECIMAL(15,2)),2), ' ', 
                     				                (select f_dopprstr from veda_spr where f_type=4 and f_num=sch.f_val)) 
                     				   from veda_schets sch 
                     					where sch.f_id=s.f_invoiceid 
                     				),''),
                     				', курс ', case when nad.f_curs2=1 then getcbrate(s.f_val,na.f_dt) else nad.f_curs2 end,' на ', DATE_FORMAT(na.f_dt,'%d.%m.%Y')
                     				 
                            )
                    ) nop,
                    ###############################################~#
                    case 
                      when t.f_c1doctype=4 and s.f_invoiceid>0 then '' 
                      else '' 
                    end dopn,
                    s.f_sum,s.f_val,s86.f_name,0 tp,										
                    ###############################################~#
                    (select sch.f_num from veda_schets sch where sch.f_id=s.f_invoiceid) ppnum,
                    (select DATE_FORMAT(sch.f_dt,'%d.%m.%Y') from veda_schets sch where sch.f_id=s.f_invoiceid) ppdt,
                    (select f_cname from veda_clients where f_id=na.f_contrid) contrn,
                    case na.f_val
                      when 643 then nad.f_sum  
                    	else round(cast(nad.f_sum*getcbrate(na.f_val, na.f_dt) AS DECIMAL(15,2)),2)
                    end clssum
                    ,-1 korrazn
                    ,'Соглашение о зачете взаимных требований' na_name
                    ,DATE_FORMAT(na.f_dt,'%d.%m.%Y') na_dt
                    ,na.f_num na_num
                    ,0 ispayspecinvPA #sdid2865~#
                    ,s.f_idoper #sdid2865~#
                    ,3 blockid
                  from veda_spr s86,veda_spr s85,veda_typeopers t,veda_spec_invoices s, veda_netting_agr_details nad, veda_netting_agr na 
                  where t.f_id=s.f_idoper 
                    and s86.f_type=86 
                    and s85.f_type=85 
                    and s85.f_num=s86.f_uslint 
                    and s86.f_num=s.f_bdrarticle 
                    and s.f_parenttype=2 
                    and $spec_or_oper in (".$oprid.") 
                    and s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 
                    #sdid2295~#
                    and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг~#
                    #~sdid2295~#
                    #sdid 3092
                    and s.f_idoper not in (522,552,553) #Исключаем новые операции: Покупка товара, Покупка валюты, Перевод валюты
                    #~sdid 3092
                    and nad.f_operid2=s.f_id
                    and na.f_id=nad.f_nettingagrid
                  #sdid2865 - добавляем курсовую разницу при покупке валюты по продаже валюты~#
                  union
                  select
                    get_rrsum(sp.f_id,0) rrsum,'' hvaln,0 ddcurs,
                    '' salevaldt,
                    '' bayvaldt,
                    0 valpaysellsumoper,
                    0 valpaysellsum,
                    -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                    s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                    '-' zdoctype, 
                    concat('Курсовая разница при приобретении валюты: ',round(cast(s.f_sum AS DECIMAL(15,2)),2),' ',
                           (select f_name from ".DBPref."spr where f_type=4 and f_num=sp.f_val),'.\n',
                           'курс покупки валюты: ',
                           case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select lah.f_cursoper from ".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                                where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end,
                           ' руб.\n',
                           'курс ЦБ на дату покупки валюты: ',
                           case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select getcbrate(lah.f_val,lah.f_dt1C) from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                                where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end,
                           ' руб.\n',
                           'Расчет курсовой разницы: (',
                           case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select lah.f_cursoper from ".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                                where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end
                           ,' - ',
                           case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select getcbrate(lah.f_val,lah.f_dt1C) from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                                where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end,
                           ')*',round(cast(s.f_sum AS DECIMAL(15,2)),2),'=',
                           round(cast(((case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select lah.f_cursoper from ".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                                where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end-case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select getcbrate(lah.f_val,lah.f_dt1C) from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                                where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end)*s.f_sum) AS DECIMAL(15,2)),2),' руб.') nop,
                    '' dopn,
                    s.f_sum,s.f_val,s86.f_name,0 tp,										
                    (select lah.f_ppnum from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                     where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1) ppnum,
                    (select lah.f_dt1C from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                     where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1) ppdt,
                    (select b.f_bankname from ".DBPref."banks b,".DBPref."acchist lah, ".DBPref."acchist_docs lahd 
                     where b.f_bic=lah.f_contrbic1C and lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and 
                       lahd.f_docid=sp.f_id limit 1) contrn,
                    round(cast(((case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select lah.f_cursoper from ".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                                where lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end-case 
                             when (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 then
                               (select getcbrate(lah.f_val,lah.f_dt1C) from ".DBPref."acchist lah, ".DBPref."acchist_docs lahd
                                where lahd.f_acchistid=lah.f_id and lahd.f_acchistid=lah.f_id and lahd.f_doctype=3 and lahd.f_docid=sp.f_id limit 1)
                             else 1
                           end)*s.f_sum) AS DECIMAL(15,2)),2) clssum
                    ,-1 korrazn
                    ,'' na_name
                    ,'' na_dt
                    ,'' na_num
                    ,-1 ispayspecinvPA 
                    ,sp.f_idoper #sdid2865~#
                    ,5 blockid
                  from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."typeopers tp,".DBPref."spec_invoices s,".DBPref."spec_invoices sp 
                  where t.f_id=s.f_idoper 
                    and s86.f_type=86 
                    and s85.f_type=85 
                    #and s85.f_dopprint=1
                    and s85.f_num=s86.f_uslint 
                    and s86.f_num=s.f_bdrarticle 
                    and s.f_parenttype=2 
                    $sioprids1
                    and t.f_c1doctype=18
                    and sp.f_id=s.f_parentid
                    and tp.f_id=sp.f_idoper 
                    and tp.f_c1doctype=4
                    and (select count(*) from ".DBPref."acchist_docs where f_doctype=3 and f_docid=sp.f_id)>0 
                    and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid=sp.f_id)>0
                  #~sdid2865 - добавляем курсовую разницу при покупке валюты по продаже валюты~#
                    #sdid 3092
                    and s.f_idoper not in (522,552,553) #Исключаем новые операции: Покупка товара, Покупка валюты, Перевод валюты
                    #~sdid 3092

                  #sdid 3092 НОВЫЕ ОПЕРАЦИИ 522,552,553
                  union
                  select 
                     get_rrsum(s.f_id,0) rrsum,
                     (select f_name from veda_spr where f_type=4 and f_num=h.f_val) hvaln,
                     (select f_curs from veda_acchist_docs where f_acchistid=h.f_id and f_doctype=3 and f_docid=s.f_id limit 1) ddcurs,
                     ifnull((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto 
                      where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and 
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') salevaldt,
                     ifnull((select lah.f_dt1C 
                      from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto 
                      where vsto.f_c1doctype=4 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and 
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto 
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     case 
                       when t.f_c1doctype=1 and s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643 
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto 
                                         where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0 then #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты~#
                         case
                           when DATE_FORMAT(h.f_dt1C,'%d.%m.%Y')=DATE_FORMAT((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'%d.%m.%Y') then -999999999.999
                           else round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-getcbrate(h.f_val,(select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1))) AS DECIMAL(15,2)),2)
                         end
                       else -999999999.999
                     end vozmoplinvalwsaleval,
                     case 
                       when t.f_c1doctype=18 and s.f_bdrarticle>0 and s.f_isvozm=1 and h.f_val<>643 
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto 
                                         where vsto.f_c1doctype=1 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid),0))>0 then #операции возмещаемой продажи валюты с наличием связанной оплаты в валюте~#
                         round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-h.f_cursoper) AS DECIMAL(15,2)),2)
                       else -999999999.999
                     end vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     case 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         'Акт выполненных работ'
                       when PA_ispayspecinv(s.f_id)=1 then 'Акт-отчет от платежного агента' #sdid2865~#
                       else
                         (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) 
                     end
                     zdoctype, 
                    #############################################
                    case 
                      when PA_ispayspecinv(s.f_id)=1 
                        then ''
                      when t.f_nomenkid in (2,24) and t.f_c1doctype=3 
                        then concat((select f_name from veda_nomenk where f_id=t.f_nomenkid),'. ',ifnull(s.f_invcom,'')) 
                      when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from veda_spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 
                        then '' #услуга в долг~#
                      # 552 - Покупка валюты. Ком.Инвойс вытягиваем из предка предка с f_idoper=522				
                      #when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs=1 
                      when (s.f_idoper=552 and t.f_c1doctype=4 and s.f_parentid>0 and d.f_curs=1 
                            and (select si2.f_cominvid from veda_spec_invoices si1, veda_spec_invoices si2 
                                  where si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)>0 
                                    and (select a.f_val from veda_akts a, veda_spec_invoices si1, veda_spec_invoices si2 
                                          where a.f_id=si2.f_cominvid and si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)=s.f_val
                                            and h.f_val=s.f_val)											 
                        then (select concat('(',(SELECT f_name FROM veda_spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM veda_clients where f_id=s.f_contrid)),' ',
                                            (select f_cname from veda_clients where f_id=s.f_contrid),
                                            '), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'), ' на сумму ',
                                            format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',
                                            (select f_namedop from veda_spr where f_type=4 and f_num=h.f_val),
                                            ', курс ',
                                            case 
                                              when s.f_sum=0 
                                                then ifnull((select round(cast(f_rate as decimal(15,5)),4) 
                                                               from veda_cbrates where f_val=s.f_val and f_dt=(select f_perpravdt from veda_specs where f_id=s.f_specid)),1) 
                                                else round(cast(h.f_cursoper as decimal(15,5)),4) 
                                            end
                                            ) 
                                from veda_akts i 
                               where i.f_id=(select si2.f_cominvid from veda_spec_invoices si1, veda_spec_invoices si2 
                                              where si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)
                             ) 
                      # 552 - Покупка валюты. Ком.Инвойс вытягиваем из предка предка с f_idoper=522			
                      #when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs>1 
                      when (s.f_idoper=552 and t.f_c1doctype=4 and s.f_parentid>0 and d.f_curs>1 
                            and (select si2.f_cominvid from veda_spec_invoices si1, veda_spec_invoices si2 
                                  where si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)>0 
                                    and (select a.f_val from veda_akts a, veda_spec_invoices si1, veda_spec_invoices si2 
                                          where a.f_id=si2.f_cominvid and si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)=s.f_val
                                            and h.f_val=s.f_val)											 
                        then (select concat('(',(SELECT f_name FROM veda_spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM veda_clients where f_id=s.f_contrid)),' ',
                                            (select f_cname from veda_clients where f_id=s.f_contrid),
                                            '), инвойс № ', i.f_num, ' от ', DATE_FORMAT(i.f_dt,'%d.%m.%Y'), ' на сумму ',
                                            format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'), ' ',
                                            (select f_namedop from veda_spr where f_type=4 and f_num=h.f_val),
                                            ', курс ', round(cast(d.f_curs as decimal(15,5)),4) 
                                           ) 
                                from veda_akts i 
                               where i.f_id=(select si2.f_cominvid from veda_spec_invoices si1, veda_spec_invoices si2 
                                              where si1.f_id=s.f_parentid and si2.f_id=si1.f_parentid and si2.f_idoper=522)
                             ) 
                      # 553 - Перевод валюты. Ком.Инвойс вытягиваем из предка с f_idoper=522
                      when (s.f_idoper=553 and t.f_c1doctype=5 and s.f_parentid>0 
                            and (select f_cominvid from veda_spec_invoices where f_id=s.f_parentid and f_idoper=522)>0 
                            and (select a.f_val from veda_akts a, veda_spec_invoices si where a.f_id=si.f_cominvid and si.f_id=s.f_parentid)=s.f_val
                           and h.f_val=s.f_val)
                        then (select concat('(',(SELECT f_name FROM veda_spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM veda_clients where f_id=s.f_contrid)),' ',
                                            (select f_cname from veda_clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',
                                            DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',
                                            (select f_namedop from veda_spr where f_type=4 and f_num=h.f_val)) 
                                from veda_akts i 
                               where i.f_id=(select f_cominvid from veda_spec_invoices where f_id=s.f_parentid)) 
                      else ''
                    end dopn, 
                    ##############################################
                    case 
                       when PA_ispayspecinv(s.f_id)=1 then 'Курсовые разницы' #sdid2865~#
                       when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then '' 
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and 
                            (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг~#
                         concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' ',
                                (select f_name from ".DBPref."spr where f_type=4 and 
                                   f_num=(select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' г. - ',
                                getcbrate((select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),
                                          (select f_dt from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                      when (t.f_c1doctype=4 or t.f_c1doctype=5 or t.f_c1doctype=18) then 
                        (select f_name from ".DBPref."spr where f_type=27 and f_num=t.f_c1doctype) 
                       else t.f_name 
                     end nop,
                    s.f_sum,s.f_val,s86.f_name,1 tp,
                    case 
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select la.f_num from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                  
                      when t.f_zdoctype=3 then 
                         #sdid3300
                         #sdid 3601
                         #case
                         #  when s.f_dtid>0 then 
                         #    (select f_TDnum from veda_dt where f_id=s.f_dtid)
                         #  else dt.f_TDnum 
                         #end 
                         case
                           when s.f_dtid>0 then 
                             (select f_TDnum from veda_dt where f_id=s.f_dtid)
                           else ifnull((select f_TDnum from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end 
                         #~sdid 3601
                         #~sdid3300
                      when s.f_idoper=552 and t.f_c1doctype=4 and h.f_ahtype=3 then
                        h.f_ppnum	
                      when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                        where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ##
                        ),0)>0 then 
                        (select f_ppnum from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                         where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ##
                         limit 1 
                        ) 
                      when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ##
                        h.f_ppnum ##
                      when s.f_idoper=553 and t.f_c1doctype=5 and h.f_ahtype=2 then
                        h.f_ppnum
                      else '' 
                    end ppnum,
                    case 
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select la.f_dt from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                  
                      when t.f_zdoctype=3 then 
                         #sdid3300
                        #sdid 3601
                        #case
                        #  when s.f_dtid>0 then 
                        #    (select f_TDdt from veda_dt where f_id=s.f_dtid)
                        #  else dt.f_TDdt 
                        #end 
                         case
                           when s.f_dtid>0 then 
                             (select f_TDdt from veda_dt where f_id=s.f_dtid)
                          else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end 
                        #~sdid 3601
                         #~sdid3300
                      when s.f_idoper=552 and t.f_c1doctype=4 and h.f_ahtype=3 then
                        case when h.f_dt1C <>  '0000-00-00 00:00:00' then DATE_FORMAT(h.f_dt1C,'%d.%m.%Y') else DATE_FORMAT(h.f_ppdt,'%d.%m.%Y') end
                      when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                        where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ##
                          ),0)>0 then 
                        (select f_ppdt from ".DBPref."acchist lh,".DBPref."acchist_docs lhd 
                         where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id 
                           and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ##
                         limit 1
                        ) 
                      when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ##
                        h.f_ppdt ##
                      when s.f_idoper=553 and t.f_c1doctype=5 and h.f_ahtype=2 then
                        case when h.f_dt1C <>  '0000-00-00 00:00:00' then DATE_FORMAT(h.f_dt1C,'%d.%m.%Y') else DATE_FORMAT(h.f_ppdt,'%d.%m.%Y') end
                      else '' 
                    end ppdt,
                    case 
                      when t.f_zdoctype=3 then 'ФТС' 
                       when s.f_idoper=454 then                                                                                                      #sdid2865~#
                         ifnull((select lcc.f_cname from ".DBPref."clients lcc,".DBPref."acchist lah,".DBPref."acchist_docs lahd                     #sdid2865~#
                                 where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=s.f_id and lcc.f_id=lah.f_contrid limit 1),'')#sdid2865~#
                       when PA_ispayspecinv(s.f_id)=1 then                                          #sdid2865~#
                         ifnull((select lcc.f_cname from ".DBPref."clients lcc,".DBPref."dogs ldd   #sdid2865~#
                                 where lcc.f_id=ldd.f_contrid and ldd.f_id=s.f_dogid),'')           #sdid2865~#
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                         (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                       when t.f_c1doctype in (4,5,18) then 
                        (select lc.f_cname from ".DBPref."dogs ld,".DBPref."clients lc where lc.f_id=ld.f_contrid and ld.f_dogtype=10 
                        and ld.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=5 and f_valstr=h.f_orgbic1C) limit 1) 
                      when t.f_c1doctype=6 then 
                        (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) 
                       else h.f_name 
                    end contrn,
                    case 
                       #sdid2865~#
                       when PA_ispayspecinv(s.f_id)=1 then
                         case
                           when PA_ispayspecinvusldolg(s.f_id)>0 then
                             (PA_getsumusldolgpay(s.f_id)-PA_getsumusldolg(s.f_id))
                           else
                             (PA_getsumusldolgpay(s.f_id)-PA_getsumaktdolg(s.f_id))
                         end
                       #sdid2865~#
                       #sdid2295 услуга в долг~#
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then 
                          (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                       #~sdid2295~#
                       ##when t.f_c1doctype=4 and s.f_sum=0 then 0 
                       when t.f_c1doctype=4 and d.f_curs>1 and (select count(*) from ".DBPref."akts where f_type=23 and f_operid=s.f_id 
                                                                and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' ##
                                                               )>0 
                         then 
                         CAST((IFNULL((select sum(f_sum) from ".DBPref."akts where f_type=23 and f_operid=s.f_id 
                                          and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' ##
                                     ),0)*d.f_curs) AS DECIMAL(15,2)) 
                       when t.f_c1doctype=4 and d.f_curs>1 and (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>1
                         then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2) 
                       when (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>0 then 
                         (select sum(ahd.f_clssum) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1 
                          where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and 
                            ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)
                       when d.f_curs>1 then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2) 
                       else d.f_clssum 
                     end clssum 
                     #sdid 1552~#
                     ,-1 korrazn
                     ,'' na_name
                     ,'' na_dt
                     ,'' na_num
                     ,case when PA_ispayspecinv(s.f_id)=1 then 1 else 0 end ispayspecinvPA #sdid2865~#
                     ,s.f_idoper #sdid2865~#
                     ,6 blockid ".
                     "
                     from veda_spr s86,veda_spr s85,veda_acchist_docs d,veda_acchist h,veda_typeopers t,veda_spec_invoices s 
                       #sdid 3601
                       #left join veda_dt as dt on dt.f_specid=s.f_specid 
                       left join ".DBPref."dt as dt on dt.f_id=s.f_dtid 
                       #~sdid 3601
                     where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle 
                       and s.f_parenttype=2 
                       and $spec_or_oper in (".$oprid.") 
                       and h.f_ahtype<>4 
                       and h.f_ahtype<>16 
                       and s.f_idoper in (522,552,553) 
                       and d.f_docid=s.f_id 
                       and d.f_doctype=3 
                       and d.f_clssum<>0
                       and h.f_id=d.f_acchistid 
                       ".
                       //$sdtsost.
                       " 
                       and ( (s.f_parentid in (select si522.f_id 
                                                 from veda_spec_invoices si522, veda_akts a 
                                                where si522.f_idoper=522 
                                                and si522.f_specid=s.f_specid 
                                                and si522.f_isvozm=1 
                                                and si522.f_val=s.f_val
                                                and si522.f_cominvid>0
                                                and a.f_id=si522.f_cominvid
                                                and a.f_val=s.f_val
                                                ) /*and s.f_sum>0*/) 
                             or 
                             (s.f_parentid in (select si553.f_id 
                                                 from veda_spec_invoices si553, veda_spec_invoices si522, veda_akts a 
                                                where si553.f_idoper=553 
                                                  and si553.f_specid=s.f_specid
                                                  #and si553.f_sum>0
                                                  and si522.f_idoper=522
                                                  and si522.f_isvozm=1
                                                  and si522.f_cominvid>0
                                                  and a.f_id=si522.f_cominvid
                                                  and a.f_val=s.f_val
                                                  and si553.f_val=s.f_val
                                                  and si522.f_val=s.f_val
                                                  and si553.f_parentid=si522.f_id
                                              ) 
                             )								 
                           )
                     "; 
            //if($_SESSION['loginid']==2)
            //  {echo $sql1."<br>";}
            $cnt553 = 0; // кол-во операций перевода валюты в выборке //sdid 3092
            $res1 = $this->dbh->query($sql1);
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if($row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454) {error_log("\n\nblockid = ".$row1['blockid']."\n\n",0);} //sdid 3325
              $rrvalsum   = 0;
              $wdoprow    = 0;
              $needaddrow = 1;
              $nop = $row1['nop'].$row1['dopn'];
              $clssum = number_format($row1['clssum'],2, ',', ' ');
              if(($row1['f_idoper']==386)or($row1['f_idoper']==388))
                {
                $nop = "Курсовая разница при переоценке задолженности за товар ".$row1['sicom']." ".$row1['nop'];
                }
              if(($row1['blockid']==2)&&($row1['korrazn']==0))
                {$needaddrow = 0;$clssum = 0;}
              elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']==-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {$needaddrow = 0;$clssum = 0;}
              elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                $clssum = number_format($row1['vozmoplinvalwsaleval'],2, ',', ' ');
                $ccbrate = getcbrate($row1['f_val'],$row1['salevaldt']);
                $nop = "Курсовая разница при переоценке валюты принципала на расчетном счете ".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                     "курс ЦБ на ".format_dt($row1['ppdt'],0,1)." - ".number_format($row1['ddcurs'],4, ',', ' ')." руб.\n".
                     "курс ЦБ на дату продажи валюты ".format_dt($row1['salevaldt'],0,1)." - ".$ccbrate." руб.\n".
                     "Расчет курсовой разницы: \n(".number_format($row1['ddcurs'],4, ',', ' ')."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".number_format($row1['vozmoplinvalwsaleval'],2, ',', ' ')."р.";
                }
              elseif(($row1['f_c1doctype']==4)&&($row1['f_val']<>643)&&($row1['valpaysellsumoper']>0)&&
                     ($row1['valpaysellsum']!=0)&&($row1['vozmoplinvalwsaleval']==-999999999.999))#операции покупки валюты не в день продажи
                {
                $ccbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                $clssum  = number_format(($row1['ddcurs']-$ccbrate)*$row1['f_sum'],2, ',', ' ');
                $nop = "Курсовая разница при приобретении валюты\n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                     "курс покупки валюты ".number_format($row1['ddcurs'],4, ',', ' ')." руб.\n".
                     "курс ЦБ на дату покупки валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                     "Расчет курсовой разницы: \n(".number_format($row1['ddcurs'],4, ',', ' ')."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $clssum."р.";
                }
              elseif(($row1['f_c1doctype']==4)&&($row1['clssum']==0)&&($row1['f_invoiceid']>0)&&
                     ($row1['valpaysellsumoper']<$row1['f_sum']))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше суммы операции
                {
                if($row1['f_parenttype']==2)
                  {
                  $sql2 = "select f_perpravdt from ".DBPref."specs where f_id=".$row1['f_specid'];
                  $res2 = $this->dbh->query($sql2);
                  if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $cbdt = $row2['f_perpravdt'];
                    if(strcmp($cbdt,"0000-00-00")==0){}
                    else
                      {
                      $sql2 = "select f_sum,f_val from ".DBPref."schets where f_id=".$row1['f_invoiceid'];
                      $res2 = $this->dbh->query($sql2);
                      if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                        $row1['clssum']=round(($row2['f_sum']*getCBRate($row2['f_val'],$cbdt)),2);
                        $clssum = number_format($row1['clssum'],2, ',', ' ');
                        }
                      }
                    }
                  }
                }
              elseif(($row1['f_c1doctype']==4)&&($row1['f_invoiceid']>0)&&
                     ($row1['valpaysellsumoper']>=$row1['f_sum'])&&($row1['f_idoper']!=552))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше = сумме операции //sdid 3092 2025-03-12
                {$needaddrow = 0;$clssum = 0; /*error_log("\n\nsfid=".$row1['sfid']." idoper=".$row1['f_idoper']."\n\n",0);*/}
              elseif(($row1['f_c1doctype']==4)&&($row1['clssum']>0)&&($row1['f_invoiceid']>0))
                {$clssum = number_format($row1['clssum'],2, ',', ' ');}
              elseif($row1['f_c1doctype']==5)
                {$clssum = "";$row1['clssum']=0;}
              elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                $clssum = number_format($row1['vozmsalevalwoplinval'],2, ',', ' ');
                $ccbrate = number_format(getcbrate($row1['f_val'],$row1['ppdt']),4, ',', ' ');
                $nop = "Курсовая разница при продаже валюты принципала ".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                     "курс продажи валюты ".number_format($row1['f_cursoper'],4, ',', ' ')." руб.;\n".
                     "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                     "Расчет курсовой разницы: \n(".$ccbrate."-".number_format($row1['f_cursoper'],4, ',', ' ').")*".number_format($row1['f_sum'],2, ',', ' ')."=".number_format($row1['vozmsalevalwoplinval'],2, ',', ' ')."р.";
                }
              elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']==-999999999.999)&&(strlen($row1['bayvaldt'])>0))#операции продажи валюты не в день покупки
                {
                $row1['f_kindzdoc'] = 2;
                $wdoprow = 1;
                $vbbrate = getcbrate($row1['f_val'],substr($row1['bayvaldt'],0,10));
                $cbbrate = number_format($vbbrate,4, ',', ' ');
                $vcbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                $ccbrate = number_format($vcbrate,4, ',', ' ');
                $clssum  = number_format(($vcbrate-$row1['ddcurs'])*$row1['f_sum'],2, ',', ' ');
                $rrvalsum = round(($vbbrate-$vcbrate)*$row1['f_sum'],2);
                $rvalsum = number_format($rrvalsum,2, ',', ' ');
                $nopd = "Курсовая разница при переоценке валюты \n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                     "курс ЦБ на дату покупки валюты ".format_dt($row1['bayvaldt'],0,1)." - ".$cbbrate." руб.\n".
                     "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                     "Расчет курсовой разницы: \n(".$cbbrate."-".$ccbrate.")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $rvalsum."р.";
                $nop = "Курсовая разница при продаже валюты \n".number_format($row1['f_sum'],2, ',', ' ')." ".$row1['hvaln']."\n".
                     "курс ЦБ на дату продажи валюты ".format_dt($row1['ppdt'],0,1)." - ".$ccbrate." руб.\n".
                     "курс продажи валюты ".number_format($row1['ddcurs'],4, ',', ' ')." руб.;\n".
                     "Расчет курсовой разницы: \n(".$ccbrate."-".number_format($row1['ddcurs'],4, ',', ' ').")*".number_format($row1['f_sum'],2, ',', ' ')."=".
                        $clssum."р.";
                if($needaddrow==1)
                  {
                  if($wphpword==1){
                    $sheet->setCellValue("A".$cstr,$nopd);
                    $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $sheet->setCellValue("B".$cstr,$rvalsum);
                    $sheet->setCellValue("C".$cstr,"-");
                    $sheet->setCellValue("D".$cstr,$row1['contrn']);
                    $sheet->setCellValue("E".$cstr,"-");
                    $sheet->setCellValue("G".$cstr,"Платежное поручение");
                    $sheet->setCellValue("H".$cstr,format_dt($row1['ppdt'],0,1));
                    $sheet->setCellValue("I".$cstr,$row1['ppnum']);
                    $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                    $sheet->getStyle("C".$cstr.":C".$cstr)->getAlignment()->setWrapText(true);
                    $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
                    $sheet->getStyle("G".$cstr.":G".$cstr)->getAlignment()->setWrapText(true);
                    if($istorep==1)
                      {
                      $oa_add = [
                        'curtbl' => 305,
                        'f_agentrep' => $rep_id,
                        'f_spending' => $nopd,
                        'f_spent' => $rvalsum,
                        'f_spending_proof_name' => '-',
                        'f_spending_proof_client' => $row1['contrn'],
                        'f_spending_proof_date' => '-',
                        'f_payment_proof_name' => 'Платежное поручение',
                        'f_payment_proof_date' => format_dt($row1['ppdt'],0,1),
                        'f_payment_proof_num' => $row1['ppnum']
                        ];
                      $oa->addRowTbl($this->dbh, $oa_add, "");
                      }
                    }
                  $cstr++;
                  }
                }
              if($istorep==1) {$oa_add = ['curtbl' => 305, 'f_agentrep' => $rep_id];} // sdid 2363
              if($needaddrow==1)
                {
                if($wphpword==1)
                  {
                  $sheet->setCellValue("A".$cstr,$nop);
                  $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                  $sheet->setCellValue("B".$cstr,$clssum);
                  $valC = "";
                  $valD = "";
                  $valE = "";
                  $valG = "";
                  $valH = "";
                  $valI = "";
                  if($row1['blockid']==4) {$valC = $row1['zdoctype'];}
                  elseif($row1['f_idoper']==73 || $row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454) {$valC = $row1['zdoctype'];}
                  else {$valC = "-";}
                  if(($row1['blockid']==4)||($row1['f_idoper']==454)) {$valD = $row1['contrn'];}
                  elseif($row1['f_idoper']==73 || $row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454) {$valD = $row1['contrn'];}
                  else {$valD = "-";}
                  if($row1['blockid']==4) {$valE = format_dt($row1['ppdt'],0,1);}
                  elseif($row1['f_idoper']==73 || $row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454)
                    {
                    if(strlen($row1['ppdt'])>0) {$valE = format_dt($row1['ppdt'],0,1);}
                    else {$valE = "-";}
                    }
                  else {$valE = "-";}
                  // !!!!!!!!!! В отчет ОА вероятно нужно добавить еще одно поле для вывода столбца F !!!!!!!!
                  if($row1['f_idoper']==73 || $row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454)
                    {$sheet->setCellValue("F".$cstr,$row1['ppnum']); if($istorep==1) {}}
                  if($row1['f_idoper']==73 || $row1['f_idoper']==125 || $row1['f_idoper']==395 || $row1['f_idoper']==454)
                    {
                    $valG = "-"; $valH = "-"; $valI = "-";
                    }
                  elseif(($row1['ispayspecinvPA']==1)||($row1['ispayspecinvPA']==-1))
                    {
                    $valG = "Платежное поручение";
                    $valH = format_dt($row1['ppdt'],0,1);
                    $valI = $row1['ppnum'];
                    }
                  elseif($row1['f_idoper']==553) // Перевод валюты
                    {
                    $valG = $row1['zdoctype'];
                    $valH = format_dt($row1['ppdt'],0,1);
                    $valI = $row1['ppnum'];
                    }
                  else {$valG = "-"; $valH = "-"; $valI = "-";}
                  $sheet->setCellValue("C".$cstr,$valC);
                  $sheet->setCellValue("D".$cstr,$valD);
                  $sheet->setCellValue("E".$cstr,$valE);
                  $sheet->setCellValue("G".$cstr,$valG);
                  $sheet->setCellValue("H".$cstr,$valH);
                  $sheet->setCellValue("I".$cstr,$valI);
                  if($istorep==1) 
                    {
                    $oa_add['f_spending'] = $nop;
                    $oa_add['f_spent'] = $clssum;
                    $oa_add['f_spending_proof_name']   = $valC;
                    $oa_add['f_spending_proof_client'] = $valD;
                    $oa_add['f_spending_proof_date']   = $valE;
                    $oa_add['f_payment_proof_name']    = $valG;
                    $oa_add['f_payment_proof_date']    = $valH;
                    $oa_add['f_payment_proof_num']     = $valI;
                    }
                  }
                }
              if($wdoprow==1)
                {
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    {
                    if($istorep==1) {$oa_add['f_spending_proof_client'] = $row1['contrn'];} // sdid 2363
                    $sheet->setCellValue("D".$cstr,$row1['contrn']);
                    }
                  }
                }
              if(($row1['f_kindzdoc']==1)||($row1['f_kindzdoc']==3))
                {
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    {
                    if($istorep==1) {$oa_add['f_spending_proof_client'] = $row1['contrn'];}
                    $sheet->setCellValue("D".$cstr,$row1['contrn']);
                    }
                  }
                $zdoct = $row1['zdoctype'];
                if((($row1['f_zdoctype']==6)||//Закрывающий документ
                    ($row1['f_zdoctype']==4))  //Заявление на страхование
                    &&($row1['blockid']!=3)//соглашение о зачете
                  )
                  {
                  $zD = "";$zE="";$zF="";$wk=0;
                  $sql = "select s.f_sum,(select f_uslstr from ".DBPref."spr where f_type=4 and f_num=s.f_val) val,s.f_val,s.f_id,".
                            "  concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) clname,".
                            "  (select f_name from ".DBPref."spr where f_type=104 and f_num=s.f_tdocb) tdocb,s.f_status,s.f_num,s.f_dt ".
                            "from ".DBPref."akts s ".
                            "where (s.f_operid=".$row1['sfid']." or ".
                            "  s.f_id in (select f_aktid from ".DBPref."akts_details where f_id in ".
                            "  (select f_akts_detailsid from ".DBPref."akts_details_opers where f_operid=".$row1['sfid']."))) and s.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'";
                  $zD = "";$zE="";$zF="";$wk=0;
                  $res2 = $this->dbh->query($sql);
                  while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                    {
                    $zdoct = $row2['tdocb'];
                    if(strlen($zD)>0){$zD=$zD.";";}$zD=$zD.$row2['clname'];
                    if(strlen($zE)>0){$zE=$zE.";";}$zE=$zE.format_dt($row2['f_dt'],0,1);
                    if(strlen($zF)>0){$zF=$zF.";";}$zF=$zF.$row2['f_num'];
                    $wk++;
                    }
                  if(($wk==0)&&($row1['ispayspecinvPA']==1)) {$zD .= $row1['contrn'];}//sdid2865
                  if($needaddrow==1)
                    {
                    if($wphpword==1)
                      {
                      $sheet->setCellValue("D".$cstr,$zD);
                      $sheet->setCellValue("E".$cstr,$zE);
                      $sheet->setCellValue("F".$cstr,$zF);
                      if($istorep==1)
                        {
                        $oa_add['f_spending_proof_client'] = $zD;
                        $oa_add['f_spending_proof_date'] = $zE;
                        $oa_add['f_spending_proof_num'] = $zF;
                        }
                      }
                    }
                  }
                if($needaddrow==1)
                  {
                  if($wphpword==1)
                    {
                    if($row1['f_idoper']!=553) 
                      {
                      $sheet->setCellValue("C".$cstr,$zdoct);
                      if($istorep==1) {$oa_add['f_spending_proof_name'] = $zdoct;}
                      } 
                    else 
                      {
                      $sheet->setCellValue("C".$cstr,"-");
                      if($istorep==1) {$oa_add['f_spending_proof_name'] = "-";}
                      }
                    if((($row1['f_zdoctype']!=6)&&($row1['f_zdoctype']!=4)&&($row1['blockid']!=5))||($row1['blockid']==3))//sdid2865
                      {
                      if($row1['f_idoper']==553)
                        {
                        $sheet->setCellValue("E".$cstr,"-");
                        $sheet->setCellValue("F".$cstr,"-");
                        if($istorep==1) {$oa_add['f_spending_proof_date'] = '-';}
                        if($istorep==1) {$oa_add['f_spending_proof_num'] = '-';}
                        }
                      else
                        {
                        if(strlen($row1['ppdt'])>0)
                          {
                          $sheet->setCellValue("E".$cstr,format_dt($row1['ppdt'],0,1));
                          if($istorep==1) {$oa_add['f_spending_proof_date'] = format_dt($row1['ppdt'],0,1);}
                          }
                        else
                          {
                          $sheet->setCellValue("E".$cstr,"");
                          if($istorep==1) {$oa_add['f_spending_proof_date'] = "";}
                          }
                        $sheet->setCellValue("F".$cstr,$row1['ppnum']);
                        if($istorep==1) {$oa_add['f_spending_proof_num'] = $row1['ppnum'];}
                        }
                      }
                    }
                  }
                }
              if($row1['blockid']==4)
                {
                $sheet->setCellValue("F".$cstr,$row1['ppnum']);
                if($istorep==1) {$oa_add['f_spending_proof_num'] = $row1['ppnum'];}
                }
              if($needaddrow==1) //!!!!!!!!!!!!!!!
                {
                if($wphpword==1)
                  {
                  if((($row1['f_kindzdoc']==2)||($row1['f_kindzdoc']==3))&&($row1['f_idoper']!=552)&&($row1['f_idoper']!=73)&&($row1['f_idoper']!=125)&&($row1['f_idoper']!=395)&&($row1['f_idoper']!=454)) //sdid 3092 //sdid 3325
                    {
                    if($row1['f_c1doctype']==5)
                      {
                      if($istorep==1) {$oa_add['f_spending_proof_client'] = $row1['contrn'];} // sdid 2363
                      $sheet->setCellValue("D".$cstr,$row1['contrn']);
                      }
                    if(strcmp($row1['zdoctype'],"Заявление на страхование")==0)
                      {if(strlen($row1['ppnum'])>0)
                        {
                        if($istorep==1) {$oa_add['f_payment_proof_name'] = 'Платежное поручение';} // sdid 2363
                        $sheet->setCellValue("G".$cstr,"Платежное поручение");
                        }}
                      else
                        {
                        if($istorep==1) {$oa_add['f_payment_proof_name'] = $row1['zdoctype'];} // sdid 2363
                        $sheet->setCellValue("G".$cstr,$row1['zdoctype']);
                        }
                      if(strlen($row1['ppdt'])>0)
                        {
                        if($istorep==1) {$oa_add['f_payment_proof_date'] = format_dt($row1['ppdt'],0,1);} // sdid 2363
                        $sheet->setCellValue("H".$cstr,format_dt($row1['ppdt'],0,1));
                        }
                      else
                        {
                        if($istorep==1) {$oa_add['f_payment_proof_date'] = "";} // sdid 2363
                        $sheet->setCellValue("H".$cstr,"");
                        }
                      if($istorep==1) {$oa_add['f_payment_proof_num'] = $row1['ppnum'];} // sdid 2363
                      $sheet->setCellValue("I".$cstr,$row1['ppnum']);
                      }
                if(strcmp($row1['na_name'],"Соглашение о зачете взаимных требований")==0)
                  {
                  $sheet->setCellValue("G".$cstr,$row1['na_name']);
                  $sheet->setCellValue("H".$cstr,format_dt($row1['na_dt'],0,1));
                  $sheet->setCellValue("I".$cstr,$row1['na_num']);
                  if($istorep==1)
                    {
                    $oa_add['f_payment_proof_name'] = $row1['na_name'];
                    $oa_add['f_payment_proof_date'] = format_dt($row1['na_dt'],0,1);
                    $oa_add['f_payment_proof_num'] = $row1['na_num'];
                    }
                  }
                $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                $sheet->getStyle("C".$cstr.":C".$cstr)->getAlignment()->setWrapText(true);
                $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
                $sheet->getStyle("G".$cstr.":G".$cstr)->getAlignment()->setWrapText(true);
                }
              $klclssum = str_replace(' ','',str_replace(',','.',$clssum));
              $vozmsum=$vozmsum+$rrvalsum;
              if(is_numeric($klclssum)) {$vozmsum=$vozmsum+$klclssum;}
              if($row1['blockid']==6) {if($row1['f_idoper']==553) {$cnt553++;}}
              $cstr++;
              if($istorep==1) {$oa->addRowTbl($this->dbh, $oa_add, "");} // sdid 2363
              $vozmkol++;
              } // !!!! добавил скобку
            } // !!!! добавил скобку
          // Если есть операции Перевода валюты (553), собираем суммы Покупки валюты (552) в разрезе Ком.инвойсов, привязанных к операциям Покупки товара (522)
          if($cnt553>0)
            {
            $sdtsost552 = "";
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
              {$sdtsost552 = " and si552.f_dttmcr<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
            $sql = "
                    select a.f_id cominvid,
                           a.f_sum cominvsum, 
                           (select f_namedop from veda_spr where f_type=4 and f_num=h.f_val) valname,
                           ifnull((select DATE_FORMAT(f_perpravdt,'%d.%m.%Y') from veda_specs where f_id=si552.f_specid),'') perpravdt,
                           case when (select f_perpravdt from veda_specs where f_id=si552.f_specid) is not null
                             then getcbrate(h.f_val, (select f_perpravdt from veda_specs where f_id=si552.f_specid))
                             else ''
                           end cursperpravdt,
                    	   #round(cast(sum(si552.f_sum) AS DECIMAL(15,2)),2) sum552
                    	   round(cast(sum(d.f_clssum) AS DECIMAL(15,2)),2) sum552,
                           ifnull(concat('(',(SELECT f_name FROM veda_spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM veda_clients where f_id=si552.f_contrid)),' ',
                                         (select f_cname from veda_clients where f_id=si552.f_contrid),'), инвойс № ',a.f_num,' от ',DATE_FORMAT(a.f_dt,'%d.%m.%Y')),'') contrinvdescr 
                      from veda_spec_invoices si552, # Покупка валюты
                    	   veda_spec_invoices si553, # Перевод валюты
                    	   veda_spec_invoices si522, # Покупка товара
                           veda_akts a,
                    	   veda_acchist_docs d,
                    	   veda_acchist h
                     where a.f_id=si522.f_cominvid
                           and si522.f_id=si553.f_parentid
                    	   and si553.f_id=si552.f_parentid
                    	   and si552.f_parenttype=2
                    	   and si552.f_specid in ($oprid)
                    	   and si552.f_idoper=552
                    	   and si553.f_idoper=553
                    	   and si522.f_idoper=522
                    	   #and si552.f_dttmcr<='2023-06-30 23:59:59'	
                           $sdtsost552
                           $isdrep552
                    	   and d.f_docid=si552.f_id 
                    	   and d.f_doctype=3 
                    	   and h.f_id=d.f_acchistid
                    	   and h.f_val=si552.f_val
                    	   and a.f_val=si552.f_val
                    	   #group by d.f_id
                    	   group by a.f_id
                   ";
            $res3 = $this->dbh->query($sql);
            while($row3 = $res3->fetch(PDO::FETCH_ASSOC))
              {
              $deltasum = $row3['cominvsum'] - $row3['sum552'];
              if($deltasum!=0)
                {
                $perpravdt = $row3['perpravdt'];
                if(strlen($perpravdt)!=0) {$perpravdt = $perpravdt."г.";}
                $cursperpravdt = $row3['cursperpravdt'];
                $deltasumrub = "";
                $sdeltasumrub = "";
                if(strlen($cursperpravdt)>0) {$deltasumrub = round($deltasum*$cursperpravdt,2); $sdeltasumrub = number_format($deltasumrub,2, ',', ' ');}
                $sdeltasum = number_format($deltasum,2, ',', ' ');
                $celmsg = "Неоплаченная сумма за товар $sdeltasum ".$row3['valname'].
                          " для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ".
                          $perpravdt." - ".$cursperpravdt." ".$row3['contrinvdescr'].""
                          ;
                $sheet->setCellValue("A".$cstr,$celmsg);
                $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue("B".$cstr,$sdeltasumrub);
                $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(false);
                $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                if(is_numeric($deltasumrub))
                  {$vozmsum=$vozmsum+$deltasumrub;}
                $cstr++;
                if($istorep==1) 
                  {
                  $oa_add = [
                      'curtbl' => 305,
                      'f_agentrep' => $rep_id,
                      'f_spending' => $celmsg,
                      'f_spent' => $sdeltasumrub,
                      'f_spending_proof_name' => '',
                      'f_spending_proof_client' => '',
                      'f_spending_proof_date' => '',
                      'f_payment_proof_name' => '',
                      'f_payment_proof_date' => '',
                      'f_payment_proof_num' => ''
                      ];
                  $oa->addRowTbl($this->dbh, $oa_add, "");
                  }
                $vozmkol++;
                }
              }
            }
          //!!!! здесь возможно нужно вписать те 2 верхние скобки !!!!
          if($vozmkol>0)//пишем невозмещаемые расходы
            {
              if($wphpword==1){
                  $sheet->getStyle("A".$cstr.":B".$cstr)->getFont()->setBold(true);
                  $sheet->setCellValue("A".$cstr,"ИТОГО ".$repdop);
                  $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                  $sheet->setCellValue("B".$cstr,number_format($vozmsum,2, ',', ' '));
                  $f_vozm = $vozmsum;
                  $sheet->setCellValue("C".$cstr,"***");
                  $sheet->setCellValue("D".$cstr,"***");
                  $sheet->setCellValue("E".$cstr,"***");
                  $sheet->setCellValue("F".$cstr,"***");
                  $sheet->setCellValue("G".$cstr,"***");
                  $sheet->setCellValue("H".$cstr,"***");
                  $sheet->setCellValue("I".$cstr,"***");
                  if($istorep==1) 
                    {
                    $oa_add = [
                        'curtbl' => 305,
                        'f_agentrep' => $rep_id,
                        'f_spending' => "ИТОГО ".$repdop,
                        'f_spent' => number_format($vozmsum,2, ',', ' '),
                        'f_spending_proof_name' => "***",
                        'f_spending_proof_client' => "***",
                        'f_spending_proof_date' => "***",
                        'f_spending_proof_num' => "***",
                        'f_payment_proof_name' => "***",
                        'f_payment_proof_date' => "***",
                        'f_payment_proof_num' => "***",
                        'f_bold' => 1
                        ];
                    $oa->addRowTbl($this->dbh, $oa_add, "");
                    }
                  }
              $cstr++;
            }
          $nvozmsum = 0;
          $ndss     = 0;
          $letter = "";
          if($old_new_flag>0) {$letter = " s.";}
          $spec_or_oper = "";
          if($istorep==1) {$spec_or_oper =  "s.f_id  IN (".$opers.")"; }
          else {$spec_or_oper = "s.f_specid in (".$oprid.")";}
          $sql1 = "select s.f_sum,s.f_id,#sdid3272
                   concat(t.f_name,'. ',ifnull(s.f_invcom,'')) nop
                 from ".DBPref."spec_invoices s,".DBPref."typeopers t
                 where t.f_id=s.f_idoper and s.f_parenttype=2 ".$isdrep." and $spec_or_oper and
                   #sdid 637
                   #(s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298)
                   (s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298,237,285)
                   #~sdid 637";
          $res1 = $this->dbh->query($sql1);
          if($row['onestrdocs'] == 0)
            {
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              $sheet->setCellValue("A".$cstr,$row1['nop']);
              $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
              $sheet->setCellValue("B".$cstr,number_format($row1['f_sum'],2, ',', ' '));
              $sheet->setCellValue("C".$cstr,"***");
              $sheet->setCellValue("D".$cstr,"***");
              $sheet->setCellValue("E".$cstr,"***");
              $sheet->setCellValue("F".$cstr,"***");
              $sheet->setCellValue("G".$cstr,"***");
              $sheet->setCellValue("H".$cstr,"***");
              $sheet->setCellValue("I".$cstr,"***");
              if($istorep==1)
                {
                $oa_add = [
                    'curtbl' => 305,
                    'f_agentrep' => $rep_id,
                    'f_spending' => $row1['nop'],
                    'f_spent' => number_format($row1['f_sum'],2, ',', ' '),
                    'f_spending_proof_name' => "***",
                    'f_spending_proof_client' => "***",
                    'f_spending_proof_date' => "***",
                    'f_spending_proof_num' => "***",
                    'f_payment_proof_name' => "***",
                    'f_payment_proof_date' => "***",
                    'f_payment_proof_num' => "***"
                    ];
                $oa->addRowTbl($this->dbh, $oa_add, "");
                }
              $nvozmsum=$nvozmsum+$row1['f_sum'];
              $lnds = getNDS(["dt"=>date('Y-m-d',strtotime($dtsost)),"specinvid"=>$row1['f_id']]);
              $lnds = $lnds[0];//sdid3342
              if($lnds>-1)
                {
                $sql  = "select f_uslint from ".DBPref."spr where f_type=10 and f_num=$lnds";
                $res2 = $this->dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {$ndss = $ndss+round(($row1['f_sum']*$row2['f_uslint']/(100+$row2['f_uslint'])),2);}
                }
              $cstr++;
              }
            }
          elseif($row['onestrdocs'] == 1)
            {
            $services_names = ""; // Список наименования услуг
            $is_first_loop_passed = 0;
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
              {
              if($is_first_loop_passed)
              {$services_names = $services_names . "; " . $row1['nop'];}
              else
              {
              $services_names = $row1['nop'];
              $is_first_loop_passed = 1;
              }
              $nvozmsum += $row1['f_sum'];
              $lnds = getNDS(["dt"=>date('Y-m-d',strtotime($dtsost)),"specinvid"=>$row1['f_id']]);
              $lnds = $lnds[0];//sdid3342
              if($lnds>(-1))
                {
                $sql  = "select f_uslint from ".DBPref."spr where f_type=10 and f_num=$lnds";
                $res2 = $this->dbh->query($sql);
                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                  {$ndss = $ndss+round(($row1['f_sum']*$row2['f_uslint']/(100+$row2['f_uslint'])),2);}
                }
              }
            $services_names = "Вознаграждение агента ($services_names)";
            $sheet->setCellValue("A".$cstr, $services_names);
            $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("B".$cstr,number_format($nvozmsum,2, ',', ' '));
            $sheet->setCellValue("C".$cstr,"***");
            $sheet->setCellValue("D".$cstr,"***");
            $sheet->setCellValue("E".$cstr,"***");
            $sheet->setCellValue("F".$cstr,"***");
            $sheet->setCellValue("G".$cstr,"***");
            $sheet->setCellValue("H".$cstr,"***");
            $sheet->setCellValue("I".$cstr,"***");
            if($istorep==1)
              {
              $oa_add = [
                  'curtbl' => 305,
                  'f_agentrep' => $rep_id,
                  'f_spending' => $services_names,
                  'f_spent' => number_format($nvozmsum,2, ',', ' '),
                  'f_spending_proof_name' => "***",
                  'f_spending_proof_client' => "***",
                  'f_spending_proof_date' => "***",
                  'f_spending_proof_num' => "***",
                  'f_payment_proof_name' => "***",
                  'f_payment_proof_date' => "***",
                  'f_payment_proof_num' => "***"
                  ];
              $oa->addRowTbl($this->dbh, $oa_add, "");
              }
            $cstr++;
            }
            $sheet->getStyle("A".$cstr.":B".$cstr)->getFont()->setBold(true);
            $sheet->setCellValue("A".$cstr,"Всего выставлено ".mb_strtolower($princ)."у к оплате");
            $sheet->getStyle("B".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("B".$cstr,number_format(($vozmsum+$nvozmsum),2, ',', ' '));
            $f_nvozm = $nvozmsum;
            $f_total = $vozmsum + $nvozmsum;
            $sheet->setCellValue("C".$cstr,"***");
            $sheet->setCellValue("D".$cstr,"***");
            $sheet->setCellValue("E".$cstr,"***");
            $sheet->setCellValue("F".$cstr,"***");
            $sheet->setCellValue("G".$cstr,"***");
            $sheet->setCellValue("H".$cstr,"***");
            $sheet->setCellValue("I".$cstr,"***");
            if($istorep==1)
              {
              $oa_add = [
                  'curtbl' => 305,
                  'f_agentrep' => $rep_id,
                  'f_spending' => "Всего выставлено ".mb_strtolower($princ)."у к оплате",
                  'f_spent' => number_format(($vozmsum+$nvozmsum),2, ',', ' '),
                  'f_spending_proof_name' => "***",
                  'f_spending_proof_client' => "***",
                  'f_spending_proof_date' => "***",
                  'f_spending_proof_num' => "***",
                  'f_payment_proof_name' => "***",
                  'f_payment_proof_date' => "***",
                  'f_payment_proof_num' => "***",
                  'f_bold' => 1
                  ];
              $oa->addRowTbl($this->dbh, $oa_add, "");
              }
            $etl = $cstr;
            $sheet->getStyle("A".$stl.":I".$etl)->applyFromArray($borderi);
            $sheet->getStyle("A".$stl.":I".$etl)->applyFromArray($bordero);
            $sheet->getStyle("A".$stl.":A".$etl)->getAlignment()->setWrapText(true);
            $sheet->getStyle("B".$stl.":B".$etl)->getAlignment()->setWrapText(true);
            $cstr++;
            $sheet->setCellValue("A".$cstr,"* Копии документов, подтверждающих суммы расходов и оплату расходов, прилагаем. Оригиналы хранятся у ".$agenta.".");
            $cstr=$cstr+2;
            $sheet->setCellValue("A".$cstr,"Отчет ".mb_strtolower($agenta)." утвержден в сумме ".number_format(($vozmsum+$nvozmsum),2, ',', ' ')." (".sum2words(($vozmsum+$nvozmsum),643).")");
            $cstr++;
            $cnds = ", без НДС";
            if($ndss>0)
              {
              $cnds = ", в т.ч. НДС ".number_format($ndss,2, ',', ' ');
              }
            $sheet->setCellValue("A".$cstr,"в том числе вознаграждение ".mb_strtolower($agenta)." в сумме ".number_format(($nvozmsum),2, ',', ' ')." (".sum2words(($nvozmsum),643).")".$cnds);
            $is_weco = $row['weco'] > 0 ? 1 : 0;
            $is_shipment_before_dt = $row['shipment_before_dt'];
            if($is_weco || $is_shipment_before_dt)
              {
              $cstr += 2;
              if($is_weco)
              {
              //sdid 3539
              $sheet->mergeCells("A".$cstr.":J".($cstr+1));
              $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
              $sheet->getStyle("A".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
              //~sdid 3539
              $sheet->getStyle("A".$cstr)->getFont()->setItalic(true);
              //$sheet->setCellValue("A".$cstr, "Внимание! Есть товары, включенные в Перечень товаров, упаковки товаров (утв.  Распоряжением Правительства РФ от 31.12.2020  N 3721-р), подлежащих утилизации и/или уплате экологического сбора в Роспотребнадзор.");
              $sheet->setCellValue("A".$cstr, "Внимание! В списке импортируемых товаров содержатся товары, по которым согласно Постановлению Правительства РФ от 30.12.2024 N 1990 оплачивается экологический сбор. Обязанность по уплате экологического сбора возложена на Принципала. С подробностями о порядке, сроках и размерах оплаты Вы можете ознакомиться на сайте Росприроднадзора https://rpn.gov.ru/activity/rop/ecological-fee/.");
              //$cstr++;  //sdid 3539
              $cstr += 2; //sdid 3539
              }
              if($is_shipment_before_dt)
              {
                  //sdid 3539
                  $sheet->mergeCells("A".$cstr.":J".($cstr+1));
                  $sheet->getStyle("A".$cstr.":A".$cstr)->getAlignment()->setWrapText(true);
                  $sheet->getStyle("A".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                  //~sdid 3539
                  $sheet->getStyle("A".$cstr)->getFont()->setItalic(true);
                  $sheet->setCellValue("A".$cstr, "Внимание! Товар выпущен по Заявлению до подачи ДТ. Расчет таможенных платежей не является документом, подтверждающим уплату таможенных платежей. После выпуска ДТ будет предоставлен доп. отчет на сумму фактически уплаченных таможенных платежей с приложением копии ДТ.");
                  //$cstr++;  //sdid 3539
                  $cstr += 2; //sdid 3539


              }
              $cstr++;
              }
            else{$cstr += 2;}
            $sheet->setCellValue("A".$cstr,"От имени ".$agenta.":");
            $sheet->setCellValue("D".$cstr,"От имени ".$princ."а ".$row["contrname"].":");
            $cstr=$cstr+2;
            $sheet->setCellValue("A".$cstr,$opodpd);
            $sheet->setCellValue("C".$cstr,$isppodp);
            $sheet->setCellValue("D".$cstr,$cpodpd);
            $sheet->setCellValue("E".$cstr,"/");
            $sheet->setCellValue("F".$cstr,"/".$clnpodp);
            $sheet->getStyle("D".$cstr.":D".$cstr)->getAlignment()->setWrapText(true);
            $sheet->getStyle("B".$cstr.":B".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("D".$cstr.":D".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("E".$cstr.":E".$cstr)->applyFromArray($borderb);
            $sheet->getStyle("F".$cstr.":F".$cstr)->applyFromArray($borderb);
            $cstr=$cstr+1;
            $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D".$cstr)->getFont()->setSize(10);
            $sheet->getStyle("E".$cstr)->getFont()->setSize(10);
            $sheet->getStyle("F".$cstr)->getFont()->setSize(10);
            $sheet->setCellValue("D".$cstr,"должность");
            $sheet->setCellValue("E".$cstr,"подпись");
            $sheet->setCellValue("F".$cstr,"расшифровка подписи");
            $cstr=$cstr+1;
            $sheet->setCellValue("A".$cstr,$opodpbd);
            $sheet->setCellValue("C".$cstr,$opodpbn);
            $sheet->getStyle("B".$cstr.":B".$cstr)->applyFromArray($borderb);
            $cstr=$cstr+2;
            $sheet->getStyle("A".$cstr.":I".$cstr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue("B".$cstr,"М.П.");
            $sheet->setCellValue("D".$cstr,"М.П.");
          }
        if(strlen($trade_secret_path) > 0)
          {
          $trade_secret_img = imagecreatefrompng(__DIR__."/../../templates/".$trade_secret_path);
          $trade_secret_obj = new PHPExcel_Worksheet_MemoryDrawing();
          $trade_secret_obj->setName('Trade Secret Logo');
          $trade_secret_obj->setDescription('Trade Secret Logo');
          $trade_secret_obj->setImageResource($trade_secret_img);
          $trade_secret_obj->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
          $trade_secret_obj->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
          $trade_secret_obj->setHeight(70);
          $trade_secret_obj->setWorksheet($xls->getActiveSheet());
          $trade_secret_obj->setCoordinates('F9');
          $trade_secret_obj->setOffsetY(10);
          }
        $objWriter = new PHPExcel_Writer_Excel2007($xls);
        $fname = __DIR__ . "/../../download/agrep".$_SESSION['loginid']."_".date("His").".xlsx";
        $objWriter->save($fname);
        if($istorep==1)
          {
          $file = file_get_contents($fname);
          $update_rep = ['curtbl' => 286, 'curidx' => $rep_id, 'f_flrep' => base64_encode($file), 'f_vozm' => $f_vozm, 'f_nvozm' => $f_nvozm, 'f_total' => $f_total]; // sdid 2073
          $result = editRowTbl($update_rep);
          }
        if($istorep==1)
          {
          if($old_new_flag === 0 && $do_download) // sdid 2109
            {
            file_force_download($fname);
            }
          return $result . "; " . $rep_id;
          }
        else {file_force_download($fname);}
    }
    //~sdid 3496

    public function downloadAgentRepById($id)
    {
        try {
            $sql = "SELECT * FROM veda_agentreps WHERE f_id=" . $id;
            $conn = $this->dbh->query($sql);
            if ($row = $conn->fetch(PDO::FETCH_ASSOC)) {

                if ($row['f_status'] == 1)
                {
                    $blob = $row['f_flrep'];
                    $data = base64_decode($blob);
                    $path = __DIR__ . "/../../download/agrep" . $_SESSION['loginid'] . "_" . date("His") . ".xlsx";
                    $result = file_put_contents($path, $data);
                    $result = file_force_download($path);
                    $result = unlink($path);
                }
                elseif ($row['f_specid'] > 0)
                {
                    //$this->createAgentRepFile(15, $row['f_specid'], $row['f_id'], $row['f_num']); //sdid 3496
                    $this->createAgentRepFile1(15, $row['f_specid'], $row['f_id'], $row['f_num'],0,true,1); //sdid 3496
                }
            }
        } catch (\Throwable $e)
        {
            echo 'Error: '.$e->getMessage() . "; " . $e->getFile() . "; " .$e->getLine();
        }
    }

    public function createAgentRepBySpec($id = 0)
    {
        $messages = [];
        $operations_old = [];
        $operations_new = [];
        $response = [true, ""];

        $data = [];

        if ($id > 0)
        {
//            $sql = "SELECT
//                        (CASE si.f_parenttype
//                            WHEN 4 THEN
//                                (select
//                                    (select lsp.f_id
//                                    from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
//                                             veda_clients o,veda_contacts ct
//                                    where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
//                                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 and
//                                        lct.f_objectid=si.f_id)
//                                from veda_dogs dd,veda_clients oo,veda_clients cc
//                                where oo.f_id=dd.f_orgid and cc.f_id=dd.f_contrid and dd.f_id=si.f_specid)
//                            ELSE
//                                (SELECT f_id FROM veda_specs WHERE f_id=si.f_specid)
//                        END) specid,
//                        (SELECT f_id FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_id,
//                        (SELECT f_num FROM veda_agentreps WHERE f_specid=si.f_specid AND f_status=0 LIMIT 1) rep_num,
//                        (SELECT MAX(f_num) FROM veda_agentreps WHERE f_specid=si.f_specid) rep_num_max,
//                        si.f_id
//                        FROM veda_spec_invoices si
//                        WHERE si.f_id IN (".$oprid.")";
//            $conn = $this->dbh->query($sql);
//            while ($row = $conn->fetch(PDO::FETCH_ASSOC))
//            {
//                $rep_id = -1;
//                $rep_num = -1;
//                if (strlen($row['specid']) > 0)
//                {
//                    $spec_id = $row['specid'];
//                    if(!key_exists($spec_id, $data))
//                    {
//                        $data[$spec_id] = ['opers' => [$row['f_id']]];
//
//                        if (strlen($row['rep_id']) > 0 && strlen($row['rep_num']) > 0)
//                        {
//                            $rep_id = $row['rep_id'];
//                            $rep_num = $row['rep_num'];
//
//                            $data[$spec_id]['rep_id'] = $rep_id;
//                            $data[$spec_id]['rep_num'] = $rep_num;
//
//                            $messages[] = "Успешно найден отчет ид ". $rep_id;
//                        }
//                        else
//                        {
//                            if (strlen($row['rep_num_max']) > 0)
//                            {
//                                $rep_num = $row['rep_num_max'] + 1;
//                            }
//                            else
//                            {
//                                $rep_num = 1;
//                            }
//
//                            $data[$spec_id]['rep_num'] = $rep_num;
//
//                            $create_new_rep = [
//                                'curtbl' => 286,
//                                'f_specid' => $spec_id,
//                                'f_num' => $rep_num,
//                                'f_dttm' => date('Y-m-d H:i:s'),
//                                'f_status' => 0
//                            ];
//
//                            $result = json_decode(addRowTbl($create_new_rep));
//                            if ($result[0])
//                            {
//                                $data[$spec_id]['rep_id'] = $result[2];
//
//                                $messages[] = "Успешно создан отчет ид ". $result[2];
//                            }
//                            else
//                            {
//                                $messages[] = "Ошибка создания счет-операции по ".json_encode($create_new_rep);
//                            }
//                        }
//                    }
//                    else
//                    {
//                        $data[$spec_id]['opers'][] = $row['f_id'];
//                    }
//                }
//            }

            $sql_duplicate_check = "SELECT COUNT(f_id) cnt FROM veda_agentreps WHERE f_num=1 AND f_specid=".$id;
            $conn_check = $this->dbh->query($sql_duplicate_check);
            if($row_check = $conn_check->fetch(PDO::FETCH_ASSOC))
              {
              if($row_check['cnt'] > 0)
                {
                return [false, "Основной отчет уже есть и привязан к спецификации"];
                }
              }

            $sql = "SELECT
                        s.f_dttoclnt, -- 01.05.2024
                        (SELECT GROUP_CONCAT(sin.f_id) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_outbuhperiod=0) siid1_old,
                        #sdid3551
                        #(select GROUP_CONCAT(lct.f_objectid)
                        #                from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
                        #                                 veda_clients o,veda_contacts ct, veda_spec_invoices sin
                        #                where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
                        #                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 AND lsp.f_id=s.f_id AND lct.f_objectid=sin.f_id AND sin.f_outbuhperiod=0) siid2_old,
                        '' siid2_old,
                        #~sdid3551
                        (SELECT GROUP_CONCAT(sin.f_id) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_outbuhperiod=1) siid1_new,
                        (SELECT MAX(sin.f_dttmcr) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_outbuhperiod=1) new_agentrep_dt, -- 01.05.2024
                        #sdid3551
                        #(select GROUP_CONCAT(lct.f_objectid)
                        #                from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
                        #                                 veda_clients o,veda_contacts ct, veda_spec_invoices sin
                        #                where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
                        #                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 AND lsp.f_id=s.f_id AND lct.f_objectid=sin.f_id AND sin.f_outbuhperiod=1) siid2_new
                        '' siid2_new
                        #~sdid3551
                    FROM veda_specs s WHERE s.f_id=".$id;
            error_log("\n\n2 sbor opers issql_istorep = $sql\n\n", 3, "/var/www/html/veda/logs/test3551.log"); //sdid 3551
            $conn = $this->dbh->query($sql);
            $sids_old = "";
            $sids_new = "";
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
              {
              if(strlen($row['siid1_old']) > 0 && strlen($row['siid2_old']) > 0)
                {
                $sids_old = $row['siid1_old'] . "," . $row['siid2_old'];
                }
              elseif (strlen($row['siid1_old']) > 0)
                {
                $sids_old = $row['siid1_old'];
                }
              elseif (strlen($row['siid2_old']) > 0)
                {
                $sids_old = $row['siid2_old'];
                }
              if(strlen($row['siid1_new']) > 0 && strlen($row['siid2_new']) > 0)
                {
                $sids_new = $row['siid1_new'] . "," . $row['siid2_new'];
                }
              elseif (strlen($row['siid1_new']) > 0)
                {
                $sids_new = $row['siid1_new'];
                }
              elseif (strlen($row['siid2_new']) > 0)
                {
                $sids_new = $row['siid2_new'];
                }
              if(strlen($sids_old) === 0 && strlen($sids_new) === 0)
                {
                $response = [false, "Не найдены связанные операции"];
                return $response;
                }
              $rep_num = 1;
              // 20.11.2023
              $status = 0;
              if(strlen($sids_new) > 0)
                {
                $status = 1;
                }
              // ~ 20.11.2023
              if(strlen($sids_old) > 0)
                {
                $operations = explode(",", $sids_old);
                $new_agent_rep = [
                        'curtbl' => 286,
                        'f_specid' => $id,
                        'f_num' => $rep_num,
                        //'f_dttm' => date('Y-m-d H:i:s'), 01.05.2024
                        'f_dttm' => $row['f_dttoclnt'], // 01.05.2024
                        'f_status' => $status // 20.11.2023
                    ];
                $result = json_decode(addRowTbl($new_agent_rep));
                if($result[0])
                  {
                  $new_rep_id = $result[2];
                  foreach($operations as $operid)
                    {
                    $create_new_rep_oper = [
                            'curtbl' => 287,
                            'f_agentrepid' => $new_rep_id,
                            'f_operid' => $operid
                        ];
                    $result = addRowTbl($create_new_rep_oper);
                    }
                  $rep_num++;
                  //$result = $this->createAgentRepFile(15, $id, $new_rep_id, $rep_num, 1); //sdid 3496
                  $result = $this->createAgentRepFile1(15, $id, $new_rep_id, $rep_num, 1,true,1); //sdid 3496
                  //$messages[] = $result;
                  }
                }
              if(strlen($sids_new) > 0)
                {
                $operations = explode(",", $sids_new);
                $new_agent_rep = [
                        'curtbl' => 286,
                        'f_specid' => $id,
                        'f_num' => $rep_num,
                        //'f_dttm' => date('Y-m-d H:i:s'), 01.05.2024
                        'f_dttm' => $row['new_agentrep_dt'], // 01.05.2024
                        'f_status' => 0
                    ];
                $result = json_decode(addRowTbl($new_agent_rep));
                if($result[0])
                  {
                  $new_rep_id = $result[2];
                  foreach($operations as $operid)
                    {
                    $create_new_rep_oper = [
                        'curtbl' => 287,
                        'f_agentrepid' => $new_rep_id,
                        'f_operid' => $operid
                    ];
                    $result = addRowTbl($create_new_rep_oper);
                    }
                  //$result = $this->createAgentRepFile(15, $id, $new_rep_id, $rep_num, 2); //sdid 3496
                  $result = $this->createAgentRepFile1(15, $id, $new_rep_id, $rep_num, 2,true,1); //sdid 3496
                  }
                }
              }

//            foreach($data as $spec_id => $dataArray)
//            {
//                foreach ($dataArray['opers'] as $operid)
//                {
//                    $create_new_rep_oper = [
//                        'curtbl' => 287,
//                        'f_agentrepid' => $dataArray['rep_id'],
//                        'f_operid' => $operid
//                    ];
//
//                    $result = json_decode(addRowTbl($create_new_rep_oper));
//
//                    if ($result[0])
//                    {
//                        $messages[] = "Успешно создан отчет-операция ид ". $result[2];
//                    }
//                    else
//                    {
//                        $messages[] = "Ошибка создания счет-операции по f_agentrepid=".$dataArray['rep_id']." f_operid=".$operid;
//                    }
//                }
//
////                $messages[] = " -> " . $oprst . "; " . implode(',', $dataArray['opers']) . "; " . $dataArray['rep_id'] . " <- ";
//
////                $result = $this->createAgentRepFile($oprst, implode(',', $dataArray['opers']), $dataArray['rep_id'], $dataArray['rep_num']);
////                $messages[] = $result;
//            }
        }
        else
        {
            $response = [false, "Неверные параметры: " . $id];
        }

        if ($response[0])
        {
            $response[1] = $messages;
        }

        return $response;
    }
//sdid 3339
    public function createAgentRepBySpec2($id = 0)
      {
      $messages = [];
      $operations_old = [];
      $operations_new = [];
      $response = [true, ""];
      $data = [];
      if($id > 0)
        {
        $sql_duplicate_check = "SELECT COUNT(f_id) cnt FROM veda_agentreps WHERE f_num=1 AND f_specid=".$id;
        $conn_check = $this->dbh->query($sql_duplicate_check);
        if($row_check = $conn_check->fetch(PDO::FETCH_ASSOC))
          {
          if($row_check['cnt'] > 0) {return [false, "Основной отчет уже есть и привязан к спецификации"];}
          }
        $sql = "SELECT
                    s.f_dttoclnt, 
                    (SELECT GROUP_CONCAT(sin.f_id) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_dttmcr<=s.f_dttoclnt) siid1_old,
                    #sdid3551
                    #(select GROUP_CONCAT(lct.f_objectid)
                    #                from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
                    #                                 veda_clients o,veda_contacts ct, veda_spec_invoices sin
                    #                where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
                    #                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 AND lsp.f_id=s.f_id AND lct.f_objectid=sin.f_id AND sin.f_dttmcr<=s.f_dttoclnt) siid2_old,
                    '' siid2_old,
                    #~sdid3551
                    (SELECT GROUP_CONCAT(sin.f_id) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_dttmcr>s.f_dttoclnt) siid1_new,
                    (SELECT MAX(sin.f_dttmcr) FROM veda_spec_invoices sin WHERE sin.f_specid=s.f_id AND sin.f_dttmcr>s.f_dttoclnt) new_agentrep_dt, 
                    #sdid3551
                    #(select GROUP_CONCAT(lct.f_objectid)
                    #                from veda_spr spr,veda_specs lsp,veda_categs lct,veda_dogs ld,veda_clients cl,
                    #                                 veda_clients o,veda_contacts ct, veda_spec_invoices sin
                    #                where spr.f_type=33 and spr.f_num=lsp.f_typez and ct.f_id=lsp.f_contactid and cl.f_id=ld.f_contrid and
                    #                        o.f_id=ld.f_orgid and ld.f_id=lsp.f_dogid and lsp.f_id=lct.f_valstr and lct.f_ctgtype=24 and lct.f_objecttype=5 AND lsp.f_id=s.f_id AND lct.f_objectid=sin.f_id AND sin.f_dttmcr>s.f_dttoclnt) siid2_new
                    '' siid2_new
                    #~sdid3551
                FROM veda_specs s WHERE s.f_id=".$id;
        error_log("\n\n3 sbor opers issql_istorep = $sql\n\n", 3, "/var/www/html/veda/logs/test3551.log"); //sdid 3551
        $conn = $this->dbh->query($sql);
        $sids_old = "";
        $sids_new = "";
        if($row = $conn->fetch(PDO::FETCH_ASSOC))
          {
          if(strlen($row['siid1_old']) > 0 && strlen($row['siid2_old']) > 0) {$sids_old = $row['siid1_old'] . "," . $row['siid2_old'];}
          elseif(strlen($row['siid1_old']) > 0) {$sids_old = $row['siid1_old'];}
          elseif(strlen($row['siid2_old']) > 0) {$sids_old = $row['siid2_old'];}

          if(strlen($row['siid1_new']) > 0 && strlen($row['siid2_new']) > 0) {$sids_new = $row['siid1_new'] . "," . $row['siid2_new'];}
          elseif(strlen($row['siid1_new']) > 0) {$sids_new = $row['siid1_new'];}
          elseif(strlen($row['siid2_new']) > 0) {$sids_new = $row['siid2_new'];}

          if(strlen($sids_old) === 0 && strlen($sids_new) === 0) {$response = [false, "Не найдены связанные операции"]; return $response;}
          $rep_num = 1;
          $status = 0;
          //if(strlen($sids_new) > 0) {$status = 1;}
          if(strlen($sids_old) > 0)
            {
            $operations = explode(",", $sids_old);
            $new_agent_rep = [
                'curtbl' => 286,
                'f_specid' => $id,
                'f_num' => $rep_num,
                'f_dttm' => $row['f_dttoclnt'], 
                'f_status' => $status,
                'f_dtrep' => $row['f_dttoclnt']
            ];
            $result = json_decode(addRowTbl($new_agent_rep));
            if($result[0])
              {
              $new_rep_id = $result[2];
              foreach($operations as $operid)
                {
                $create_new_rep_oper = [
                    'curtbl' => 287,
                    'f_agentrepid' => $new_rep_id,
                    'f_operid' => $operid
                ];
                $result = addRowTbl($create_new_rep_oper);
                }
              $rep_num++;
              //$result = $this->createAgentRepFile(15, $id, $new_rep_id, $rep_num, 1); //sdid 3496
              $result = $this->createAgentRepFile1(15, $id, $new_rep_id, $rep_num, 1,true,1); //sdid 3496
              }
            }

          if(strlen($sids_new) > 0)
            {
            $operations = explode(",", $sids_new);
            $new_agent_rep = [
                'curtbl' => 286,
                'f_specid' => $id,
                'f_num' => $rep_num,
                'f_dttm' => $row['new_agentrep_dt'], 
                'f_status' => 0//,
                //'f_dtrep' => "0000-00-00"

            ];
            $result = json_decode(addRowTbl($new_agent_rep));
            if($result[0])
              {
              $new_rep_id = $result[2];
              foreach($operations as $operid)
                {
                $create_new_rep_oper = [
                    'curtbl' => 287,
                    'f_agentrepid' => $new_rep_id,
                    'f_operid' => $operid
                ];
                $result = addRowTbl($create_new_rep_oper);
                }
              //$result = $this->createAgentRepFile(15, $id, $new_rep_id, $rep_num, 2); //sdid 3496
              $result = $this->createAgentRepFile1(15, $id, $new_rep_id, $rep_num, 2,true,1); //sdid 3496
              }
            }
          }
        }
      else {$response = [false, "Неверные параметры: " . $id];}
      if($response[0]) {$response[1] = $messages;}
      return $response;
      }
//~sdid 3339

    public function getVozmNevozmTotalSums($oprst, $oprid, $rep_id, $rep_num, $old_new_flag=0)
    {

        $opers = "0";

        $sql = "SELECT ifnull(GROUP_CONCAT(aro.f_operid),0) opers FROM veda_agentreps_opers aro WHERE aro.f_agentrepid=".$rep_id;
        $conn = $this->dbh->query($sql);
        if ($row = $conn->fetch(PDO::FETCH_ASSOC))
        {
            $opers = $row['opers'];
        }

        $old_new = "";
        $and_sql = "";
        if ($old_new_flag == 1)
        {
            $old_new = "f_outbuhperiod=0 ";
            $and_sql = " AND ";
        } elseif ($old_new_flag == 2)
        {
            $old_new = "f_outbuhperiod=1 ";
            $and_sql = " AND ";
        }

        $isdrep = "";
        if(($oprst==35)||($oprst==145))
        {$isdrep = $oprid;}

        $wphpword=1;

        if(($oprst==35)||($oprst==145))
        {$oprid = "select distinct(f_specid) from ".DBPref."spec_invoices where f_id in (".$oprid.") ";}
        $sql ="select s.f_subtype ssubtype,d.f_subtype dogsubtype,d.f_dogname,d.f_dogdate,d.f_contrid,d.f_orgid,s.f_num,s.f_dt,s.f_typez, ".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_weco=2),0) weco,".
            "  ifnull((SELECT count(*) FROM ".DBPref."dt dt WHERE dt.f_specid in (select f_id from ".DBPref."specs where f_id=s.f_id or (f_parentspecid=s.f_id and f_subtype=3)) and dt.f_status<>17 AND dt.f_zayavnum IS NOT NULL AND dt.f_zayavnum != ''),0) shipment_before_dt,
                 d.f_onestrdocs onestrdocs,".
            "  (select f_name from ".DBPref."spr where f_type=39 and f_num=d.f_city) dogcity, ".
            "  c.f_addname contrname,c.f_inn contrinn,c.f_kpp contrkpp,".
            "  case when s.f_dtreptocl is null or s.f_dtreptocl='0000-00-00' then ifnull(DATE_FORMAT(s.f_dttoclnt,'%d.%m.%Y'),'') else ifnull(DATE_FORMAT(s.f_dtreptocl,'%d.%m.%Y'),'') end dttoclnt,".
            "  concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=o.f_opf),' ',o.f_cname) orgFname,o.f_opf,o.f_addname orgname,o.f_inn orginn,o.f_kpp orgkpp,o.f_sno osno ".
            "from ".DBPref."specs s,".DBPref."dogs d,".DBPref."clients o,".DBPref."clients c  ".
            "where d.f_id=s.f_dogid and o.f_id=d.f_orgid and c.f_id=d.f_contrid and s.f_id in (".$oprid.")";
        $res = $this->dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
            $dogsubtype = $row["dogsubtype"];

            if(strlen($isdrep)>0){$isdrep = " and s.f_id in (".$isdrep.") ";}
            $dtsost = $row['dttoclnt'];
            if(strlen($isdrep)>0)
            {
                $sql = "select ifnull(DATE_FORMAT(s.f_dttmcr,'%d.%m.%Y'),'') dttoclnt from ".DBPref."spec_invoices s where s.f_id IN (".$opers.") order by s.f_dttmcr desc";
                $res1 = $this->dbh->query($sql);
                if($row1 = $res1->fetch(PDO::FETCH_ASSOC)){$dtsost = $row1['dttoclnt'];}
            }
            $cstr = 15;
            //собираем оступившие платежные поурчения
            $sdtsost = "";
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
            {$sdtsost = " and h.f_ppdt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}
            $sql1 = "select h.f_ppnum,h.f_ppdt,h.f_sum,h.f_val,
                     (select f_uslstr from ".DBPref."spr where f_type=4 and f_num=h.f_val) val,
                     (select sum(f_clssum) from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                       (".$opers.") and f_acchistid=h.f_id) clssum
                   from ".DBPref."acchist h where f_type=0 and f_contrid=".$row['f_contrid']." and f_id in
                     (select f_acchistid from ".DBPref."acchist_docs where f_doctype=3 and f_docid in
                       (".$opers.")) ".$sdtsost;
            //echo "<br>$opers<br>$sql1<br>";
            $res1 = $this->dbh->query($sql1);
            $ps = 0;$psv="";
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
                $ps=$ps+$row1['clssum'];
                $cstr++;
            }
            $cstr++;
            $vozmsum = 0;
            $vozmkol = 0;
            if((strlen($dtsost)>0)&&(strcmp($dtsost,"00.00.0000")!==0))
            {$sdtsost = " and s.f_dttmcr<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ";}

            $letter = "";
            if ($old_new_flag > 0)
            {
                $letter = " vssi.";
            }
            if(!isset($letter2)){$letter2="";}
            $sql1 = "select
                     get_rrsum(s.f_id,0) rrsum,
                     (select f_name from veda_spr where f_type=4 and f_num=h.f_val) hvaln,
                     (select f_curs from veda_acchist_docs where f_acchistid=h.f_id and f_doctype=3 and f_docid=s.f_id limit 1) ddcurs,
                     ifnull((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
                      where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') salevaldt,
                     ifnull((select lah.f_dt1C
                      from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto
                      where vsto.f_c1doctype=4 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and
                        lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'') bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     case
                       when t.f_c1doctype=1 and s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                         where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0 then #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                         case
                           when DATE_FORMAT(h.f_dt1C,'%d.%m.%Y')=DATE_FORMAT((select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1),'%d.%m.%Y') then -999999999.999
                           else round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-getcbrate(h.f_val,(select lah.f_dt1C from veda_acchist lah,veda_acchist_docs lahd,veda_spec_invoices vssi,veda_typeopers vsto where vsto.f_c1doctype=18 ".$and_sql.$letter.$old_new." and  vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id and lahd.f_doctype=3 and lahd.f_docid=vssi.f_id and lah.f_id=lahd.f_acchistid and lah.f_type=1 limit 1))) AS DECIMAL(15,2)),2)
                         end
                       else -999999999.999
                     end vozmoplinvalwsaleval,
                     case
                       when t.f_c1doctype=18 and s.f_bdrarticle>0 and s.f_isvozm=1 and h.f_val<>643
                            and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                         where vsto.f_c1doctype=1 and vsto.f_id=vssi.f_idoper and vssi.f_id=s.f_parentid),0))>0 then #операции возмещаемой продажи валюты с наличием связанной оплаты в валюте
                         round(cast(h.f_sum*(getcbrate(h.f_val, h.f_dt1C)-h.f_cursoper) AS DECIMAL(15,2)),2)
                       else -999999999.999
                     end vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                     #sdid2295
                     case
                       when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг
                         'Акт выполненных работ'
                       else
                         (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype)
                     end
                     #~sdid2295
                  zdoctype, ".
                "  case ".
                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then ".
                "      concat((select f_name from ".DBPref."nomenk where f_id=t.f_nomenkid),'. ',ifnull(s.f_invcom,'')) ".
                //sdid2295
                "
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and
                          (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг
                       ''
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs=1 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
                "      case when s.f_sum=0 then ".
                "        ifnull((select round(cast(f_rate as decimal(15,5)),4) from ".DBPref."cbrates where f_val=s.f_val and f_dt=(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),1) else round(cast(h.f_cursoper as decimal(15,5)),4) end) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                //~sdid2295
                "    when t.f_c1doctype=4 and s.f_invoiceid>0 and d.f_curs>1 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val),', курс ',".
                "                round(cast(d.f_curs as decimal(15,5)),4) ) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum=0 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(i.f_sum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    when t.f_c1doctype=5 and s.f_invoiceid>0 and d.f_clssum>0 then ".
                "      (select concat('(',(SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),'), инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y'),' на сумму ',format(round(cast(d.f_clssum as decimal(15,3)),2),2,'ru_RU'),' ',(select f_namedop from ".DBPref."spr where f_type=4 and f_num=i.f_val)) from ".DBPref."schets i where i.f_id=s.f_invoiceid) ".
                "    else '' ".
                "  end dopn, ".
                "  case ".
                "    when t.f_nomenkid in (2,24) and t.f_c1doctype=3 then '' ".
                //sdid2295
                "
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and
                          (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then #услуга в долг
                       concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' ',
                              (select f_name from ".DBPref."spr where f_type=4 and
                                 f_num=(select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),
                              ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                              (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),' г. - ',
                              getcbrate((select f_val from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1),
                                        (select f_dt from ".DBPref."akts where f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and f_type=23 limit 1)),'(',
                              (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                ".
                //~sdid2295
                "    when (t.f_c1doctype=4 or t.f_c1doctype=5 or t.f_c1doctype=18) then ".
                "      (select f_name from ".DBPref."spr where f_type=27 and f_num=t.f_c1doctype)
                       else t.f_name ".
                "  end nop,".
                "  s.f_sum,s.f_val,s86.f_name,1 tp,".
                "  case 
                    #sdid2295 услуга в долг
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                       (select la.f_num from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295
                ".
                "    when t.f_zdoctype=3 then 
                       #sdid3300
                       #sdid 3601
                       #case
                       #  when s.f_dtid>0 then 
                       #    (select f_TDnum from veda_dt where f_id=s.f_dtid)
                       #  else dt.f_TDnum 
                       #end 
                       case
                         when s.f_dtid>0 then 
                           (select f_TDnum from veda_dt where f_id=s.f_dtid)
                         else ifnull((select f_TDnum from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                       end 
                       #~sdid 3601
                       #~sdid3300
                     when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
                "      ),0)>0 then ".
                "      (select f_ppnum from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
                "      ) ".
                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
                "      h.f_ppnum ".
                "    else '' ".
                "  end ppnum,".
                "  case 
                     #sdid 3550
                     when t.f_type in (3, 25, 44, 45, 52, 56, 58, 59, 73, 144, 150, 179) then
                       #sdid 3601
                         #case 
                         #  when length(dt.f_TDnum)>3 then
                         #    ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX(dt.f_TDnum,'/',-2),'/',1),'%d%m%y'),'')
                         #  else dt.f_TDdt
                         #end
                       #-------------------
                         case
                           when s.f_dtid>0 then 
                             case 
                               when (select length(f_TDnum) from veda_dt where f_id=s.f_dtid)>3 
                                 then ifnull(STR_TO_DATE(SUBSTRING_INDEX( SUBSTRING_INDEX((select f_TDnum from veda_dt where f_id=s.f_dtid),'/',-2),'/',1),'%d%m%y'),'')
                                 else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                               end 
                           else
                             ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                         end
                         #----------------- 
                       #~sdid 3601

                     #~sdid 3550
                    #sdid2295 услуга в долг
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                       (select la.f_dt from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295
                ".
                "    when t.f_zdoctype=3 then 
                       #sdid3300
                       #sdid 3601
                       #case
                       #  when s.f_dtid>0 then 
                       #    (select f_TDdt from veda_dt where f_id=s.f_dtid)
                       #  else dt.f_TDdt
                       #end 
                       case
                         when s.f_dtid>0 then 
                           (select f_TDdt from veda_dt where f_id=s.f_dtid)
                         else ifnull((select f_TDdt from veda_dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'')
                       end 
                       #~sdid 3601
                       #~sdid3300
                     when h.f_ahtype=3 and ifnull((select count(*) from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "      where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59'".
                "        ),0)>0 then ".
                "      (select f_ppdt from ".DBPref."acchist lh,".DBPref."acchist_docs lhd ".
                "       where lhd.f_acchistid=lh.f_id and lhd.f_doctype=3 and lh.f_ahtype=4 and lhd.f_docid=s.f_id ".
                "         and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' limit 1".
                "      ) ".
                "    when h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' then ".
                "      h.f_ppdt ".
                "    else '' ".
                "  end ppdt,".
                "  case ".
                "    when t.f_zdoctype=3 then 'ФТС' 
                    #sdid2295 услуга в долг
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                       (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295
                ".
                "    when t.f_c1doctype in (4,5,18) then ".
                "      (select lc.f_cname from ".DBPref."dogs ld,".DBPref."clients lc where lc.f_id=ld.f_contrid and ld.f_dogtype=10 ".
                "      and ld.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=5 and f_valstr=h.f_orgbic1C) limit 1) ".
                "    when t.f_c1doctype=6 then ".
                "      (select f_bankname from ".DBPref."banks where f_bic=h.f_contrbic1C limit 1) ".
                "    else h.f_name ".
                "  end contrn,".
                "  case
                    #sdid2295 услуга в долг
                     when t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0 then
                        (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=(select f_id from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id) and la.f_type=23 limit 1)
                     #~sdid2295
                       when t.f_c1doctype=4 and s.f_sum=0 then 0
                       when t.f_c1doctype=4 and d.f_curs>1 and (select count(*) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."')>0 then
                         CAST((IFNULL((select sum(f_sum) from ".DBPref."akts where f_type=23 and f_operid=s.f_id and f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."'),0)*d.f_curs) AS DECIMAL(15,2))
                       when t.f_c1doctype=4 and d.f_curs>1 and (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>1
                         then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
                       when (select ifnull(count(*),0) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                             where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                               ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)>0 then
                         (select sum(ahd.f_clssum) from veda_acchist ah,veda_acchist_docs ahd,veda_acchist ah1,veda_acchist_docs ahd1
                          where ah.f_id=ahd.f_acchistid and ah1.f_id=ahd1.f_acchistid and ahd.f_doctype=3 and ahd1.f_doctype=ahd.f_doctype and ahd1.f_docid=ahd.f_docid and
                            ah.f_grnum=ah1.f_grnum and ah.f_grdt=ah1.f_grdt and ah.f_ahtype=4 and ah1.f_ahtype=3 and ahd.f_docid=s.f_id)
                       when d.f_curs>1 then round(CAST(d.f_clssum*d.f_curs AS DECIMAL(15,3)),2)
                       else d.f_clssum
                     end clssum
                     #sdid 1552
                     ,-1 korrazn
                     ,'' na_name
                     ,'' na_dt
                     ,'' na_num
                     ,1 blockid
                     #~sdid 1552
		     #sdid 1583
 		     ,s.f_idoper
                     ,'' nop1
                     ,'' nop2
                     ,'' sumnop1
                     ,'' sumnop2
		     #~sdid 1583
                   from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."acchist_docs d,".DBPref."acchist h,".DBPref."typeopers t,".DBPref."spec_invoices s
                     left join ".DBPref."dt as dt on dt.f_specid=s.f_specid
                   where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and s85.f_type=85 and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and
                     s.f_parenttype=2 and s.f_id in (".$opers.") and h.f_ahtype<>4 and
                     ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) and #нет услуги в долг
                     ((s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1)
                      or (t.f_c1doctype in (4,5) and s.f_isvozm=1)
                      #sdid2295
                      #or (t.f_c1doctype=4 and s.f_invoiceid>0 and (select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)>0) #услуга в долг
                      #~sdid2295
                      or (s.f_bdrarticle>0 and s85.f_dopprint=1 and s.f_isvozm=1 and h.f_val<>643
                          and (ifnull((select count(*) from veda_spec_invoices vssi,veda_typeopers vsto
                                       where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0))>0) #операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                      or (t.f_c1doctype=18 and s.f_isvozm=1) #возмещаемая продажа валюты
                     ) ".$sdtsost.
                "  and d.f_docid=s.f_id and d.f_doctype=3 and h.f_id=d.f_acchistid ".//and h.f_dt1C<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)." 23:59:59' ".
                //sdid2295 Услуга в долг
                "
                 union
                 select
                   get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                   '' salevaldt,
                   '' bayvaldt,
                   ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                          where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                   ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                          where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                   -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                   s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                   case
                     when s.f_idoper=389 then 'Акт выполненных работ'
                     else ''
                   end zdoctype,
                   case
                     when s.f_idoper=389 then
                       concat('Неоплаченная сумма за услуги ',(select f_sum from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' ',
                              (select f_name from ".DBPref."spr where f_type=4 and
                                 f_num=(select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),
                              ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                              (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                              getcbrate((select f_val from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),
                                        (select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                              (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                     else ''
                   end nop,
                   case
                     when s.f_idoper=389 then ''
                     else ''
                   end dopn,
                   s.f_sum,s.f_val,s86.f_name,0 tp,
                   case
                     when s.f_idoper=389 then
                       (select la.f_num from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end ppnum,
                   case
                     when s.f_idoper=389 then
                       (select la.f_dt from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end ppdt,
                   case
                     when s.f_idoper=389 then
                       (select lc.f_cname from ".DBPref."akts la,".DBPref."clients lc where lc.f_id=la.f_contrid and la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else ''
                   end contrn,
                   case
                     when s.f_idoper=389 then
                       (select la.f_sum*getcbrate(la.f_val,la.f_dt) from ".DBPref."akts la where la.f_operid=s.f_id and la.f_type=23 limit 1)
                     else 0
                   end clssum
                   ,-1 korrazn
                   ,'' na_name
                   ,'' na_dt
                   ,'' na_num
                   ,4 blockid
                   -- bugfix 30.05.2024
                   ,s.f_idoper
                   ,'' nop1
                   ,'' nop2
                   ,'' sumnop1
                   ,'' sumnop2
                   -- ~ bugfix 30.05.2024
                 from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s
                 where t.f_id=s.f_idoper
                   and s86.f_type=86
                   and s85.f_type=85
                   and s85.f_dopprint=2
                   and s85.f_num=s86.f_uslint
                   and s86.f_num=s.f_bdrarticle
                   and s.f_parenttype=2
                   and s.f_specid in (".$oprid.") and s.f_id in (".$opers.")
                   and s.f_bdrarticle>0
                   and s.f_idoper=389
                ".
                //~sdid2295
                //!!!не привязанные к банковским выпискам с расходными статьями бюджета
                "union ".
                "select
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,".
                "  case ".
                //отбираем покупки валюты, по отпущенным в долг товарам
                "    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then '-' ".
                "    else ".
                "      (select f_name from ".DBPref."spr where f_type=120 and f_num=t.f_zdoctype) ".
                "  end zdoctype, ".
                "  case ".
                //отбираем покупки валюты, по закрытым в долг услугам - акты от иностранного поставщика
                "
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then
                         concat('Неоплаченная сумма за услуги ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату оказания услуги ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                  ".
                //отбираем покупки валюты, по отпущенным в долг товарам по дате ППС
                "    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then ".
                "      concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),".
                "             ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',".
                "             (select DATE_FORMAT(f_perpravdt,'%d.%m.%Y') from ".DBPref."specs where f_id=s.f_specid),' г. - ',".
                "             getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid)),'(',".
                "             (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')') ".
                //отбираем покупки валюты, по закрытым в долг товарам по документу - Товар от иностранного поставщика
                "
                       when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=25)>0 then
                         concat('Неоплаченная сумма за товар ',s.f_sum,' ',(select f_name from ".DBPref."spr where f_type=4 and f_num=s.f_val),
                                ' для закрытия расчетов с принципалом выставляется по курсу ЦБ РФ на дату перехода права собственности ',
                                (select DATE_FORMAT(f_dt,'%d.%m.%Y') from ".DBPref."akts where f_operid=s.f_id and f_type=25 limit 1),' г. - ',
                                getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1)),'(',
                                (select concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid),' инвойс № ',i.f_num,' от ',DATE_FORMAT(i.f_dt,'%d.%m.%Y')) from ".DBPref."schets i where i.f_id=s.f_invoiceid),')')
                  ".
                //отбираем по детализации актов
                "    when (select count(*) from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' ".
                "          and ad.f_operid=s.f_id )>0 then ".
                "      (select f_grnd from ".DBPref."akts_details where f_id in (select f_akts_detailsid from ".DBPref."akts_details_opers ad,".DBPref."akts_details akd,".DBPref."akts ak ".
                "        where ad.f_akts_detailsid=akd.f_id and akd.f_aktid=ak.f_id and ak.f_dt<='".substr($dtsost,6,4)."-".substr($dtsost,3,2)."-".substr($dtsost,0,2)."' and ad.f_operid=s.f_id) limit 1) ".
                "    else concat(t.f_name,'. ',ifnull(s.f_invcom,'')) ".
                "  end nop, ".
                "  case ".
                "    when t.f_c1doctype=4 and s.f_invoiceid>0 then '' ".
                "    else '' ".
                "  end dopn,".
                "  s.f_sum,s.f_val,s86.f_name,0 tp,".
                "  case when t.f_zdoctype=3 then 
                     #sdid3300
                     case
                       when s.f_dtid>0 then 
                         (select f_TDnum from veda_dt where f_id=s.f_dtid)
                       else ifnull((select f_TDnum from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                     end 
                     #~sdid3300
                   else '' end ppnum,".
                "  case when t.f_zdoctype=3 then 
                     #sdid3300
                     case
                       when s.f_dtid>0 then 
                         (select f_TDdt from veda_dt where f_id=s.f_dtid)
                       else ifnull((select f_TDdt from ".DBPref."dt where f_specid=s.f_specid and length(f_TDnum)>3 limit 1),'') 
                     end 
                     #~sdid3300
                   else '' end ppdt,".
                "  case when t.f_zdoctype=3 then 'ФТС' ".
                "    when t.f_zdoctype in (4,6) then ".
                "      concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=(SELECT f_opf FROM ".DBPref."clients where f_id=s.f_contrid)),' ',(select f_cname from ".DBPref."clients where f_id=s.f_contrid)) ".
                "  else '' end contrn,".
                //отбираем покупки валюты, по отпущенным в долг товарам
                "  case
                     when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."akts where f_operid=s.f_id and f_type=23)>0 then
                         cast((s.f_sum*getcbrate(s.f_val,(select f_dt from ".DBPref."akts where f_operid=s.f_id and f_type=23 limit 1))) AS DECIMAL(15,2))
                     when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_invoiceid>0 and (select count(*) from ".DBPref."specs where f_id=s.f_specid and f_perpravdt is not null)>0 then ".
                "      cast((s.f_sum*getcbrate(s.f_val,(select f_perpravdt from ".DBPref."specs where f_id=s.f_specid))) AS DECIMAL(15,2)) ".
                "    else s.f_sum ".
                "  end clssum
                  #sdid 1552
                  ,(ifnull((select sum(nd.f_sum) from veda_netting_agr_details nd where nd.f_operid2=s.f_id),0)-s.f_sum) korrazn
                  ,'' na_name
                  ,'' na_dt
                  ,'' na_num
                  ,2 blockid
                  #~sdid 1552
		  #sdid 1583
 		  ,s.f_idoper,
		  case
                  # Курсовая разница при приобретении валюты для расчетов с поставщиком товара
                    when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
		         and (select ifnull(si0.f_invoiceid,0)
			        from veda_spec_invoices si1, veda_spec_invoices si0
			       where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
                         and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
		      then
			 (select concat('Курсовая разница при приобретении валюты для расчетов с поставщиком товара ',
			                s.f_sum, ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
			                ' курс покупки валюты ',
			                s.f_cursoper,
			                ' курс ЦБ на дату покупки валюты ',
					(getcbrate(s.f_val, s.f_dttmcr)),
					' Расчет курсовой разницы: (',
					s.f_cursoper,
					' - ',
					(getcbrate(s.f_val, s.f_dttmcr)),
					') * ',
					s.f_sum,
					' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					' = ',
					#s.f_sum,
					ROUND(CAST(((s.f_cursoper-getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2),
					' руб. (',
					cl2.f_cname, ' инвойс № ', i.f_num, ' от ', DATE_FORMAT(i.f_dt,'%d.%m.%Y'),
					')'
			               )
			    from veda_schets i, veda_spec_invoices si1, veda_spec_invoices si0, veda_dogs d2, veda_clients cl2
				 where i.f_id=si0.f_invoiceid
				   and si1.f_id=s.f_parentid
				   and si0.f_id=si1.f_parentid
				   and d2.f_id=i.f_dogid
				   and cl2.f_id=d2.f_contrid
			 )
		      else ''
		    end nop1,

                    case
                      # Курсовая разница полученная в следствии изменения курса ЦБ РФ с даты приобретения валюты на дату осуществления платежа
                        when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
		             and (select ifnull(si0.f_invoiceid,0)
		                    from veda_spec_invoices si1, veda_spec_invoices si0
			           where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
			  	     and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
                        then
			 (select ifnull(concat('Курсовая разница полученная в следствии изменения курса ЦБ РФ',
		                               ' с даты приобретения валюты на дату осуществления платежа ',
					       s.f_sum, ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					       ' курс ЦБ на дату покупки валюты ',
					       getcbrate(s.f_val, s.f_dttmcr),
					       ' курс ЦБ на дату осуществления платежа ',
					       getcbrate(sis.f_val,sis.f_dttmcr),
					       ' Расчет курсовой разницы: (',
					       getcbrate(s.f_val, s.f_dttmcr),
					       ' - ',
					       getcbrate(sis.f_val,sis.f_dttmcr),
					       ') * ',
					       s.f_sum,
					       ' ', (select f_namedop from veda_spr where f_type=4 and f_num=s.f_val),
					       ' = ',
					       ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2),
					      ' руб.'
					      ),'')
			    from veda_spec_invoices si1, veda_spec_invoices si0, veda_spec_invoices sis
				 where si1.f_id=s.f_parentid
					 and si0.f_id=si1.f_parentid
					 and si1.f_idoper in (385,387)
					 and sis.f_parentid=s.f_parentid
					 and sis.f_dttmcr>s.f_dttmcr
					 and sis.f_sum=s.f_sum
					 and sis.f_idoper=54
					 and ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)<>0 LIMIT 1)
                      else ''
                    end nop2,

		    case
                    # Сумма Курсовой разницы при приобретении валюты для расчетов с поставщиком товара
                      when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
			   and (select ifnull(si0.f_invoiceid,0)
			          from veda_spec_invoices si1, veda_spec_invoices si0
				 where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
                           and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
			then
			  ROUND(CAST(((s.f_cursoper-getcbrate(s.f_val, s.f_dttmcr))*s.f_sum) AS DECIMAL(15,3)),2)
		        else ''
		    end sumnop1,

                    case
                      # Сумма курсовй разницы полученная в следствии изменения курса ЦБ РФ с даты приобретения валюты на дату осуществления платежа
                      when t.f_c1doctype=4 and s.f_parenttype=2 and s.f_idoper=53
                           and (select ifnull(si0.f_invoiceid,0)
	                          from veda_spec_invoices si1, veda_spec_invoices si0
	                         where si1.f_id=s.f_parentid and si0.f_id=si1.f_parentid)>0
		           and (select ifnull(si1.f_idoper,0) from veda_spec_invoices si1 where si1.f_id=s.f_parentid) in (385,387)
                        then
                          (select ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)
		            from veda_spec_invoices si1, veda_spec_invoices si0, veda_spec_invoices sis
			   where si1.f_id=s.f_parentid
                             and si0.f_id=si1.f_parentid
                             and si1.f_idoper in (385,387)
                             and sis.f_parentid=s.f_parentid
                             and sis.f_dttmcr>s.f_dttmcr
                             and sis.f_sum=s.f_sum
                             and sis.f_idoper=54
                             and ROUND(CAST( (getcbrate(s.f_val, s.f_dttmcr)-getcbrate(sis.f_val,sis.f_dttmcr))*s.f_sum AS DECIMAL(15,3)),2)<>0 LIMIT 1)

                        else ''
                    end sumnop2
		#~sdid 1583
                  ".
                "from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."typeopers t,".DBPref."spec_invoices s ".
                "where t.f_id=s.f_idoper ".$isdrep." and s86.f_type=86 and  s85.f_type=85 ".$and_sql.$letter2.$old_new." and s85.f_num=s86.f_uslint and s86.f_num=s.f_bdrarticle and ".
                "  s.f_parenttype=2 and s.f_id  IN (".$opers.")  and ".
                "    s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1 ".$sdtsost.
                "  and (select count(*) from ".DBPref."acchist_docs where f_docid=s.f_id and f_doctype=3)=0 
                    #sdid2295
                   and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг
                   #~sdid2295
                ".
                "
                  union
                  select
                  ################################################
                     get_rrsum(s.f_id,0) rrsum,'' hvaln,0 ddcurs,
                     '' salevaldt,
                     '' bayvaldt,
                     ifnull((select sum(vssi.f_sum) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and vssi.f_parentid=s.f_id),0) valpaysellsumoper,
                     ifnull((select sum(get_rrsum(vssi.f_id,0)) from veda_spec_invoices vssi,veda_typeopers vsto
                            where vsto.f_c1doctype=18 and vsto.f_id=vssi.f_idoper and  vssi.f_parentid=s.f_id),0) valpaysellsum,
                     -999999999.999 vozmoplinvalwsaleval,-999999999.999 vozmsalevalwoplinval,
                     s.f_id sfid,t.f_kindzdoc,s.f_parenttype,s.f_specid,s.f_invoiceid,t.f_c1doctype,t.f_zdoctype,t.f_nomenkid,
                  ################################################
                     'Инвойс' zdoctype,
                     #s.f_id,
                     #nad.f_nettingagrid,
                     (concat(t.f_name, ' (', (select f_cname from veda_clients where f_id=na.f_contrid), '), инвойс № ',
                             ifnull((select concat (sch.f_num, ' от ', DATE_FORMAT(sch.f_dt,'%d.%m.%Y'), ' на сумму ',
                                                                round(cast(sch.f_sum AS DECIMAL(15,2)),2), ' ',
                     				                (select f_dopprstr from veda_spr where f_type=4 and f_num=sch.f_val))
                     				   from veda_schets sch
                     					where sch.f_id=s.f_invoiceid
                     				),''),
                                                ', курс ', case when nad.f_curs2=1 then getcbrate(s.f_val,na.f_dt) else nad.f_curs2 end,' на ', DATE_FORMAT(na.f_dt,'%d.%m.%Y')

                            )
                     ) nop,
                  ###############################################
                    case
                      when t.f_c1doctype=4 and s.f_invoiceid>0 then ''
                      else ''
                    end dopn,
                    s.f_sum,s.f_val,s86.f_name,0 tp,
                  ###############################################
                    (select sch.f_num from veda_schets sch where sch.f_id=s.f_invoiceid) ppnum,
                    (select DATE_FORMAT(sch.f_dt,'%d.%m.%Y') from veda_schets sch where sch.f_id=s.f_invoiceid) ppdt,
                    (select f_cname from veda_clients where f_id=na.f_contrid) contrn,
                    case na.f_val
                      when 643 then nad.f_sum
                    	else round(cast(nad.f_sum*getcbrate(na.f_val, na.f_dt) AS DECIMAL(15,2)),2)
                    end clssum
                     ,-1 korrazn
                     ,'Соглашение о зачете взаимных требований' na_name
                     ,DATE_FORMAT(na.f_dt,'%d.%m.%Y') na_dt
                     ,na.f_num na_num
                     ,3 blockid
		     #sdid 1583
 		     ,s.f_idoper
                     ,'' nop1
                     ,'' nop2
                     ,'' sumnop1
                     ,'' sumnop2
		     #~sdid 1583
                    from veda_spr s86,veda_spr s85,veda_typeopers t,veda_spec_invoices s, veda_netting_agr_details nad, veda_netting_agr na
                    where t.f_id=s.f_idoper
                    and s86.f_type=86
                    and s85.f_type=85
                    and s85.f_num=s86.f_uslint
                    and s86.f_num=s.f_bdrarticle
                    and s.f_parenttype=2
                    AND s.f_id  IN (".$opers.")
                    and s.f_bdrarticle>0 and s85.f_dopprint=2 and s.f_isvozm=1
                    #sdid2295
                    and ((select count(*) from ".DBPref."spec_invoices where f_idoper=389 and f_parentid>0 and f_parentid=s.f_id)=0 and s.f_idoper<>389) #нет услуги в долг
                    #~sdid2295
                    and nad.f_operid2=s.f_id
                    and na.f_id=nad.f_nettingagrid
                  ";
            //echo $sql1;
            $res1 = $this->dbh->query($sql1);
            while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
            {
                $rrvalsum   = 0;
                $needaddrow = 1;
                $clssum = number_format($row1['clssum'],2, ',', ' ');
                if(($row1['blockid']==2)&&($row1['korrazn']==0))
                {$needaddrow = 0;$clssum = 0;}
                elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']==-999999999.999)&&($row1['f_idoper']!=386)&&($row1['f_idoper']!=388))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты

                {$needaddrow = 0;$clssum = 0;}
                elseif(($row1['f_c1doctype']==1)&&($row1['f_val']<>643)&&($row1['vozmoplinvalwsaleval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                    $clssum = number_format($row1['vozmoplinvalwsaleval'],2, ',', ' ');
                    $ccbrate = getcbrate($row1['f_val'],$row1['salevaldt']);
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['f_val']<>643)&&($row1['valpaysellsumoper']>0)&&
                    ($row1['valpaysellsum']!=0)&&($row1['vozmoplinvalwsaleval']==-999999999.999))#операции покупки валюты не в день продажи
                {
                    $ccbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                    $clssum  = number_format(($row1['ddcurs']-$ccbrate)*$row1['f_sum'],2, ',', ' ');
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['clssum']==0)&&($row1['f_invoiceid']>0)&&
                    ($row1['valpaysellsumoper']<$row1['f_sum']))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше суммы операции
                {
                    if($row1['f_parenttype']==2)
                    {
                        $sql2 = "select f_perpravdt from ".DBPref."specs where f_id=".$row1['f_specid'];
                        $res2 = $this->dbh->query($sql2);
                        if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                        {
                            $cbdt = $row2['f_perpravdt'];
                            if(strcmp($cbdt,"0000-00-00")==0){}
                            else
                            {
                                $sql2 = "select f_sum,f_val from ".DBPref."schets where f_id=".$row1['f_invoiceid'];
                                $res2 = $this->dbh->query($sql2);
                                if($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                                {
                                    $row1['clssum']=round(($row2['f_sum']*getCBRate($row2['f_val'],$cbdt)),2);
                                    $clssum = number_format($row1['clssum'],2, ',', ' ');
                                }
                            }
                        }
                    }
                }
                elseif(($row1['f_c1doctype']==4)&&($row1['f_invoiceid']>0)&&
                    ($row1['valpaysellsumoper']>=$row1['f_sum']))//покупка валюты с непустым инвойсом и суммой продажи валюты меньше = сумме операции
                {$needaddrow = 0;$clssum = 0;}
                elseif(($row1['f_c1doctype']==4)&&($row1['clssum']>0)&&($row1['f_invoiceid']>0))
                {$clssum = number_format($row1['clssum'],2, ',', ' ');}
                elseif($row1['f_c1doctype']==5)
                {$clssum = "";$row1['clssum']=0;}
                elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']!=-999999999.999))#операции возмещаемой оплаты в валюте с наличием связанной продажи валюты
                {
                    $clssum = number_format($row1['vozmsalevalwoplinval'],2, ',', ' ');
                    $ccbrate = number_format(getcbrate($row1['f_val'],$row1['ppdt']),4, ',', ' ');
                }
                elseif(($row1['f_c1doctype']==18)&&($row1['f_val']<>643)&&($row1['vozmsalevalwoplinval']==-999999999.999)&&(strlen($row1['bayvaldt'])>0))#операции продажи валюты не в день покупки
                {
                    $row1['f_kindzdoc'] = 2;
                    $vbbrate = getcbrate($row1['f_val'],substr($row1['bayvaldt'],0,10));
                    $cbbrate = number_format($vbbrate,4, ',', ' ');
                    $vcbrate = getcbrate($row1['f_val'],$row1['ppdt']);
                    $ccbrate = number_format($vcbrate,4, ',', ' ');
                    $clssum  = number_format(($vcbrate-$row1['ddcurs'])*$row1['f_sum'],2, ',', ' ');
                    $rrvalsum = round(($vbbrate-$vcbrate)*$row1['f_sum'],2);
                    $rvalsum = number_format($rrvalsum,2, ',', ' ');
                }
                if($needaddrow==1)
                {
                    $klclssum = str_replace(' ','',str_replace(',','.',$clssum));
                    $vozmsum=$vozmsum+$rrvalsum;
                    if(is_numeric($klclssum))
                    {$vozmsum=$vozmsum+$klclssum;}
                    if(($row1['f_idoper']==53)&&(strlen($row1['nop1'])>0)&&($row1['f_c1doctype']==4)&&($row1['f_val']<>643))
                    {
                        if($needaddrow==1)
                        {
                            if(is_numeric($row1['sumnop1']))
                            {$vozmsum=$vozmsum+$row1['sumnop1'];}
                        }
                    }
                    if(($row1['f_idoper']==53)&&(strlen($row1['nop2'])>0)&&($row1['f_c1doctype']==4)&&($row1['f_val']<>643))
                    {
                        if($needaddrow==1)
                        {
                            if(is_numeric($row1['sumnop2']))
                            {$vozmsum=$vozmsum+$row1['sumnop2'];}
                        }
                    }
                    if(($row1['f_idoper']==386)||($row1['f_idoper']==388))
                    {
                        if($needaddrow==1)
                        {
                            if($wphpword==1)
                            {
                                $sql = "
                                  select #sum(sis.f_sum),
                                    sis.f_sum, sis.f_val, spr4.f_namedop valname, sis.f_dttmcr,
				          getcbrate(sis.f_val, sis.f_dttmcr) curs_send,
  		                    (getcbrate(sis.f_val,(select f_perpravdt from veda_specs where f_id=s.f_specid))) curs_pps,
				          ROUND(CAST((
				            (getcbrate(sis.f_val, sis.f_dttmcr)-
				            (getcbrate(sis.f_val,(select f_perpravdt from veda_specs where f_id=s.f_specid))))*
					     sis.f_sum) AS DECIMAL(15,3)),2) sum_delta,
				          (select concat(cl.f_cname, ' инвойс № ', i.f_num, ' от ', DATE_FORMAT(i.f_dt,'%d.%m.%Y'))
				             from veda_schets i,veda_dogs d,veda_clients cl
					    where i.f_id=si0.f_invoiceid and d.f_id=i.f_dogid and cl.f_id=d.f_contrid) invname
                                  from veda_spec_invoices sis,
	                               veda_spec_invoices s,
				             veda_spec_invoices si1,
				             veda_spec_invoices si0,
				             veda_spr spr4
                                 where sis.f_parentid=s.f_parentid
                                   and s.f_id=".$row1['sfid']."    #114876  #114872
	                           and sis.f_idoper=54
	                           and si1.f_id=s.f_parentid
	                           and si1.f_idoper in (385,387)
	                           and si0.f_id=si1.f_parentid
	                           and spr4.f_type=4 and spr4.f_num=sis.f_val
	                           and (case when (select count(f_dttmcr)
						           from veda_spec_invoices
							  where f_id<>s.f_id and f_idoper in (386,388) and f_parentid=s.f_parentid and f_dttmcr<s.f_dttmcr)>0 #is not null
						   then (select max(f_dttmcr)
							   from veda_spec_invoices
						          where f_id<>s.f_id and f_idoper in (386,388) and f_parentid=s.f_parentid and f_dttmcr<s.f_dttmcr)
						   else (select f_perpravdt from veda_specs where f_id=s.f_specid)
					       end
				             )<sis.f_dttmcr
                                   and sis.f_dttmcr <= s.f_dttmcr
                                 ";

                                $res2 = $this->dbh->query($sql);
                                while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
                                {
                                    if(is_numeric($row2['sum_delta']))
                                    {$vozmsum=$vozmsum+$row2['sum_delta'];}
                                }
                            }
                        }
                    }
                    //~sdid 1583
                }
            }
            $nvozmsum = 0;

            $sql1 = "select s.f_sum,
                     concat(t.f_name,'. ',ifnull(s.f_invcom,'')) nop
                   from ".DBPref."spec_invoices s,".DBPref."typeopers t
                   where t.f_id=s.f_idoper and s.f_parenttype=2 ".$isdrep." and s.f_id  IN (".$opers.")  and
                     #sdid 637
                     #(s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298)
                     (s.f_sub_type_oper=0 or s.f_fordoc=1) and s.f_isvozm=2 and t.f_id not in (116,298,237,285)
                     #~sdid 637";
            $res1 = $this->dbh->query($sql1);
            if($row['onestrdocs'] == 0)
            {
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                    $nvozmsum=$nvozmsum+$row1['f_sum'];
                }
            }
            elseif($row['onestrdocs'] == 1)
            {
                while($row1 = $res1->fetch(PDO::FETCH_ASSOC))
                {
                    $nvozmsum += $row1['f_sum'];
                }
            }
        }

        return [$vozmsum, $nvozmsum, $vozmsum+$nvozmsum];
    }

    // sdid 2073
    public function checkIfTheAmountsDiffer($specid=0)
    {
        if ($specid > 0)
        {
            $total_agentrep = 0;
            $total_rp_faktsum = 0;

            $clid = " WHERE ar.f_specid=".$specid;
            $sql = "SELECT ar.f_total
                   FROM veda_agentreps ar " . $clid;
            $conn = $this->dbh->query($sql);
            while ($row = $conn->fetch(PDO::FETCH_ASSOC)) {
                $total_agentrep += $row['f_total'];
            }

            $total_rp_faktsum = $this->getTotalRPFaktsum($specid, 0);
            $total_rp_faktsum += $this->getTotalRPFaktsum($specid, 1);
            $repnm = "Суммы Отчетов Агента и РП";
            if($total_agentrep != $total_rp_faktsum)
              {
              $repnm = "Суммы Отчетов Агента не соответствуют реализации РП";
              }
            $response_html = "<p id='sums-diff_".$specid."'>$repnm<br>
ОА: ".number_format($total_agentrep,2,',',' ')." руб<br>
РП: ".number_format($total_rp_faktsum,2,',',' ')." руб<br></p>";

            $response = [false, $response_html];

            if ($total_agentrep != $total_rp_faktsum)
            {
                $response[0] = true;
            }

            return $response;
        }
    }

    private function getTotalRPFaktsum($specid, $outbuhperiod)
    {
        if ($specid > 0 && $outbuhperiod >= 0)
        {
            $sql = "select
                  SUM(faktsum) invfaktsum
                 from(
                   select
                    round(CAST(sum(get_realizsum(si.f_id)) AS DECIMAL(15,3)),2) faktsum
                   from ".DBPref."spec_invoices si,".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."typeopers sit
                   where s86.f_type=86 and s85.f_type=85 and s84.f_type=84 and s86.f_num=si.f_bdrarticle and s85.f_num=s86.f_uslint and
                     s84.f_num=s85.f_dopprint and si.f_bdrarticle>0 and si.f_idoper=sit.f_id and si.f_specid>0 and
                     ((si.f_parenttype=2 and si.f_specid=".$specid.") 
                      #sdid3551
                      #or
                      #(si.f_id in (select sii.f_id from ".DBPref."spec_invoices sii,".DBPref."categs sic
                      #             where sic.f_ctgtype=24 and sic.f_objecttype=5 and sic.f_objectid=sii.f_id and sic.f_valstr=".$specid." and
                      #               (select count(*) from ".DBPref."categs where f_ctgtype=33 and f_objecttype=5 and f_objectid=sii.f_id and f_valstr='2')>0 and
                      #               (select count(*) from ".DBPref."categs where f_ctgtype=32 and f_objecttype=5 and f_objectid=sii.f_id)>0)
                      #)
                      #~sdid3551
                     )
                     and
                     si.f_outbuhperiod=".$outbuhperiod."
                   group by si.f_itemcalcrp) rp";
            error_log("\n\n2 sbor faktsum issql_istorep = $sql\n\n", 3, "/var/www/html/veda/logs/test3551.log"); //sdid 3551
            $conn = $this->dbh->query($sql);
            if ($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
                return $row['invfaktsum'];
            }
            else
            {
                return null;
            }
        }
        else
        {
            return null;
        }
    }
    // ~ sdid 2073

    // sdid 2023
    public function getAgentRepsRP($curPage=1, $rowsPerPage=10, $clid=0, $fid=0)
    {
        try
        {
            $rowbtnmenu = 0;
            $sql = "select f_rowbtnmenu from ".DBPref."users where f_id=".$_SESSION['loginid'];
            $res = $this->dbh->query($sql);
            if($row = $res->fetch(PDO::FETCH_ASSOC))
            {$rowbtnmenu = $row['f_rowbtnmenu'];}
            $response = new stdClass();
            if ($clid > 0) {
                // bugfix 30.05.2024
                $this->updateAgentReps($clid);
                // ~ bugfix 30.05.2024

                $clid = " WHERE f_specid=" . $clid . " ";
            } else {
                $clid = "";
            }

            if ($fid > 0) {
                $and = "";
                if (strlen($clid) > 0)
                {
                    $and = " AND ";
                }
                $fid = $and." WHERE f_id=".$fid." ";
            } else {
                $fid = "";
            }

            $sql = "SELECT COUNT(s.f_id) AS count FROM " . DBPref . "agentreps s where s.f_id>0";
            $rows = $this->dbh->query($sql);
            $totalRows = $rows->fetch(PDO::FETCH_ASSOC);
            $firstRowIndex = $curPage * $rowsPerPage - $rowsPerPage;
            $response->page = $curPage;
            $response->total = ceil($totalRows['count'] / $rowsPerPage);
            $response->records = $totalRows['count'];

            $sql = "SELECT ar.f_id, ar.f_num,
                      ar.f_dttm dttm,
                      ar.f_specid,
                      ar.f_vozm,
                      ar.f_nvozm,
                      ar.f_total,
                      (SELECT MAX(si.f_outbuhperiod) FROM " . DBPref . "agentreps_opers aro, " . DBPref . "spec_invoices si WHERE aro.f_operid=si.f_id AND aro.f_agentrepid=ar.f_id) f_outbuhperiod,
                      ar.f_status
                      ,case when ar.f_status=1 then 'true' else 'false' end as status
                      #sdid 3339
                      ,case when ar.f_dtrep is NULL or ar.f_dtrep = '0000-00-00' then '' else ar.f_dtrep end f_dtrep
                      #~sdid 3339
                   FROM veda_agentreps ar " . $clid . $fid;
            $res = $this->dbh->query($sql);
            $i = 0;
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $btns = "";
                $opars = "{\"usl\":\"0\"}";
                if($rowbtnmenu==1){$btns = getTblRecMenu(294,$opars);}
                else{$btns = getTblRecBtns(294,$opars);}

                $btns = str_replace("@f_id", $row['f_id'], $btns);
                $btns = str_replace("@agentrep_id", $row['f_id'], $btns);

                $vozmNevozmTotalSums = $this->getVozmNevozmTotalSums(15, $row['f_specid'], $row['f_id'], $row['f_num'], $row['f_outbuhperiod']); // sdid 2073
                $response->rows[$i]['cell'] = array(
//                    "<a href='#' title='Операции' onclick='formCtgList(287,2," . $row['f_id'] . ");'>" . getSpanStrOpr("list-alt") . "</a>&nbsp;",
                    $btns,
                    $row['f_num'],
                    $row['dttm'],
                    $row['f_vozm'],
                    $row['f_nvozm'],
                    $row['f_total'],
                    $row['status'],
                    //"<a href=# onclick=downloadAgentRep(" . $row['f_id'] . ") >Скачать отчет</a>", sdid 2363
                    //"<a href='?pgid=305obid=".$row['f_id']."&typeid=1' target='_blank' >Отчет агента</a>", sdid 2363
                    //"<a href='#' onclick=\"showTabsForm('',6,305,".$row['f_id'].",6)\"  >Отчет агента</a>", // sdid 2363 //sdid 3339
                    "<a href='#' onclick=\"showTabsForm('',6,305,".$row['f_id'].",6)\"  >Просмотр</a>", //sdid 3339
                    $row['f_dtrep'], //sdid 3339
                    $row['f_id']
                );
                $i++;
            }
            return $response;
        }
        catch (\Throwable $e)
        {
            echo json_encode([false, 'Error: '.$e->getMessage() . "; " . $e->getFile() . "; " .$e->getLine()]);
        }
    }
    // ~ sdid 2023

//sdid2109
    public function createAgentRepByCorrectId($correctid)
    {
        if($correctid > 0)
        {
            $sql = "SELECT GROUP_CONCAT(f_correctoperid) operids FROM ".DBPref."corrects_opers WHERE f_correctid=$correctid";
            $conn = $this->dbh->query($sql);
            if($row = $conn->fetch(PDO::FETCH_ASSOC))
            {
                $operids = $row['operids'];
                if(strlen($operids) > 0)
                {
                    $this->createAgentRepByOpers($correctid, $operids, false);
                    $sql1 = "SELECT f_specid FROM ".DBPref."corrects WHERE f_id=$correctid";
                    $conn1 = $this->dbh->query($sql1);
                    if($row1 = $conn1->fetch(PDO::FETCH_ASSOC))
                    {
                        return [true, $row1['f_specid']];
                    }
                }
                else
                {
                    return [false, "Не найдены операции по корректировке"];
                }
            }
        }
        else
        {
            return [false, "Некорректный ИД корректировки"];
        }
    }
    //~sdid2109

    // bugfix 30.05.2024
    public function updateAgentRep($rep_id)
    {
        if (strlen($rep_id) > 0)
        {
            if (filter_var($rep_id, FILTER_VALIDATE_INT) !== false)
            {
                if ($rep_id > 0)
                {
                    $sql = "SELECT * FROM ".DBPref."agentreps WHERE f_id=$rep_id";
                    $conn = $this->dbh->query($sql);
                    if ($row = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                        $spec_id = $row['f_specid'];
                        $rep_num = $row['f_num'];

                        //$this->createAgentRepFile(15, $spec_id, $rep_id, $rep_num, 0, false); //sdid 3496
                        $this->createAgentRepFile1(15, $spec_id, $rep_id, $rep_num, 0, false,1); //sdid 3496
                    }
                }
            }
        }
    }

    public function updateAgentReps($spec_id)
    {
        if (strlen($spec_id) > 0)
        {
            if (filter_var($spec_id, FILTER_VALIDATE_INT) !== false)
            {
                if ($spec_id > 0)
                {
                    $sql = "SELECT * FROM ".DBPref."agentreps WHERE f_specid=$spec_id AND f_status=0";
                    $conn = $this->dbh->query($sql);
                    while ($row = $conn->fetch(PDO::FETCH_ASSOC))
                    {
                        $rep_id = $row['f_id'];

                        $this->updateAgentRep($rep_id);
                    }
                }
            }
        }
    }
    // ~ bugfix 30.05.2024
}

?>
