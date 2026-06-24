<?php
	require_once ("lib/classlib.php");
	require_once ("pofflib.php");
	use PhpOffice\PhpWord\Element\Table;
	require_once __DIR__ . '/Classes/PHPExcel-1.8/Classes/PHPExcel.php';
	require_once __DIR__ . '/Classes/PHPExcel-1.8/Classes/PHPExcel/Writer/Excel2007.php';
        //sdid 625
        require_once __DIR__ . '/Classes/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';
        //~sdid 625

  //sdid 791
function translateToWordsEN($number) 
{
/*****
     * A recursive function to turn digits into words
     * Numbers must be integers from -999,999,999,999 to 999,999,999,999 inclussive.    
     *
     *  (C) 2010 Peter Ajtai
     *    This program is free software: you can redistribute it and/or modify
     *    it under the terms of the GNU General Public License as published by
     *    the Free Software Foundation, either version 3 of the License, or
     *    (at your option) any later version.
     *
     *    This program is distributed in the hope that it will be useful,
     *    but WITHOUT ANY WARRANTY; without even the implied warranty of
     *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *    GNU General Public License for more details.
     *
     *    See the GNU General Public License: <http://www.gnu.org/licenses/>.
     *
     */
    // zero is a special case, it cause problems even with typecasting if we don't deal with it here
    $max_size = pow(10,18);
    if (!$number) return "zero";
    if (is_int($number) && $number < abs($max_size)) 
    {            
        switch ($number) 
        {
            // set up some rules for converting digits to words
            case $number < 0:
                $prefix = "negative";
                $suffix = translateToWordsEN(-1*$number);
                $string = $prefix . " " . $suffix;
                break;
            case 1:
                $string = "one";
                break;
            case 2:
                $string = "two";
                break;
            case 3:
                $string = "three";
                break;
            case 4: 
                $string = "four";
                break;
            case 5:
                $string = "five";
                break;
            case 6:
                $string = "six";
                break;
            case 7:
                $string = "seven";
                break;
            case 8:
                $string = "eight";
                break;
            case 9:
                $string = "nine";
                break;                
            case 10:
                $string = "ten";
                break;            
            case 11:
                $string = "eleven";
                break;            
            case 12:
                $string = "twelve";
                break;            
            case 13:
                $string = "thirteen";
                break;            
            // fourteen handled later
            case 15:
                $string = "fifteen";
                break;            
            case $number < 20:
                $string = translateToWordsEN($number%10);
                // eighteen only has one "t"
                if ($number == 18)
                {
                $suffix = "een";
                } else 
                {
                $suffix = "teen";
                }
                $string .= $suffix;
                break;            
            case 20:
                $string = "twenty";
                break;            
            case 30:
                $string = "thirty";
                break;            
            case 40:
                $string = "forty";
                break;            
            case 50:
                $string = "fifty";
                break;            
            case 60:
                $string = "sixty";
                break;            
            case 70:
                $string = "seventy";
                break;            
            case 80:
                $string = "eighty";
                break;            
            case 90:
                $string = "ninety";
                break;                
            case $number < 100:
                $prefix = translateToWordsEN($number-$number%10);
                $suffix = translateToWordsEN($number%10);
                $string = $prefix . "-" . $suffix;
                break;
            // handles all number 100 to 999
            case $number < pow(10,3):                    
                // floor return a float not an integer
                $suffix = "";
                $prefix = translateToWordsEN(intval(floor($number/pow(10,2)))) . " hundred";
                if ($number%pow(10,2)) $suffix = " and " . translateToWordsEN($number%pow(10,2));
                $string = $prefix . $suffix;
                break;
            case $number < pow(10,6):
                // floor return a float not an integer
                $prefix = translateToWordsEN(intval(floor($number/pow(10,3)))) . " thousand";
                if ($number%pow(10,3)) $suffix = translateToWordsEN($number%pow(10,3));
                $string = $prefix . " " . $suffix;
                break;
            case $number < pow(10,9):
                // floor return a float not an integer
                $prefix = translateToWordsEN(intval(floor($number/pow(10,6)))) . " million";
                if ($number%pow(10,6)) $suffix = translateToWordsEN($number%pow(10,6));
                $string = $prefix . " " . $suffix;
                break;                    
            case $number < pow(10,12):
                // floor return a float not an integer
                $prefix = translateToWordsEN(intval(floor($number/pow(10,9)))) . " billion";
                if ($number%pow(10,9)) $suffix = translateToWordsEN($number%pow(10,9));
                $string = $prefix . " " . $suffix;    
                break;
            case $number < pow(10,15):
                // floor return a float not an integer
                $prefix = translateToWordsEN(intval(floor($number/pow(10,12)))) . " trillion";
                if ($number%pow(10,12)) $suffix = translateToWordsEN($number%pow(10,12));
                $string = $prefix . " " . $suffix;    
                break;        
            // Be careful not to pass default formatted numbers in the quadrillions+ into this function
            // Default formatting is float and causes errors
            case $number < pow(10,18):
                // floor return a float not an integer
                $prefix = translateToWordsEN(intval(floor($number/pow(10,15)))) . " quadrillion";
                if ($number%pow(10,15)) $suffix = translateToWordsEN($number%pow(10,15));
                $string = $prefix . " " . $suffix;    
                break;                    
        }
    } else
    {
        echo "ERROR with - $number<br/> Number must be an integer between -" . number_format($max_size, 0, ".", ",") . " and " . number_format($max_size, 0, ".", ",") . " exclussive.";
    }

//    $arub = floor( $number );
//    $acop = floor( ( $number - $arub ) * 100 );
//    //echo "$arub : $acop";
//    $string = $string." ".$acop." cents";

    return $string;    
}

/**
 * Возвращает сумму прописью
 * @author runcore
 * @uses morph(...)
 */
function num2str($num)
{
	$nul = 'ноль';
	$ten = array(
		array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
		array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять')
	);
	$a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
	$tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
	$hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
	$unit = array(
		array('копейка' , 'копейки',   'копеек',     1),
		array('рубль',    'рубля',     'рублей',     0),
		array('тысяча',   'тысячи',    'тысяч',      1),
		array('миллион',  'миллиона',  'миллионов',  0),
		array('миллиард', 'миллиарда', 'миллиардов', 0),
	);
 
	list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub) > 0) {
		foreach (str_split($rub, 3) as $uk => $v) {
			if (!intval($v)) continue;
			$uk = sizeof($unit) - $uk - 1;
			$gender = $unit[$uk][3];
			list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
			// mega-logic
			$out[] = $hundred[$i1]; // 1xx-9xx
			if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; // 20-99
			else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; // 10-19 | 1-9
			// units without rub & kop
			if ($uk > 1) $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
		}
	} else {
		$out[] = $nul;
	}
	$out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
	$out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
	return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

function num2strUSD($num)
{
	$nul = 'ноль';
	$ten = array(
		array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
		array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять')
	);
	$a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
	$tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
	$hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
	$unit = array(
		array('цент' ,    'цента',     'центов',     1),
		array('доллар',   'доллара',   'долларов',   0),
		array('тысяча',   'тысячи',    'тысяч',      1),
		array('миллион',  'миллиона',  'миллионов',  0),
		array('миллиард', 'миллиарда', 'миллиардов', 0),
	);
 
	list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub) > 0) {
		foreach (str_split($rub, 3) as $uk => $v) {
			if (!intval($v)) continue;
			$uk = sizeof($unit) - $uk - 1;
			$gender = $unit[$uk][3];
			list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
			// mega-logic
			$out[] = $hundred[$i1]; // 1xx-9xx
			if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; // 20-99
			else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; // 10-19 | 1-9
			// units without rub & kop
			if ($uk > 1) $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
		}
	} else {
		$out[] = $nul;
	}
	$out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
	$out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
	return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

 
/**
 * Склоняем словоформу
 * @author runcore
 */
function morph($n, $f1, $f2, $f5) 
{
	$n = abs(intval($n)) % 100;
	if ($n > 10 && $n < 20) return $f5;
	$n = $n % 10;
	if ($n > 1 && $n < 5) return $f2;
	if ($n == 1) return $f1;
	return $f5;
}
 
//echo num2str(0);      // ноль рублей 00 копеек
//echo num2str(150.50); // сто пятьдесят рублей 50 копеек
//echo num2str(1203);   // одна тысяча двести три рубля 00 копеек
//echo num2str(2541);   // две тысячи пятьсот сорок один рубль 00 копеек
//echo num2str(100000); // сто тысяч рублей 00 копеек

  //~sdid 791

//sdid 1094
/*  function printInvExcel($prods,$orgbankname,$orgbic,$orgcoracc,$orginn,$orgkpp,$orgname,$orgacc,$invnum,$invdtmprop,$orguradr,$contrnamefullstr,$dogname,
                    $dogdt,$specnum,$specdt,$mainsfio,$buhsfio,$val,$valn,$invsrok,$mainsign,$buhsign,$seal,$logo,$dogtype,$ktk,$typez,$contrsname,$ssubtype,
                    $dogsubtype)*/
//  function printInvExcel($prods,$orgbankname,$orgbic,$orgcoracc,$orginn,$orgkpp,$orgname,$orgacc,$invnum,$invdtmprop,$orguradr,$contrnamefullstr,$dogname,
//                    $dogdt,$specnum,$specdt,$mainsfio,$buhsfio,$val,$valn,$invsrok,$mainsign,$buhsign,$seal,$logo,$dogtype,$ktk,$typez,$contrsname,$ssubtype,
//                    $dogsubtype,$invcolon,$invmsg,$orgabbr)  sdid 1825

  function printInvExcel($prods,$orgbankname,$orgbic,$orgcoracc,$orginn,$orgkpp,$orgname,$orgacc,$invnum,$invdtmprop,$orguradr,$contrnamefullstr,$dogname,
                    $dogdt,$specnum,$specdt,$mainsfio,$buhsfio,$val,$valn,$invsrok,$mainsign,$buhsign,$seal,$logo,$dogtype,$ktk,$typez,$contrsname,$ssubtype,
                    $dogsubtype,$invcolon,$invmsg,$orgabbr, $trade_secret_path="") // sdid 1825
//~sdid 1094
    {
        //echo 123;
        //sdid 625
        PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
        $inputFileName = __DIR__ . "/templates/inv_templ.xls";
        
        try{
            $inputFileType  =   PHPExcel_IOFactory::identify($inputFileName);
            $objReader      =   PHPExcel_IOFactory::createReader($inputFileType);
            $xls            =   $objReader->load($inputFileName);
           }
           catch(Exception $e)
           {
                echo $e->getMessage();
                die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
           }

        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();
        //$sheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        //$sheet->getDefaultStyle()->getFont()->setSize(12);
        $sheet->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->getDefaultStyle()->getFont()->setSize(8);


        $sheet->setTitle('Счет на оплату');
        //$xls->setActiveSheetIndex(0);

        $sheet->setCellValue("J2", $orgbankname);
        $sheet->setCellValue("AH2", $orgbic);
        $sheet->setCellValue("AH3", $orgcoracc);
        $sheet->setCellValue("AH5", $orgacc);
        $sheet->setCellValue("L5", $orginn);
        $sheet->setCellValue("W5", $orgkpp);
        $sheet->setCellValue("J6", $orgname); 
        $sheet->setCellValue("B10", "Счет на оплату № ".$invnum." от ".format_dt($invdtmprop,2,1)." г.");
        $sheet->setCellValue("G14",$orgname.", ИНН ".$orginn.", КПП ".$orgkpp.", ".$orguradr);
        $sheet->setCellValue("G17", $contrnamefullstr);
        if(strlen($dogname)>0) 
          {
            $sheet->setCellValue("G20", "Договор № ".$dogname." от ".format_dt($dogdt,2,1)." г.");
          }
        else
          {
            $sheet->setCellValue("G20", "без договора");
          }

        if($dogtype==2) 
          {
            $spectn = "Спецификация";
            if($ssubtype==3){$spectn = "Поручение ТП";}
            elseif($typez==3){$spectn = "Заявка";}
            $sheet->setCellValue("B21", "Дополнение: ");
            $sheet->setCellValue("G21", $spectn." № ".$specnum." от ".format_dt($specdt,0,0)." г. ".$ktk);
          }

        // Подвал (после таблицы)
        //$sheet->setCellValue("B32", "оплатить не позднее ".format_dt($invsrok,2,1)." г.");
        $sheet->setCellValue("M37", $mainsfio);
        $sheet->setCellValue("AK37", $buhsfio);

//---------------------------
        $total = $nds = 0;
        $tblFrow = 24;
        foreach ($prods as $i => $row) 
          {
            $nnds=0;
            if($row['nds']==1)
              {$row['nds']=10;}
            elseif($row['nds']==2)
              {$row['nds']=20;}
            elseif($row['nds']==3)
              {$row['nds']=0;$nnds=1;}
            elseif($row['nds']==4)
              {$row['nds']=5;}
            elseif($row['nds']==5)
              {$row['nds']=7;}
            //sdid 3562
            elseif($row["nds"]==6)
              {$row["nds"]=22;}
            //~sdid 3562
            $total += $row['price'] * $row['count'];
           
            $curnds = round(($row['price']*$row['nds']/(100+$row['nds']) * $row['count']),2);
            $nds += $curnds;

            if($i>0) 
              {
                $sheet->insertNewRowBefore($tblFrow+$i, 1);
                $sheet->mergeCells("B".($tblFrow+$i).":C".($tblFrow+$i)); 
                $sheet->mergeCells("D".($tblFrow+$i).":T".($tblFrow+$i));
                $sheet->mergeCells("U".($tblFrow+$i).":X".($tblFrow+$i));
                $sheet->mergeCells("Y".($tblFrow+$i).":AA".($tblFrow+$i));
                $sheet->mergeCells("AB".($tblFrow+$i).":AF".($tblFrow+$i));
                $sheet->mergeCells("AG".($tblFrow+$i).":AI".($tblFrow+$i));
                $sheet->mergeCells("AJ".($tblFrow+$i).":AM".($tblFrow+$i));
                $sheet->mergeCells("AN".($tblFrow+$i).":AS".($tblFrow+$i));
                
              }

            $sheet->getRowDimension($tblFrow+$i)->setRowHeight(-1);

            $sheet->setCellValue("B".($tblFrow+$i), $i+1);
            $sheet->setCellValue("D".($tblFrow+$i), $row['name']);
            $sheet->setCellValue("U".($tblFrow+$i), $row['count']);
            $sheet->setCellValue("Y".($tblFrow+$i), $row['unit']);
            $sheet->setCellValue("AB".($tblFrow+$i), $row['price']);

            if(($row['nds']==0)&&($nnds==0))
              {
                $sheet->setCellValue("AG".($tblFrow+$i), "Без НДС");
              }
            else
              {
                $sheet->setCellValue("AG".($tblFrow+$i), $row['nds']."%");
              }
            $sheet->setCellValue("AJ".($tblFrow+$i), format_price($curnds));
            $sheet->setCellValue("AN".($tblFrow+$i), format_price($row['price'] * $row['count']));

          }  

//---------------------------
          $sheet->setCellValue("AO".(26+$i), format_price($total));

          if($nds==0)
            {
              $sheet->setCellValue("AM".(27+$i), "Без налога (НДС)");
              $sheet->setCellValue("AS".(27+$i), "-");
            }
          else
            {
              $sheet->setCellValue("AM".(27+$i), "В том числе НДС:");
              $sheet->setCellValue("AS".(27+$i), ((empty($nds)) ? '-' : format_price($nds)));
            }
          $sheet->setCellValue("AO".(28+$i), format_price($total)); 
          
//---------------------------
          $sheet->setCellValue("B".(29+$i), "Всего наименований ".count($prods) . ", на сумму " . format_price($total) . " ".$valn); 
          $sheet->setCellValue("B".(30+$i), m_mb_ucfirst(sum2words($total,$val))); 
          if($val!=643)
            {
              if($dogsubtype==5)
                {$sheet->setCellValue("B".(31+$i), "Оплата производится в рублях по курсу ЦБ на день оплаты");}
              else
                {$sheet->setCellValue("B".(31+$i), "Оплата производится в рублях по курсу ЦБ на день оплаты плюс 1%");}
            }
          if(strlen($invsrok)>0)
            {
              $sheet->setCellValue("B".(32+$i), "Оплатить не позднее ".format_dt($invsrok,0,0)." г."); 
            }

//---------------------------
          $gdImage = __DIR__ ."/templates/".$mainsign;
          if(file_exists($gdImage))
          {
            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setPath($gdImage);
            $objDrawing->setCoordinates("I".(37+$i));
            $objDrawing->setOffsetX(0);
            $objDrawing->setOffsetY(-90);
            $objDrawing->setWorksheet($xls->getActiveSheet());
          }
          $gdImage1 = __DIR__ ."/templates/".$buhsign;
          if(file_exists($gdImage1))
          {
            $objDrawing1 = new PHPExcel_Worksheet_Drawing();
            $objDrawing1->setPath($gdImage1);
            $objDrawing1->setCoordinates("AH".(37+$i));
            $objDrawing1->setOffsetX(0);
            $objDrawing1->setOffsetY(-90);
            $objDrawing1->setWorksheet($xls->getActiveSheet());
          }
          $gdImage2 = __DIR__ ."/templates/".$seal;
          if(file_exists($gdImage2))
          {
            $objDrawing2 = new PHPExcel_Worksheet_Drawing();
            $objDrawing2->setPath($gdImage2);
            $objDrawing2->setCoordinates("D".(37+$i));
            $objDrawing2->setOffsetX(0);
            $objDrawing2->setOffsetY(-50);
            $objDrawing2->setWorksheet($xls->getActiveSheet());
          }
          if(strlen($logo)>0)
            {
              $gdImage3 = __DIR__ ."/templates/".$logo;
              if(file_exists($gdImage3))
              {
                $objDrawing3 = new PHPExcel_Worksheet_Drawing();
                $objDrawing3->setPath($gdImage3);
                $objDrawing3->setCoordinates("B3");
                $objDrawing3->setOffsetX(0);
                $objDrawing3->setOffsetY(0);
                $objDrawing3->setHeight(52);
                $objDrawing3->setWorksheet($xls->getActiveSheet());
              }

            }
//sdid 1094
          if(strlen($invcolon)>0)
            {
              $gdImage4 = __DIR__ ."/templates/".$invcolon;
              if(file_exists($gdImage4))
              {
                $sheet->getRowDimension(1)->setRowHeight(200);
                $objDrawing4 = new PHPExcel_Worksheet_Drawing();
                $objDrawing4->setPath($gdImage4);
                $objDrawing4->setCoordinates("B1");
                $objDrawing4->setOffsetX(0);
                $objDrawing4->setOffsetY(0);
                $objDrawing4->setHeight(260);
                $objDrawing4->setWorksheet($xls->getActiveSheet());
              }

            }

          if(strlen($invmsg)>0)
            {
                $sheet->insertNewRowBefore(10, 1);
                $sheet->mergeCells("B10:AT10"); 
                $sheet->getRowDimension(10)->setRowHeight(30);

                if(strlen($orgabbr)>0)
                  {
                    $invmsg = str_replace("{orgabbr}",$orgabbr,$invmsg);
                  }
                $sheet->setCellValue("B10", $invmsg);
                $sheet->getStyle('B10')->getFont()->setSize(12);
                $sheet->getStyle('B10')->getFont()->setBold(true);
                $sheet->getStyle('B10')->getFont()->getColor()->setRGB('ff0000');
                $sheet->getStyle("B10")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B10")->getAlignment()->setWrapText(true);
            }
//~sdid 1094
          // sdid 1825
          if(strlen($trade_secret_path) > 0)
            {
            $xls->getActiveSheet()->insertNewRowBefore(2,1);
            $xls->getActiveSheet()->getRowDimension('2')->setRowHeight(53);
            $trade_secret_img = imagecreatefrompng(__DIR__."/templates/".$trade_secret_path);
            $trade_secret_obj = new PHPExcel_Worksheet_MemoryDrawing();
            $trade_secret_obj->setName('Trade Secret Logo');
            $trade_secret_obj->setDescription('Trade Secret Logo');
            $trade_secret_obj->setImageResource($trade_secret_img);
            $trade_secret_obj->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
            $trade_secret_obj->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $trade_secret_obj->setHeight(70);
            $trade_secret_obj->setWorksheet($xls->getActiveSheet());
            $trade_secret_obj->setCoordinates('AB2');
            }
          // ~ sdid 1825

//---------------------------
        $objWriter = new PHPExcel_Writer_Excel2007($xls);
        //$fname = __DIR__ . '/download/Сч '.$invnum.' '.$contrsname.' N '.str_replace("\\"," ",$specnum).'.xlsx';
        $fname = $invnum.' '.$contrsname.' N '.str_replace("\\"," ",$specnum).'.xlsx';
        $fname = str_replace(",","_",$fname).'.xlsx';
        $fname = __DIR__ . '/download/Сч '.str_replace("/","_",$fname);
        //$fname = 'tst1.xlsx';
        //$fname = str_replace("\\","_",$invnum).' '.str_replace("\\","_",$contrsname).' N '.str_replace("\\"," ",$specnum).'.xlsx';
        //$fname = __DIR__ . '/download/'.$fname;

        //echo $inputFileName."<br>";
        //echo $fname."<br>";
        
	//$fname = __DIR__ . "/download/sch".$_SESSION['loginid']."_".date("His").".xlsx";
        $objWriter->save($fname);
        file_force_download($fname);
        //sdid 625
        
    }

  function print_RP_shablon(int $oprid, int $wphpword, object $dbh, array $table_settings)
    {
    /*
    Расчёт поставки
    $oprid - ИД спецификации/заявки
    $wphpword(0/1) - разрешение на работу частей ф-ции
    $dbh - соединение с БД
    $table_settings - параметры для выделения ключевых моментов таблицы
    */
    $withid=0;
    if($_SESSION['loginid']==2){$withid=1;}
    $dtformat = $table_settings[0];
    $bordero = $table_settings[1];
    $borderi = $table_settings[2];
    $our_organisation_ids = array(15, 29, 30, 31, 32, 34);
    $yellow_color_parameters = array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array(
        'rgb' => 'FFD966'));
    $notonespec = 0;
    if(m_strpos($oprid,",")>=0)
      {$notonespec = 1;}
    $sumpribil = 0;
    $sumdoloc  = 0;
    $sumpostdc = 0;
    if($wphpword==1)
      {
      $active_index = 0;
      $spec_ids_array = array();
      $sql = "SELECT specs.f_parentspecid, specs.f_is_extra_spec FROM veda_specs specs WHERE specs.f_id IN ($oprid)";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {
        if($row['f_parentspecid']>0 && $row['f_is_extra_spec'])
          {$main_spec_id = $row['f_parentspecid'];}
        else
          {$main_spec_id = $oprid;}
        array_push($spec_ids_array, $main_spec_id);
        }
      $sql = "SELECT specs.f_id FROM veda_specs specs WHERE specs.f_is_extra_spec=1 AND specs.f_parentspecid IN ($main_spec_id)";
      $res = $dbh->query($sql);
      while($row = $res->fetch(PDO::FETCH_ASSOC))
        {array_push($spec_ids_array, $row['f_id']);}
      //sdid 1123             
      $fname = "";
      $sql = "select concat('РП ',d.f_dogname,'_',s.f_num,'_',o.f_abbr,'_',c.f_cname) fname
               from veda_dogs d, veda_specs s, veda_clients c, veda_clients o 
               where d.f_id=s.f_dogid 
                 and s.f_id=".$main_spec_id." 
                 and c.f_id=d.f_contrid
                 and o.f_id=d.f_orgid";
      $res = $dbh->query($sql);
      if($row = $res->fetch(PDO::FETCH_ASSOC))
        {$fname = $row['fname'];} 
      //~sdid 1123             
      PHPExcel_Settings::setZipClass(PHPExcel_Settings::PCLZIP);
      $xls = new PHPExcel();
      foreach ($spec_ids_array as $spec_id)
        {
        $is_main_spec = $spec_id == $main_spec_id ? 1 : 0;
        $sql = "SELECT spr.f_dopprstr FROM veda_spr spr, veda_specs specs WHERE spr.f_type=33 AND spr.f_num=specs.f_typez AND specs.f_id=$spec_id";
        $res = $dbh->query($sql);
        if($row = $res->fetch(PDO::FETCH_ASSOC))
          {$spec_type = $row['f_dopprstr'];}
        $spec_type = $is_main_spec ? "Основная $spec_type" : "Доп. $spec_type";
        if($active_index>0)
          {$xls->createSheet();}
        $xls->setActiveSheetIndex($active_index);
        $active_index++;
        $sheet = $xls->getActiveSheet();
        $sheet->getDefaultStyle()->getFont()->setName('Arial');
        $sheet->getDefaultStyle()->getFont()->setSize(10);
        $sheet->setTitle("$spec_type, ид - $spec_id");			
        $sheet->getStyle("A1:O1")->getFont()->setBold(true);
        $sheet->getColumnDimension("A")->setWidth(18);
        $sheet->getColumnDimension("B")->setWidth(15);
        $sheet->getColumnDimension("C")->setWidth(45);
        $sheet->getColumnDimension("D")->setWidth(20);
        $sheet->getColumnDimension("E")->setWidth(20);
        $sheet->getColumnDimension("F")->setWidth(15);
        $sheet->getColumnDimension("G")->setWidth(15);
        $sheet->getColumnDimension("H")->setWidth(15);
        $sheet->getColumnDimension("I")->setWidth(15);
        $sheet->getColumnDimension("J")->setWidth(15);
        $sheet->getColumnDimension("K")->setWidth(15);
        $sheet->getColumnDimension("L")->setWidth(15);
        $sheet->getColumnDimension("M")->setWidth(15);
        $sheet->getColumnDimension("N")->setWidth(15);
        $sheet->getColumnDimension("O")->setWidth(15);			
        $sheet->setCellValue("A2", "");
        $sheet->getStyle("A2:A2")->getFont()->setBold(true);
        $sheet->setCellValue("A3", "Клиент");
        $sheet->setCellValue("A4", "Юридическое лицо");
        $sheet->setCellValue("A5", "Спецификация/Заявка");
        $sheet->setCellValue("A6", "Контейнеры");
        $sheet->getStyle("B6:B6")->getAlignment()->setWrapText(true);
        $sheet->setCellValue("A7", "Путь");
        $sheet->setCellValue("A9", "ДВИЖЕНИЕ ДЕНЕЖНЫХ СРЕДСТВ");
        $sheet->getStyle("A9:A9")->getFont()->setBold(true);
        $sheet->mergeCells("A10:D10");
        $sheet->mergeCells("E10:J10");
        $sheet->getStyle("A10:D10")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("A10", "ВЫСТАВЛЕНО");
        $sheet->setCellValue("D10", "ОПЛАЧЕНО");
        $sheet->getStyle("A11:J11")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("A11", "№ Счета");
        $sheet->setCellValue("B11", "Дата счета");
        $sheet->setCellValue("C11", "Валюта");
        $sheet->setCellValue("D11", "Сумма счета");
        $sheet->setCellValue("E11", "Статья БДДС");
        $sheet->setCellValue("F11", "№ п/п");
        $sheet->setCellValue("G11", "Дата п/п");
        $sheet->setCellValue("H11", "Сумма п/п");
        $sheet->setCellValue("I11", "Валюта");
        $sheet->setCellValue("J11", "Курс валюты");
        $sheet->getStyle("A10:O10")->getFont()->setBold(true);
        $sheet->getStyle("A11:O11")->getFont()->setBold(true);
        $cln  = 12;
        $cdds = $cln;$sumpp=0;$cah=0;
        $sql  = "select ah.f_id ahid,i.f_num,DATE_FORMAT(i.f_dt,'%d.%m.%Y') f_dt,i.f_val,
                   (select f_name from ".DBPref."spr where f_type=4 and f_num=i.f_val) val, 
                   i.f_sum f_clssum,
                   ah.f_ppnum,DATE_FORMAT(ah.f_ppdt,'%d.%m.%Y') f_ppdt,ahd.f_clssum f_sum,ah.f_val,
                   case 
                     when i.f_val<>ah.f_val then cast((ahd.f_clssum/i.f_sum) AS DECIMAL(15,2)) 
                     else 1 
                   end f_cursoper, 
                   (select f_name from ".DBPref."spr where f_type=4 and f_num=ah.f_val) ahval 
                 from ".DBPref."schets i 
                 left join ".DBPref."acchist_docs ahd on ahd.f_doctype=1 and ahd.f_docid=i.f_id 
                 left join ".DBPref."acchist ah on ah.f_id=ahd.f_acchistid and ah.f_type=0 
                 where i.f_type=1 and i.f_maininv=0 and i.f_dogtype=2 and i.f_dogid in (".$spec_id.") order by i.f_id,ah.f_id";
        $res = $dbh->query($sql);
        while($row = $res->fetch(PDO::FETCH_ASSOC))
          {
          if($wphpword==1)
            {
            $sheet->setCellValue("A".$cln, $row["f_num"]);
            $sheet->setCellValue("B".$cln, $row["f_dt"]);
            $sheet->setCellValue("C".$cln, $row["val"]);
            $sheet->setCellValue("D".$cln, $row["f_clssum"]);
            $sheet->setCellValue("F".$cln, $row["f_ppnum"]);
            $sheet->setCellValue("G".$cln, $row["f_ppdt"]);
            $sheet->setCellValue("H".$cln, $row["f_sum"]);
            $sheet->setCellValue("I".$cln, $row["ahval"]);
            $sheet->setCellValue("J".$cln, $row["f_cursoper"]);
            $cln++;
            }
          }
        if($wphpword==1)
          {
          $sheet->setCellValue("A".$cln, "Итого");
          $sheet->setCellValue("D".$cln, "=SUM(D".$cdds.":D".($cln-1).")");
          $sheet->setCellValue("H".$cln, "=SUM(H".$cdds.":H".($cln-1).")");
          $sheet->getStyle("A".$cln.":O".$cln)->getFont()->setBold(true);
          $sheet->getStyle("D".$cdds.":D".$cln)->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("F".$cdds.":F".$cln)->getNumberFormat()->setFormatCode('#');
          $sheet->getStyle("A10:J".$cln)->applyFromArray($borderi);
          $sheet->getStyle("A10:J".$cln)->applyFromArray($bordero);
          $cln=$cln+2;
          $sheet->setCellValue("A".$cln, "РАСЧЕТ ПОСТАВКИ");
          $sheet->getStyle("A".$cln.":A".$cln)->getFont()->setBold(true);
          $cln++;
          $sheet->setCellValue("A".$cln, "Итого начислено:");
          $sheet->getStyle("A".$cln.":A".$cln)->getFont()->setBold(true);
          $cln++;
          $clvz = $cln;
          $cln++;
          $clnvz = $cln;
          $sheet->setCellValue("A".$clvz , "Возмещаемые");
          $sheet->getStyle("A".$clvz.":B".$clvz)->getFont()->setBold(true);
          $sheet->setCellValue("A".$clnvz, "Невозмещаемые");
          $sheet->getStyle("A".$clnvz.":B".$clnvz)->getFont()->setBold(true);
          $cln=$cln+2;
          $clnt = $cln;
          $sheet->getStyle("A".$cln.":O".$cln)->getAlignment()->setWrapText(true);
          $sheet->getStyle("A".$cln.":O".$cln)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $sheet->setCellValue("A".$cln, "Спецификация");
          $sheet->setCellValue("B".$cln, "Дата");
          $sheet->setCellValue("C".$cln, "Операция");
          $sheet->setCellValue("D".$cln, "Тип расходов");
          $sheet->setCellValue("E".$cln, "Признак входящего НДС");
          $sheet->setCellValue("F".$cln, "Валюта по операции");
          $sheet->setCellValue("G".$cln, "Сумма по операции");
          $sheet->setCellValue("H".$cln, "Оплачено клиентом");
          $sheet->setCellValue("I".$cln, "Начислено расходов");
          $sheet->setCellValue("J".$cln, "Реализация/Отчет агента");
          $sheet->setCellValue("K".$cln, "\"+\" Долг клиента/\"-\" Долг организации");
          //sdid - 277
          $sql = "SELECT org.f_id, org.f_cname org_name FROM veda_specs specs, veda_clients org, veda_dogs d WHERE specs.f_id in ($spec_id) AND org.f_id=d.f_orgid AND d.f_id=specs.f_dogid";
          $res = $dbh->query($sql);
          if($row = $res->fetch(PDO::FETCH_ASSOC))
            {
            $organisation_id = $row['f_id'];
            $organisation_name = $row['org_name'];
            if($organisation_id == 0)
              {$organisation_name = "АО ВЭД АГЕНТ";}
            }
          $sheet->setCellValue("L".$cln, "Прибыль/убыток " . $organisation_name);
          $sheet->setCellValue("M".$cln, "Прибыль/убыток ВЭД (ГК,КНР)");
          //~sdid - 277
          $sheet->setCellValue("N".$cln, "НДС");
          $sheet->setCellValue("O".$cln, "Расходы без входного НДС");
          $sheet->getStyle("A".$cln.":O".$cln)->getFont()->setBold(true);
          }
        //sdid1314
        $sql ="select r.*,
                 case 
                   when sfid=0 then
                     (select f_dogname from ".DBPref."dogs where f_id=r.f_specid) 
                   else
                     (select concat(d.f_dogname,'/',c.f_num) from ".DBPref."dogs d,".DBPref."specs c where d.f_id=c.f_dogid and c.f_id=r.f_specid) 
                 end specname,
                 (select f_namedop from ".DBPref."spr where f_type=4 and f_num=r.f_val) val,
                 case when r.f_dopprint=1 then r.rrsum else 0 end sumprihf,
                 case when r.f_dopprint=1 then r.bdrsum else 0 end sumprih,
                 (select count(*) from ".DBPref."spec_invoices where f_parentid>0 and f_parentid=r.operation_id and f_idoper=389) cntparentusldolg,
                 case 
                   #sdid2295 обнуляем сумму реализации для Поручения на покупку валюты, если с ней связана Услуга. В ДОЛГ
                   when r.f_idoper=389 then
                     ifnull((select sum(round(CAST(la.f_sum*getcbrate(la.f_val,la.f_dt) AS DECIMAL(15,3)),2)) from veda_akts la where la.f_operid=r.operation_id and la.f_type=23),0)
                   when r.f_c1doctype=4 and (select count(*) from ".DBPref."spec_invoices where f_parentid>0 and f_parentid=r.operation_id and f_idoper=389)>0 then 0
                   #~sdid2295
                   when r.f_dopprint=2 then r.bdrsum 
                   else 0 end 
                 sumrash 
               from (select k.*,
                       case when k.f_addnds=1 then
                         round((k.rsum*1.2),2) 
                       else k.rsum end bdrsum, 
                       case when k.f_addnds=1 then 1 
                       else 0 end wonds 
                     from 
                       (select s.f_dttmcr,s.f_id operation_id,
                          #sdid2022
                          t.f_c1doctype,
                          #~sdid2022
                          case when 
                            (select count(*) from veda_categs where f_ctgtype=33 and f_objecttype=5 and f_objectid=s.f_id and f_valstr='2')>0 and 
                            (select count(*) from veda_categs where f_ctgtype=32 and f_objecttype=5 and f_objectid=s.f_id)>0 and 
                            (select count(*) from veda_categs where f_ctgtype=24 and f_objecttype=5 and f_objectid=s.f_id)>0 
                          then (select f_valstr from veda_categs where f_ctgtype=24 and f_objecttype=5 and f_objectid=s.f_id)
                          else s.f_specid end f_specid,
                          s.f_idoper,t.f_name oper,s.f_type_oper,s.f_sub_type_oper,s.f_bdrarticle,s.f_nds,s.f_isvozm,s.f_contrid operation_contrid,
                          s86.f_name stat,s84.f_name razd,
                          ifnull((select f_name from ".DBPref."spr where f_type=33 and f_num=ss.f_typez),'') typez,
                          ifnull(ss.f_num,'') ssnum,ifnull(ss.f_id,0) sfid,
                          ifnull(concat('№ ',ss.f_num,' от ',DATE_FORMAT(ss.f_dt,'".$dtformat."')),'') ssnumdt,
                          s.f_parenttype,
                          case when s.f_parenttype=2 then
                            ifnull(concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=clnt.f_opf),' ',clnt.f_cname),'') 
                          else '' end cname,
                          ifnull(concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=org.f_opf),' ',org.f_cname),'') oname,
                          ifnull(ss.f_dirmain,'') ssdirmain,
                          ifnull(ss.f_postid,0) f_postid, 
                          s85.f_dopprint,s.f_sum,s.f_val,s.f_addnds,s.f_nodoccalc,
                          #sdid1583
                          get_paidsum(s.f_id) paidsum,
                          get_expensessum(s.f_id) expensessum,
                          get_realizsum(s.f_id) realizsum,
                          get_debitor(s.f_id) debitor,
                          get_profit(s.f_id) profit,
                          #~sdid1583
                          get_fesum(s.f_id) fesum,
                          get_rrsum(s.f_id,0) rrsum,
                          get_rrsum(s.f_id,1) rsum 
                        from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."typeopers t,".DBPref."spec_invoices s
                        left join ".DBPref."specs ss on ss.f_id=s.f_specid
                        left join ".DBPref."dogs d on d.f_id=ss.f_dogid
                        left join ".DBPref."clients clnt on clnt.f_id=d.f_contrid
                        left join ".DBPref."clients org on org.f_id=d.f_orgid
                        where t.f_id=s.f_idoper and s86.f_type=86 and s85.f_type=85 and 
                          case when 
                       	    (select count(*) from veda_categs where f_ctgtype=33 and f_objecttype=5 and f_objectid=s.f_id and f_valstr='2')>0 and 
                            (select count(*) from veda_categs where f_ctgtype=32 and f_objecttype=5 and f_objectid=s.f_id)>0 and 
                            (select count(*) from veda_categs where f_ctgtype=24 and f_objecttype=5 and f_objectid=s.f_id)>0 
                          then s86.f_num=(select f_valstr from veda_categs where f_ctgtype=32 and f_objecttype=5 and f_objectid=s.f_id)
                          else s86.f_num=s.f_bdrarticle end
                          and 
                          s.f_outbuhperiod=0 and s85.f_num=s86.f_uslint 
                          and s84.f_type=84 and s84.f_num=s85.f_dopprint and 
                          (
                           (s.f_parenttype=2 and s.f_specid in (".$spec_id.")) or  
                           (s.f_id in (select si.f_id from ".DBPref."spec_invoices si,veda_categs sic
                                       where sic.f_ctgtype=24 and sic.f_objecttype=5 and sic.f_objectid=si.f_id and sic.f_valstr in (".$spec_id.") and 
                                         (select count(*) from ".DBPref."categs where f_ctgtype=33 and f_objecttype=5 and f_objectid=si.f_id and f_valstr='2')>0 and 
                                         (select count(*) from ".DBPref."categs where f_ctgtype=32 and f_objecttype=5 and f_objectid=si.f_id)>0)
                           )
                          )
                          and 
                          #((s.f_parenttype=2 and s.f_specid in (".$spec_id.")) or s.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=24 and f_objecttype=5 and f_valstr in (".$spec_id."))) and 
                          s.f_bdrarticle>0 order by s.f_specid,s.f_id
                       ) k
                    ) r 
               order by r.f_isvozm,r.f_type_oper,r.f_dopprint,r.f_sub_type_oper,r.stat"; 
    //echo "$sql<br>";
    //~sdid1314
    if($wphpword==1)
      {
    $cln++;
    $csn = $cln;
    $sump=0;$sumr=0;$owonds=0;$wonds=0;
    $vozms=0;$nvozms=0;
    $topr=0;$ownds=0;$lvozm=0;
    $oprsl=0;
    $prib = 0;$postid=0;$typez = "";
    $ktk = "";$klw = 0;$kwn = 0;
    $res = $dbh->query($sql);
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      if($wphpword==1)
        {
      if(($row['sfid']>0)&&($kwn==0)&&($row['f_parenttype']==2))
        {
        $sheet->setCellValue("B3", $row['cname']);
        $sheet->setCellValue("B4", $row['oname']);
        $sheet->setCellValue("B5", $row['ssnumdt']);
        $sheet->setCellValue("B7", str_replace("&bsol;","\\",$row['ssdirmain']));
        $kwn++;
        }
        }
      if(($row['sfid']>0)&&($klw==0))
        {
        $typez  = $row['typez'];
        $postid = $row['f_postid'];
        }
      $fesum = $row['fesum'];
      if(($topr!=$row["f_type_oper"])||($lvozm!=$row['f_isvozm']))
        {
        if($topr>0)
          {
          $oprsl = round($oprsl,2);
          $vk=0;$vl=0;$vm=0;
          if($lvozm==1)
            {
            if($oprsl>0)
              {$vm=$oprsl;}
            elseif($oprsl<0)
              {$vl=-1*$oprsl;}
            }
          else
            {
            $vk = $oprsl;
            $prib=$prib-$vm;
            }
          }
        $oprsl = 0;
        }
      $topr = $row["f_type_oper"];
      $oprsl = $oprsl+$row["sumprih"]-$row["sumrash"];
      $sheet->setCellValue("A".$csn, $row["specname"]);
      $sheet->setCellValue("B".$csn, substr($row["f_dttmcr"],0,10));
      $sheet->setCellValue("C".$csn, $row["oper"]);
      $vozm = "не опр";
      if($row['f_isvozm']==1)
        {
        $vozm = "возмещ";
        //$vozms=$vozms+$row["sumrash"];//sdid2360
        $vozms=$vozms+$row["realizsum"];//sdid2360
        $vozmsr=$row["sumrash"];
        }
      elseif($row['f_isvozm']==2)
        {
        $vozm = "не возм";
        $nvozmsf=$nvozms+$row["sumprihf"];
        $vozmsr=$row["sumprihf"];
        if($row['f_dopprint']==1)                            //для невозмещаемого дохода выводим сумму операции
          {
          if($row['f_val']==643)
            {$vozmsr=$row["f_sum"];}
          }
        //$nvozms = $nvozms+$vozmsr;        //sdid2360
        $nvozms = $nvozms+$row["realizsum"];//sdid2360
        }
      else
        {
        $nvozmsf=$nvozms+$row["sumprihf"];
        $vozmsr=$row["sumprihf"];
        if($row['f_dopprint']==1)
          {
          if($row['f_val']==643)
            {$vozmsr=$row["f_sum"];}
          else
            {$sumrash=$row["f_sum"]*getCBRate($row['f_sum'],substr($row["f_dttmcr"],0,10));}
          }
        }
      $sheet->setCellValue("D".$csn, $vozm);
      if($row["wonds"]==1)
        {$sheet->setCellValue("E".$csn, "Да");}
      else
        {$sheet->setCellValue("E".$csn, "Нет");}
      $sheet->setCellValue("F".$csn, $row["val"]);
      $sheet->setCellValue("G".$csn, $row["f_sum"]);
      //sdid2022
      //sdid1583
      if($row['f_idoper']==387)
        {$sheet->setCellValue("H".$csn, $row["paidsum"]);}
      //~sdid1583
      elseif(($row['f_c1doctype']==18)and($row['f_dopprint']==1))
        {$sheet->setCellValue("H".$csn, 0);}
      //sdid1583
      elseif($row['f_idoper']==236)
        {$sheet->setCellValue("H".$csn, $row["paidsum"]);}
      else
        {$sheet->setCellValue("H".$csn, $row["sumprihf"]);}
      //~sdid1583
      //~sdid2022
      $sumrash = $row["sumrash"];
      if(($row['f_nodoccalc']==1)&&($sumrash==0))
        {
        if($row['f_val']==643)
          {$sumrash=$row["f_sum"];}
        else
          {$sumrash=$row["f_sum"]*getCBRate($row['f_sum'],substr($row["f_dttmcr"],0,10));}
        }
      if(($row['f_idoper']==186)||($row['f_idoper']==236))
        {
        $sheet->setCellValue("I".$csn, 0);
        }
      //sdid2022
      elseif(($row['f_c1doctype']==18)and($row['f_dopprint']==1))
        {
        $sheet->setCellValue("I".$csn, (-1)*$row["sumprihf"]);
        }
      //~sdid2022
      else
        {
        $sheet->setCellValue("I".$csn, $sumrash);
        }
//sdid 637
//						$sheet->setCellValue("J".$csn, $vozmsr);
      if(((($row['f_idoper']==237)||($row['f_idoper']==285))&&($row['f_isvozm']==2))||
         (($row['f_idoper']==186)||($row['f_idoper']==236)))
        {
        $sheet->setCellValue("J".$csn, 0);
        $sheet->setCellValue("L".$csn, 0);
        }
      //sdid2022
      elseif(($row['f_c1doctype']==18)&&($row['f_dopprint']==1))
        {
        $sheet->setCellValue("J".$csn, (-1)*$row["sumprihf"]);
        $sheet->setCellValue("L".$csn, 0);
        }
      //~sdid2022
      elseif(($row['cntparentusldolg']>0)&&($row['f_c1doctype']==4))
        {
        $sheet->setCellValue("J".$csn, $vozmsr);
        $sheet->setCellValue("L".$csn, $row['profit']);
        }
      else
        {
        $sheet->setCellValue("J".$csn, $vozmsr);
        $sheet->setCellValue("L".$csn, ($vozmsr-$sumrash));
        }
//~sdid 637
      //sdid1583
      if($row['f_idoper']==386)
        {
        $sheet->setCellValue("I".$csn, $row['expensessum']);
        $sheet->setCellValue("J".$csn, $row['realizsum']);
        }
      elseif($row['f_idoper']==387)
        {
        $sheet->setCellValue("I".$csn, $row['expensessum']);
        $sheet->setCellValue("J".$csn, $row['realizsum']);
        $sheet->setCellValue("L".$csn, $row['profit']);
        }
      elseif($row['f_idoper']==389)
        {
        $sheet->setCellValue("J".$csn, $row['realizsum']);
        $sheet->setCellValue("L".$csn, 0);
        }
      if((($row['f_idoper']==237)||($row['f_idoper']==285))&&($row['f_isvozm']==2))
        {
        $sheet->setCellValue("K".$csn, -$row["sumprihf"]);
        }
      //sdid1583
      elseif($row['f_idoper']==387)
        {
        $sheet->setCellValue("K".$csn, $row['debitor']);
        }
      //~sdid1583
      //sdid2295
      elseif($row['f_idoper']==389)
        {
        $sheet->setCellValue("K".$csn, $row['realizsum']);
        }
      //sdid2295
      elseif($row['f_idoper']==236)
        {
        $sheet->setCellValue("K".$csn, $row['debitor']);
        }
      //sdid3340
      elseif($row['f_idoper']==522)
        {
        $sheet->setCellValue("J".$csn, $row['realizsum']);
        $sheet->setCellValue("K".$csn, $row['debitor']);
        }
      //~sdid3340
      else
        {
        $sheet->setCellValue("K".$csn, $vozmsr-$row["sumprihf"]);
        }
      //$sheet->setCellValue("L".$csn, ($vozmsr-$sumrash));
      //~sdid 637
      //$sheet->setCellValue("L".$csn, $sumrash);
      $sumpribil = $sumpribil+($vozmsr-$sumrash);
      $sumdoloc  = $sumdoloc +($vozmsr-$row["sumprihf"]);
      $sumpostdc = $sumpostdc+$row["sumprihf"];
      $sump=$sump+$row["sumprih"];
      $sumr=$sumr+$row["sumrash"];
      if($row['wonds']==1)
        {
        $owonds=$owonds+$row['bdrsum'];
        $ownds =$ownds+$row['rsum'];
        }
      if($row['f_nodoccalc']==1)
        {
        $sheet->getStyle("A".$csn.":N".$csn)->getFill()->applyFromArray(array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array(
                'rgb' => 'F28A8C')));
        }
      $sql = "SELECT akts.f_contrid FROM veda_akts akts WHERE akts.f_operid=".$row['operation_id'];
      $response = $dbh->query($sql);
      $akts_contrid = 0;
      if($operation_row = $response->fetch(PDO::FETCH_ASSOC))
        {$akts_contrid = $operation_row['f_contrid'];}
      //закрашиваем Жёлтым цветом, если в закрывающих документах или в операции указан контрагент наше ЮЛ
      if((in_array($row['operation_contrid'], $our_organisation_ids) || in_array($akts_contrid, $our_organisation_ids)) && $is_main_spec)
        {$sheet->getStyle("I".$csn)->getFill()->applyFromArray($yellow_color_parameters);}
      $sheet->setCellValue("M".$csn, $fesum);
      if($row["wonds"]==1)
        {
        $sheet->setCellValue("N".$csn, ($row['sumrash']-$row['rrsum']));
        $sheet->setCellValue("O".$csn, $row['rrsum']);
        }
      else
        {
        $sheet->setCellValue("N".$csn, 0);
        $sheet->setCellValue("O".$csn, 0);
        }
      //sdid3548 - переделали все столбцы на хранимки
      $sheet->setCellValue("G".$csn, $row["f_sum"]);
      $sheet->setCellValue("H".$csn, $row['paidsum']);
      $sheet->setCellValue("I".$csn, $row['expensessum']);
      $sheet->setCellValue("J".$csn, $row['realizsum']);
      $sheet->setCellValue("K".$csn, $row['debitor']);
      $sheet->setCellValue("L".$csn, $row['profit']);
      $sheet->setCellValue("M".$csn, $row['fesum']);
      if($row["wonds"]==1)
        {
        $sheet->setCellValue("N".$csn, ($row['expensessum']-$row['rrsum']));
        $sheet->setCellValue("O".$csn, $row['rrsum']);
        }
      else
        {
        $sheet->setCellValue("N".$csn, 0);
        $sheet->setCellValue("O".$csn, 0);
        }
      //~sdid3548 - переделали все столбцы на хранимки
      $lvozm=$row['f_isvozm'];
      if($withid==1){$sheet->setCellValue("P".$csn, $row['operation_id']);}
      $csn++;
      if($row['sfid']>0)
        {$klw = 1;}
      }
    //$sheet->setCellValue("P".$csn, $row['operation_id']);
    }
  $vk=0;$vl=0;$vm=0;
  if($lvozm==1)
    {
    if($oprsl>0)
      {$vm=$oprsl;}
    elseif($oprsl<0)
      {$vl=$oprsl;}
    }
  else
    {
    $vk=$oprsl;
    $prib=$prib-$vm;
    }
  if($wphpword==1)
    {
    $sheet->getStyle("C".$clnt.":C".$csn)->getAlignment()->setWrapText(true);
    $sheet->setCellValue("A".$clnt, $typez);
    $sheet->setCellValue("A5", $typez);
    }
  $ktk = "";
  if($postid>0)
    {
    $sql2 = "select distinct(k.f_num) f_num,k.f_id from ".DBPref."ktk k,".DBPref."routes_ktk h,".DBPref."routes r 
             where k.f_id=h.f_ktkid and r.f_id=h.f_routeid and r.f_postid=".$postid;
    $res2 = $dbh->query($sql2);
    while($row2 = $res2->fetch(PDO::FETCH_ASSOC))
      {
      if(strlen($ktk)>0){$ktk=$ktk.";\n";}
      $ktk = $ktk.$row2['f_num'];
      }
    }
  if($wphpword==1)
    {
    $itg1 = $csn;
    $sheet->setCellValue("B6", $ktk);
    $sheet->getStyle("A".$clnt.":O".$csn)->applyFromArray($borderi);
    $sheet->getStyle("A".$clnt.":O".$csn)->applyFromArray($bordero);
    $sheet->getStyle("A".$csn.":O".$csn)->getFont()->setBold(true);
    $sheet->setCellValue("A".$csn, "");
    $sheet->setCellValue("B".$csn, "");
    $sheet->setCellValue("C".$csn, "ИТОГО");
    $sheet->setCellValue("D".$csn, "");
    $sheet->setCellValue("E".$csn, "");
    $sheet->setCellValue("F".$csn, "");
    $sheet->setCellValue("G".$csn, "");
    $sheet->setCellValue("H".$csn, "=SUM(H".($clnt+1).":H".($csn-1).")");
    $sheet->setCellValue("I".$csn, "=SUM(I".($clnt+1).":I".($csn-1).")");
    $sheet->setCellValue("J".$csn, "=SUM(J".($clnt+1).":J".($csn-1).")");
    $sheet->setCellValue("K".$csn, "=SUM(K".($clnt+1).":K".($csn-1).")");
    $sheet->setCellValue("L".$csn, "=SUM(L".($clnt+1).":L".($csn-1).")");
    $sheet->setCellValue("M".$csn, "=SUM(M".($clnt+1).":M".($csn-1).")");
    $sheet->setCellValue("N".$csn, "=SUM(N".($clnt+1).":N".($csn-1).")");
    $sheet->setCellValue("O".$csn, "=SUM(O".($clnt+1).":O".($csn-1).")");
    if(!$is_main_spec)
      {
      $sheet->getStyle("J".$csn)->getFill()->applyFromArray($yellow_color_parameters);
      }
    $csn++;
    $csn++;
    }
  if($wphpword==1)
    {
    $sheet->setCellValue("A".$csn, "КОРРЕКТИРОВКИ");
    $sheet->getStyle("A".$csn.":A".$csn)->getFont()->setBold(true);
    $csn++;
    $csnkb = $csn;
    $sheet->getStyle("A".$csn.":O".$csn)->getFont()->setBold(true);
    $sheet->getStyle("A".$csn.":O".$csn)->getAlignment()->setWrapText(true);
    $sheet->getStyle("A".$csn.":O".$csn)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->setCellValue("A".$csn, "Спецификация");
    $sheet->setCellValue("B".$csn, "Дата");
    $sheet->setCellValue("C".$csn, "Операция");
    $sheet->setCellValue("D".$csn, "Тип расходов");
    $sheet->setCellValue("E".$csn, "Признак входящего НДС");
    $sheet->setCellValue("F".$csn, "Валюта по операции");
    $sheet->setCellValue("G".$csn, "Сумма по операции");
    $sheet->setCellValue("H".$csn, "Оплачено клиентом");
    $sheet->setCellValue("I".$csn, "Начислено расходов");
    $sheet->setCellValue("J".$csn, "Реализация/Отчет агента");
    $sheet->setCellValue("K".$csn, "\"+\" Долг клиента/\"-\" Долг организации");
    $sheet->setCellValue("L".$csn, "Прибыль/убыток " . $organisation_name);
    $sheet->setCellValue("M".$csn, "Прибыль/убыток ВЭД (ГК,КНР)");
    $sheet->setCellValue("N".$csn, "НДС");
    $sheet->setCellValue("O".$csn, "Расходы без входного НДС");
    $sheet->getStyle("A".$csnkb.":O".$csn)->applyFromArray($borderi);
    $sheet->getStyle("A".$csnkb.":O".$csn)->applyFromArray($bordero);
    $sheet->getStyle("C".$csnkb.":C".$csn)->getAlignment()->setWrapText(true);
    }
  $sql ="select r.*,
            case when r.f_parentid>0 then 
              ifnull((select lsi.f_id from ".DBPref."spec_invoices lsi,".DBPref."spec_invoices lssi,".DBPref."typeopers lt,".DBPref."acchist lah,".DBPref."acchist_docs lahd 
                      where lt.f_id=lsi.f_idoper and lsi.f_id=r.f_parentid and lt.f_c1doctype=4 and lssi.f_parentid=lsi.f_id and lssi.f_idoper=389 
                        and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lah.f_id=lahd.f_acchistid and lah.f_ahtype=3 limit 1),0)
            else 0 end idparentusldolg,
            PA_ispayspecinv(r.f_id) idparentusldolgpp,
            PA_ispayspecinvusldolg(r.f_id) ispayspecinvusldolg,
            case 
              when sfid=0 then
                (select f_dogname from ".DBPref."dogs where f_id=r.f_specid) 
              else
                (select concat(d.f_dogname,'/',c.f_num) from ".DBPref."dogs d,".DBPref."specs c where d.f_id=c.f_dogid and c.f_id=r.f_specid) 
            end specname,
            (select f_namedop from ".DBPref."spr where f_type=4 and f_num=r.f_val) val,".
          " case when r.f_dopprint=1 then r.rrsum else 0 end sumprihf,".
          " case when r.f_dopprint=1 then r.bdrsum else 0 end sumprih,".
          " case when r.f_dopprint=2 then r.bdrsum else 0 end sumrash ".
          "from (select k.*,
                   case 
                     when k.f_val=643 and k.f_addnds=1 then round((k.rsum*1.2),2) 
                     else k.rsum 
                   end bdrsum, 
                   case 
                     when k.f_val=643 and k.f_addnds=1 then 1 
                     else 0 
                   end wonds 
                 from 
                   (select s.f_dttmcr,s.f_id,s.f_specid,s.f_idoper,t.f_name oper,s.f_type_oper,s.f_sub_type_oper,s.f_parentid,t.f_c1doctype,
                      s.f_bdrarticle,s.f_nds,s.f_isvozm,s86.f_name stat,s84.f_name razd,".
          "           (select f_name from ".DBPref."spr where f_type=33 and f_num=ss.f_typez) typez,
                      ifnull(ss.f_num,'') ssnum,ifnull(ss.f_id,0) sfid,
                      ifnull(concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=clnt.f_opf),' ',clnt.f_cname),'') cname,
                      ifnull(concat((SELECT f_name FROM ".DBPref."spr WHERE f_type=137 AND f_num=org.f_opf),' ',org.f_cname),'') oname,
                      ifnull(ss.f_dirmain,'') ssdirmain,
                      #sdid1583
                      get_paidsum(s.f_id) paidsum,
                      get_expensessum(s.f_id) expensessum,
                      get_realizsum(s.f_id) realizsum,
                      get_debitor(s.f_id) debitor,
                      get_profit(s.f_id) profit,
                      #~sdid1583
                      ifnull(ss.f_postid,0) f_postid, ".
          "           s85.f_dopprint,s.f_sum,s.f_val,s.f_addnds,s.f_nodoccalc,".
          "           get_fesum(s.f_id) fesum,".
          "             get_rrsum(s.f_id,0) rrsum,".
          "             get_rrsum(s.f_id,1) rsum ".
          "from ".DBPref."spr s86,".DBPref."spr s85,".DBPref."spr s84,".DBPref."typeopers t,".DBPref."spec_invoices s  
           left join ".DBPref."specs ss on ss.f_id=s.f_specid
           left join ".DBPref."dogs d on d.f_id=ss.f_dogid
           left join ".DBPref."clients clnt on clnt.f_id=d.f_contrid
           left join ".DBPref."clients org on org.f_id=d.f_orgid
           where t.f_id=s.f_idoper and s86.f_type=86 and s85.f_type=85 and s86.f_num=s.f_bdrarticle and ".
          "  s.f_outbuhperiod=1 and ".
          "  s85.f_num=s86.f_uslint and s84.f_type=84 and s84.f_num=s85.f_dopprint and 
             s.f_parenttype=2 and s.f_specid in (".$spec_id.") and 
             #((s.f_parenttype=2 and s.f_specid in (".$spec_id.")) or s.f_id in (select f_objectid from ".DBPref."categs where f_ctgtype=24 and f_objecttype=5 and f_valstr in (".$spec_id."))) and 
             s.f_bdrarticle>0 order by s.f_specid,s.f_id)k) r ".
          "order by r.f_isvozm,r.f_type_oper,r.f_dopprint,r.f_sub_type_oper,r.stat"; 
                                //echo "$sql<br>";
    $csn++;
    $clntk = $csn;
    if($wphpword==1)
      {
    $res   = $dbh->query($sql);
    $sump  =0;$sumr=0;$owonds=0;$wonds=0;
    //$vozms =0;$nvozms=0;
    $topr  =0;$ownds=0;$lvozm=0;
    $oprsl =0;$widparentusldolgpp=0;
    while($row = $res->fetch(PDO::FETCH_ASSOC))
      {
      if($row['idparentusldolg']>0)
        {
        $lsql = "select 
                   k.oper,k.amount,k.val,k.insum,k.outsum,k.nds,k.vozm,k.faktsum,(k.insum-k.outsum) saldo,k.profitsum,k.fesum,k.sicom,k.f_id,k.wonds,k.f_isvozm,k.f_dttmcr
                 from (
                   select 
                     ltsi.f_name oper,
                     lsi.f_sum amount,
                     lsi.f_dttmcr,
                     case 
                       when lsi.f_val=643 and lsi.f_addnds=1 then 1 
                       else 0 
                     end wonds,
                     (select f_namedop from ".DBPref."spr where f_type=4 and f_num=lsi.f_val) val,
                     0 insum,
                     round(CAST(ifnull((select sum(lahd.f_clssum) from ".DBPref."acchist_docs lahd,".DBPref."acchist lah 
                                        where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lah.f_val=643 and 
                                          lah.f_ahtype=4),0) AS DECIMAL(15,3)),2) outsum,
                     (SELECT f_name FROM ".DBPref."spr where f_type=10 and f_num=lsi.f_nds) nds,
                     (SELECT f_uslstr FROM ".DBPref."spr where f_type=2 and f_num=lsi.f_isvozm) vozm,lsi.f_isvozm,
                     round(CAST(ifnull((select sum(lahd.f_clssum) from ".DBPref."acchist_docs lahd,".DBPref."acchist lah 
                                        where lah.f_id=lahd.f_acchistid and lahd.f_doctype=3 and lahd.f_docid=lsi.f_id and lah.f_val=643 and 
                                          lah.f_ahtype=4),0) AS DECIMAL(15,3)),2) faktsum,
                     0 profitsum,
                     0 fesum,
                     concat(case when length(lsi.f_invcom)=0 then '' else concat(lsi.f_invcom,'; ') end,lsi.f_com) sicom,
                     lsi.f_id
                   from ".DBPref."spec_invoices lsi,".DBPref."typeopers ltsi where ltsi.f_id=lsi.f_idoper and lsi.f_id=:specinvid) k";
        $lres = $dbh->prepare($lsql);
        $lres->bindParam(':specinvid',$row['idparentusldolg'],PDO::PARAM_INT);
        $lres->execute();
        if($lrow = $lres->fetch(PDO::FETCH_ASSOC))
          {
          $lvozm = "не опр";
          if($lrow['f_isvozm']==1)
            {$lvozm = "возмещ";}
          elseif($row['f_isvozm']==2)
            {$lvozm = "не возм";}
          $sheet->setCellValue("A".$csn, $row["specname"]);
          $sheet->setCellValue("B".$csn, substr($lrow["f_dttmcr"],0,10));
          $sheet->setCellValue("C".$csn, $lrow["oper"]);
          $sheet->setCellValue("D".$csn, $lvozm);
          if($lrow["wonds"]==1)
            {$sheet->setCellValue("E".$csn, "Да");}
          else
            {$sheet->setCellValue("E".$csn, "Нет");}
          $sheet->setCellValue("F".$csn, $lrow["val"]);
          $sheet->setCellValue("G".$csn, $lrow["amount"]);
          $sheet->setCellValue("H".$csn, $lrow["insum"]);
          $sheet->setCellValue("I".$csn, $lrow["outsum"]);
          $sheet->setCellValue("J".$csn, $lrow["outsum"]);
          $vozms=$vozms+$lrow["outsum"];
          $sheet->setCellValue("K".$csn, $lrow["outsum"]);
          $sheet->setCellValue("L".$csn, 0);
          $sheet->setCellValue("M".$csn, 0);
          $sheet->setCellValue("N".$csn, 0);
          $sheet->setCellValue("O".$csn, 0);
          //$sheet->setCellValue("Q".$csn, 1);
          }
        //$sheet->setCellValue("A".$csn, $row['idparentusldolg']);
        //$sheet->setCellValue("C".$csn, $lsql);
        $csn++;
        }
      if(($row['idparentusldolgpp']>0)&&($widparentusldolgpp==0))
        {
        $lsql = "";
        if(($row['ispayspecinvusldolg']>0))
          {
          $lsql = "select 
                     #concat('".$row['f_id']."_1_',k.oper) oper,
                     k.oper,
                     k.amount,k.val,k.insum,k.outsum,k.nds,k.vozm,k.faktsum,(k.insum-k.outsum) saldo,k.profitsum,k.fesum,k.sicom,k.f_id,k.wonds,k.f_isvozm,k.f_dttmcr
                   from (
                     select 
                       (select f_name from veda_typeopers where f_id=389) oper,
                       case 
                         when (select count(*) from veda_akts where f_operid=mlsi.f_id)>0 then 
                           (select f_sum from veda_akts where f_operid=mlsi.f_id)
                         when (select count(*) from veda_akts a,veda_akts_details ad,veda_akts_details_opers ado where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=mlsi.f_id)>0 then 
                           (select a.f_sum from veda_akts a,veda_akts_details ad,veda_akts_details_opers ado where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=mlsi.f_id)
                         else sum(ls.f_sum) 
                       end amount,
                       ls.f_dttmcr,
                       case 
                         when ls.f_val=643 and ls.f_addnds=1 then 1 
                         else 0 
                       end wonds,
                       (select f_namedop from ".DBPref."spr where f_type=4 and f_num=ls.f_val) val,
                       0 insum,
                       (-1)*PA_getsumusldolg(mlsi.f_id) outsum,
                       (SELECT f_name FROM ".DBPref."spr where f_type=10 and f_num=mlsi.f_nds) nds,
                       (SELECT f_uslstr FROM ".DBPref."spr where f_type=2 and f_num=mlsi.f_isvozm) vozm,ls.f_isvozm,
                       (-1)*PA_getsumusldolg(mlsi.f_id) faktsum,
                       0 profitsum,
                       0 fesum,
                       concat(case when length(mlsi.f_invcom)=0 then '' else concat(mlsi.f_invcom,'; ') end,mlsi.f_com) sicom,
                       mlsi.f_id
                     from ".DBPref."spec_invoices mlsi,veda_spec_invoices ls 
                     where ls.f_parenttype=2 and ls.f_specid=mlsi.f_specId and ls.f_itemcalcrp=mlsi.f_itemcalcrp and ls.f_idoper=389 and 
                       mlsi.f_id=:specinvid) k";
          }
        else
          {
          $lsql = "select 
                     #concat('2_',k.oper) oper,
                     k.oper,
                     k.amount,k.val,k.insum,k.outsum,k.nds,k.vozm,k.faktsum,(k.outsum-k.insum) saldo,k.profitsum,k.fesum,k.sicom,k.f_id,k.wonds,k.f_isvozm,k.f_dttmcr
                   from (
                     select 
                       tls.f_name oper,
                       ls.f_sum amount,
                       ls.f_dttmcr,
                       case 
                         when ls.f_val=643 and ls.f_addnds=1 then 1 
                         else 0 
                       end wonds,
                       (select f_namedop from ".DBPref."spr where f_type=4 and f_num=ls.f_val) val,
                       0 insum,
                       (-1)*get_rrsum(ls.f_id, 1) outsum,
                       (SELECT f_name FROM veda_spr where f_type=10 and f_num=ls.f_nds) nds,
                       (SELECT f_uslstr FROM veda_spr where f_type=2 and f_num=ls.f_isvozm) vozm,ls.f_isvozm,
                       (-1)*get_rrsum(ls.f_id, 1) faktsum,
                       0 profitsum,
                       0 fesum,
                       concat(case when length(ls.f_invcom)=0 then '' else concat(ls.f_invcom,'; ') end,ls.f_com) sicom,
                       ls.f_id
                     from ".DBPref."spec_invoices mlsi,".DBPref."spec_invoices ls,".DBPref."typeopers tls  
                     where tls.f_id=ls.f_idoper and ls.f_parenttype=2 and ls.f_specid=mlsi.f_specId and ls.f_itemcalcrp=mlsi.f_itemcalcrp and
                       ls.f_parentid=mlsi.f_parentid and tls.f_c1doctype=4 and 
                       ((select count(*) from ".DBPref."akts where f_operid=ls.f_id)>0 or 
                        (select count(*) from ".DBPref."akts a,".DBPref."akts_details ad,".DBPref."akts_details_opers ado 
                         where a.f_id=ad.f_aktid and ad.f_id=ado.f_akts_detailsid and ado.f_operid=ls.f_id)>0) and
                       mlsi.f_id=:specinvid) k";
          }
        if(strlen($lsql)>0)
          {
          //$sheet->setCellValue("C".$csn, $row['f_id']."|".$row['ispayspecinvusldolg']."|".$lsql);
          $lres = $dbh->prepare($lsql);
          $lres->bindParam(':specinvid',$row['f_id'],PDO::PARAM_INT);
          $lres->execute();
          if($lrow = $lres->fetch(PDO::FETCH_ASSOC))
            {
            $lvozm = "не опр";
            if($lrow['f_isvozm']==1)
              {$lvozm = "возмещ";}
            elseif($row['f_isvozm']==2)
              {$lvozm = "не возм";}
            $sheet->setCellValue("A".$csn, $row["specname"]);
            $sheet->setCellValue("B".$csn, substr($lrow["f_dttmcr"],0,10));
            $sheet->setCellValue("C".$csn, $lrow["oper"]);
            $sheet->setCellValue("D".$csn, $lvozm);
            if($lrow["wonds"]==1)
              {$sheet->setCellValue("E".$csn, "Да");}
            else
              {$sheet->setCellValue("E".$csn, "Нет");}
            $sheet->setCellValue("F".$csn, $lrow["val"]);
            $sheet->setCellValue("G".$csn, $lrow["amount"]);
            $sheet->setCellValue("H".$csn, $lrow["insum"]);
            $sheet->setCellValue("I".$csn, $lrow["outsum"]);
            $sheet->setCellValue("J".$csn, $lrow["outsum"]);
            $vozms=$vozms+$lrow["outsum"];
            $sheet->setCellValue("K".$csn, $lrow["outsum"]);
            $sheet->setCellValue("L".$csn, 0);
            $sheet->setCellValue("M".$csn, 0);
            $sheet->setCellValue("N".$csn, 0);
            $sheet->setCellValue("O".$csn, 0);
            //$sheet->setCellValue("Q".$csn, 2);
            }
          $widparentusldolgpp++;
          //$sheet->setCellValue("A".$csn, $row['f_id']);
          //$sheet->setCellValue("C".$csn, $lsql);
          $csn++;
          }
        }
      $fesum = $row['fesum'];
      if(($topr!=$row["f_type_oper"])||($lvozm!=$row['f_isvozm']))
        {
        if($topr>0)
          {
          $oprsl = round($oprsl,2);
          $vk=0;$vl=0;$vm=0;
          if($lvozm==1)
            {
            if($oprsl>0)
              {$vm=$oprsl;}
            elseif($oprsl<0)
              {$vl=-1*$oprsl;}
            }
          else
            {
            $vk = $oprsl;
            $prib=$prib-$vm;
            }
          }
        $oprsl = 0;
        }
        $topr = $row["f_type_oper"];
        $oprsl = $oprsl+$row["sumprih"]-$row["sumrash"];
        $sheet->setCellValue("A".$csn, $row["specname"]);
        $sheet->setCellValue("B".$csn, substr($row["f_dttmcr"],0,10));
        $sheet->setCellValue("C".$csn, $row["oper"]);
        $vozm = "не опр";
        if($row['f_isvozm']==1)
          {
          $vozm = "возмещ";
          //$vozms=$vozms+$row["sumrash"];
          $vozms=$vozms+$row["realizsum"];
          $vozmsr=$row["sumrash"];
          }
        elseif($row['f_isvozm']==2)
          {
          $vozm = "не возм";
          $nvozmsf=$nvozms+$row["sumprihf"];
          $vozmsr=$row["sumprihf"];
          if($row['f_dopprint']==1)                            //для невозмещаемого дохода выводим сумму операции
            {
            if($row['f_val']==643)
              {$vozmsr=$row["f_sum"];}
            }
          //$nvozms = $nvozms+$vozmsr;
          $nvozms = $nvozms+$row["realizsum"];
	  }
        else
          {
          $nvozmsf=$nvozms+$row["sumprihf"];
          $vozmsr=$row["sumprihf"];
          if($row['f_dopprint']==1)
            {
            if($row['f_val']==643){$vozmsr=$row["f_sum"];}
            else{$sumrash=$row["f_sum"]*getCBRate($row['f_sum'],substr($row["f_dttmcr"],0,10));}
            }
          }
        $sheet->setCellValue("D".$csn, $vozm);
        if($row["wonds"]==1)
          {$sheet->setCellValue("E".$csn, "Да");}
        else
          {$sheet->setCellValue("E".$csn, "Нет");}
        $sheet->setCellValue("F".$csn, $row["val"]);
        $sheet->setCellValue("G".$csn, $row["f_sum"]);
        //sdid1583
        if($row['f_idoper']==387)
          {$sheet->setCellValue("H".$csn, $row["paidsum"]);}
        elseif(($row['f_c1doctype']==18)and($row['f_dopprint']==1))
          {$sheet->setCellValue("H".$csn, 0);}
        elseif($row['f_idoper']==236)
          {$sheet->setCellValue("H".$csn, $row["paidsum"]);}
        else
          {$sheet->setCellValue("H".$csn, $row["sumprihf"]);}
        //~sdid1583
        $sumrash = $row["sumrash"];
        if(($row['f_nodoccalc']==1)&&($sumrash==0))
          {
          if($row['f_val']==643)
            {$sumrash=$row["f_sum"];}
          else{$sumrash=$row["f_sum"]*getCBRate($row['f_sum'],substr($row["f_dttmcr"],0,10));}
          }
        //sdid1583
        if($row['f_idoper']==386)
          {
          $sheet->setCellValue("I".$csn, $row['expensessum']);
          $sheet->setCellValue("J".$csn, $row['realizsum']);
          }
        elseif($row['f_idoper']==387)
          {
          $sheet->setCellValue("I".$csn, $row['expensessum']);
          $sheet->setCellValue("J".$csn, $row['realizsum']);
          $sheet->setCellValue("L".$csn, $row['profit']);
          }
        elseif(($row['f_idoper']==186)||($row['f_idoper']==236))
        //~sdid1583
          {
          $sheet->setCellValue("I".$csn, 0);
          $sheet->setCellValue("J".$csn, 0);
          $sheet->setCellValue("L".$csn, 0);
          }
        elseif(($row['f_c1doctype']==18)and($row['f_dopprint']==1))
          {
          $sheet->setCellValue("I".$csn, (-1)*$row["sumprihf"]);
          }
        else
          {
          $sheet->setCellValue("I".$csn, $sumrash);
          $sheet->setCellValue("J".$csn, $vozmsr);
          $sheet->setCellValue("L".$csn, ($vozmsr-$sumrash));
          }
        //sdid 637
        if(((($row['f_idoper']==237)||($row['f_idoper']==285))&&($row['f_isvozm']==2))||
           (($row['f_idoper']==186)||($row['f_idoper']==236)))
          {
          $sheet->setCellValue("J".$csn, 0);
          $sheet->setCellValue("L".$csn, 0);
          }
        elseif(($row['f_c1doctype']==18)&&($row['f_dopprint']==1))
          {
          $sheet->setCellValue("J".$csn, (-1)*$row["sumprihf"]);
          $sheet->setCellValue("L".$csn, 0);
          }
        elseif(($row['f_idoper']!=387)&&($row['f_idoper']!=386)&&($row['f_idoper']!=186)&&($row['f_idoper']!=236))
          {
          $sheet->setCellValue("J".$csn, $vozmsr);
          $sheet->setCellValue("L".$csn, ($vozmsr-$sumrash));
          }
        if((($row['f_idoper']==237)||($row['f_idoper']==285))&&($row['f_isvozm']==2))
          {
          $sheet->setCellValue("K".$csn, -$row["sumprihf"]);
          }
        //sdid1583
        elseif($row['f_idoper']==387)
          {
          $sheet->setCellValue("K".$csn, $row['debitor']);
          }
        elseif($row['f_idoper']==236)
          {
          $sheet->setCellValue("K".$csn, $row['debitor']);
          }
        else
          {
          $sheet->setCellValue("K".$csn, $vozmsr-$row["sumprihf"]);
          }
        //~sdid1583
        //~sdid 637
        //$sheet->setCellValue("I".$csn, $sumrash);
        //$sheet->setCellValue("J".$csn, $vozmsr);
        //$sheet->setCellValue("K".$csn, $vozmsr-$row["sumprihf"]);
        //$sheet->setCellValue("L".$csn, ($vozmsr-$sumrash));
        $sumpribil = $sumpribil+($vozmsr-$sumrash);
        $sumdoloc  = $sumdoloc +($vozmsr-$row["sumprihf"]);
        $sumpostdc = $sumpostdc+$row["sumprihf"];
        $sump=$sump+$row["sumprih"];
        $sumr=$sumr+$row["sumrash"];
        if($row['wonds']==1)
          {
          $owonds=$owonds+$row['bdrsum'];
          $ownds =$ownds+$row['rsum'];
          }
        if($row['f_nodoccalc']==1)
          {
          $sheet->getStyle("A".$csn.":N".$csn)->getFill()->applyFromArray(array(
        		'type' => PHPExcel_Style_Fill::FILL_SOLID,
        		'startcolor' => array(
        		'rgb' => 'F28A8C')));
          }
        $sheet->setCellValue("M".$csn, $fesum);
        if($row["wonds"]==1)
          {
          $sheet->setCellValue("N".$csn, ($row['sumrash']-$row['rrsum']));
          $sheet->setCellValue("O".$csn, $row['rrsum']);
          }
        else
          {
          $sheet->setCellValue("N".$csn, 0);
          $sheet->setCellValue("O".$csn, 0);
          }
        //sdid3548 - переделали все столбцы на хранимки
        $sheet->setCellValue("G".$csn, $row["f_sum"]);
        $sheet->setCellValue("H".$csn, $row['paidsum']);
        $sheet->setCellValue("I".$csn, $row['expensessum']);
        $sheet->setCellValue("J".$csn, $row['realizsum']);
        $sheet->setCellValue("K".$csn, $row['debitor']);
        $sheet->setCellValue("L".$csn, $row['profit']);
        $sheet->setCellValue("M".$csn, $row['fesum']);
        if($row["wonds"]==1)
          {
          $sheet->setCellValue("N".$csn, ($row['expensessum']-$row['rrsum']));
          $sheet->setCellValue("O".$csn, $row['rrsum']);
          }
        else
          {
          $sheet->setCellValue("N".$csn, 0);
          $sheet->setCellValue("O".$csn, 0);
          }
        //~sdid3548 - переделали все столбцы на хранимки
        $lvozm=$row['f_isvozm'];
        $csn++;
        }
      }
    $vk=0;$vl=0;$vm=0;
    if($lvozm==1)
      {
      if($oprsl>0)
        {$vm=$oprsl;}
      elseif($oprsl<0)
        {$vl=$oprsl;}
      }
    else
      {
      $vk=$oprsl;
      $prib=$prib-$vm;
      }
    if($wphpword==1)
      {
      if($csn==$clntk){$csn++;}
      $itg2 = $csn;
      $sheet->getStyle("A".$clntk.":O".$csn)->applyFromArray($borderi);
      $sheet->getStyle("A".$clntk.":O".$csn)->applyFromArray($bordero);
      $sheet->getStyle("A".$csn.":O".$csn)->getFont()->setBold(true);
      $sheet->setCellValue("C".$csn, "Итого корректировки");
      $sheet->setCellValue("H".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",H".$clntk.":H".($csn-1).")");
      $sheet->setCellValue("I".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",I".$clntk.":I".($csn-1).")");
      $sheet->setCellValue("J".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",J".$clntk.":J".($csn-1).")");
      $sheet->setCellValue("K".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",K".$clntk.":K".($csn-1).")");
      $sheet->setCellValue("L".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",L".$clntk.":L".($csn-1).")");
      $sheet->setCellValue("M".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",M".$clntk.":M".($csn-1).")");
      $sheet->setCellValue("N".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",N".$clntk.":N".($csn-1).")");
      $sheet->setCellValue("O".$csn, "=SUMIF(P".$clntk.":P".($csn-1).",\"<>1\",O".$clntk.":O".($csn-1).")");

      $sheet->setCellValue("B".$clvz, $vozms);
      $sheet->getStyle("B".$clvz.":B".$clvz)->getNumberFormat()->setFormatCode('#,##0.00');

      $sheet->setCellValue("B".$clnvz, $nvozms);
      $sheet->getStyle("B".$clnvz.":B".$clnvz)->getNumberFormat()->setFormatCode('#,##0.00');
      if(!$is_main_spec)
        {
        $sheet->getStyle("J".$csn)->getFill()->applyFromArray($yellow_color_parameters);
        }
      $csn++;$csn++;
      $sheet->getStyle("A".$csn.":O".$csn)->getFont()->setBold(true);
      $sheet->setCellValue("C".$csn, "Итого с учетом корректировок");
				$sheet->setCellValue("H".$csn, "=H".$itg1."+H".$itg2);
				$sheet->setCellValue("I".$csn, "=I".$itg1."+I".$itg2);
				$sheet->setCellValue("J".$csn, "=J".$itg1."+J".$itg2);
				$sheet->setCellValue("K".$csn, "=K".$itg1."+K".$itg2);
				$sheet->setCellValue("L".$csn, "=L".$itg1."+L".$itg2);
				$sheet->setCellValue("M".$csn, "=M".$itg1."+M".$itg2);
				$sheet->setCellValue("N".$csn, "=N".$itg1."+N".$itg2);
				$sheet->setCellValue("O".$csn, "=O".$itg1."+O".$itg2);

				if(!$is_main_spec)
				{
					$sheet->getStyle("J".$csn)->getFill()->applyFromArray($yellow_color_parameters);
				}
				}
				
				$csn=$csn+2;
				if($notonespec==0)
				{
				$csn=$csn+2;
				if($wphpword==1)
						{
						$sheet->setCellValue("A".$csn, "КАРТОЧКА СОГЛАСОВАНИЯ");
						$sheet->getStyle("A".$csn.":A".$csn)->getFont()->setBold(true);
						}
				$csn++;
				$csnbs=$csn;
				if($wphpword==1)
						{
						$sheet->setCellValue("A".$csn, "ФИО");
						$sheet->setCellValue("B".$csn, "Дата создания");
						$sheet->setCellValue("C".$csn, "Замечание");
						$sheet->setCellValue("D".$csn, "Статус");
						$sheet->getStyle("A".$csn.":D".$csn)->getFont()->setBold(true);
						}
				$sql = "select s.f_name st,h.f_status,h.f_dttmupd dttm,concat(u.f_name1,' ',u.f_name2) usr,h.f_comtoforol ".
								"from ".DBPref."specs_hist h,".DBPref."spr s,".DBPref."users u ".
								"where u.f_id=h.f_useridupd and s.f_type=6 and s.f_num=h.f_status and h.f_status in (2,7,8,14,6) and h.f_id=".$spec_id." ".
								"order by h.f_dttmupd,h.f_status";
				$pst = 0;
				$res = $dbh->query($sql);
				while($row = $res->fetch(PDO::FETCH_ASSOC))
						{
						if($pst!=$row['f_status'])
						{
						$csn++;
						if($wphpword==1)
								{
								$sheet->setCellValue("A".$csn, $row['usr']);
								$sheet->setCellValue("B".$csn, $row['dttm']);
								$sheet->setCellValue("C".$csn, $row['f_comtoforol']);
								$sheet->setCellValue("D".$csn, $row['st']);
								}
						$pst = $row['f_status'];
						}
						}
				if($wphpword==1)
						{
						$sheet->getStyle("A".$csnbs.":D".$csn)->applyFromArray($borderi);
						$sheet->getStyle("A".$csnbs.":D".$csn)->applyFromArray($bordero);
						$sheet->getStyle("A".$csnbs.":C".$csn)->getAlignment()->setWrapText(true);
						}
				if($wphpword==1)
				{
				$sheet->getStyle("F".$cln.":F".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("G2:G".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("H2:H".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("I2:I".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("J2:J".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("K2:K".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("L2:L".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("M2:M".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("N2:N".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				$sheet->getStyle("O2:O".$csn)->getNumberFormat()->setFormatCode('#,##0.00');
				}
				}
			}
		}
		if($wphpword==1)
		{
		$xls->setActiveSheetIndex(array_search($oprid, $spec_ids_array)); //Делаем открытой вкладку по спецификации, из которой была вызвана ф-ция РП
		$objWriter = new PHPExcel_Writer_Excel2007($xls);
//sdid 1123
                $fname = str_replace(",","_",$fname).".xlsx"; 
                $fname = str_replace("\\"," ",$fname);
                $fname = str_replace("/","_",$fname);  
                $fname = __DIR__ . "/download/".$fname;
                //$fname = __DIR__ . "/download/rps".$_SESSION['loginid']."_".date("His").".xlsx";
//~sdid 1123
		$objWriter->save($fname);
		
		file_force_download($fname);
		}
	}