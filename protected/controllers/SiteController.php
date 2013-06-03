<?php

class SiteController extends Controller
{
	
	private $excel_db = 'phpexcel';
	private $excel_files = 'excel_files';
	private $excel_sheets = 'excel_sheets';
	private $excel_columns = 'excel_columns';
	
	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		//modify
		set_time_limit(0);
		//ini_set('memory_limit', '512M');
		Yii::import('application.vendors.*');
		//echo Yii::getPathOfAlias('application.vendors');exit;
		//解除Yii的自动加载，与PHPExcel自带的autoload冲突
		spl_autoload_unregister(array('YiiBase','autoload'));
		require_once 'PHPExcel/PHPExcel.php';
		$filepath = Yii::getPathOfAlias('application.data').DIRECTORY_SEPARATOR.'test.xlsx';
		if(!file_exists($filepath)){
			throw new Exception('file not exists!');
		}
		$objPHPExcel = PHPExcel_IOFactory::load($filepath);
		$s = microtime(1);
		//$sheetData = $objPHPExcel->getSheet(2)->toArray(null,true,true,true);var_dump($sheetData);echo microtime(1)-$s;exit;
		$sheetCount = $objPHPExcel->getSheetCount();	
		for ($c=0;$c<=$sheetCount;$c++){
			ob_start();
			$currentSheetID = $c;
			$currentSheet = $objPHPExcel->getSheet($currentSheetID);
			$row_num = $currentSheet->getHighestRow();
			$col_num = $currentSheet->getHighestColumn();
			$rows = array();
			for ($i=1;$i<=$row_num;$i++){
				for ($j='A';$j<$col_num;$j++){
					$address = $j.$i;
					$rows[$i][$j] = $currentSheet->getCell($address)->getFormattedValue();
				}
			}
			if($rows != null){
				var_dump($en_str = JSON_ENCODE($rows));
				$de_arr = JSON_DECODE($en_str,true);  //加上第二个参数true表示将JSON对象强制转化为关联数组
				var_dump($de_arr);
				exit;
			}
			//comment
			echo $c,"<br/>";
			var_dump($rows);
			ob_end_flush();
			unset($rows);
			unset($currentSheet);
		}
		spl_autoload_register(array('YiiBase','autoload'));
		//var_dump($sheetArray);
		//var_dump($rows);echo microtime(1)-$s;
		//$sheets = $objPHPExcel->getSheetNames();
		exit;
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'
		
		//重新注册Yii的autoload
		spl_autoload_register(array('YiiBase','autoload'));
		$this->render('index');
	}
	
	/*
	 * 处理上传excel文件
	 */
	public function actionUpload(){
		//加载模型
		$model = new UpFile();
		
		if(isset($_POST['UpFile'])){
			
			//获取上传文件对象
			$tmpFile = CUploadedFile::getInstance($model,'excelfile');
			
			if(empty($tmpFile)){
				//这里需要使用一个更好地错误提示，同时前端也做一个检验用户是否提交文件
				$this->redirect('upload',array('model'=>$model));
				//exit;
			}
			
			//获取文件的基本信息
			$model->fileName = mb_convert_encoding($tmpFile->name,'gbk','UTF-8');  //跨平台的字符集考虑，待修改
			
			$model->fileType = $tmpFile->extensionName;

			$model->fileSize = $tmpFile->size;
			
			$baseUrl =Yii::app()->basePath;
			
			$model->filePath = $baseUrl.Yii::app()->params['uploadPath'].$model->fileName;
			//echo $model->filePath;exit;
			
			//验证文件信息
			if($model->validate()){
				//将临时文件转存
				if($tmpFile->saveAs($model->filePath)){
					
					//文件信息存入数据库中
					//$model->save();
					//引入application.vendors.PHPExcel第三方库
					Yii::import('application.vendors.*');
					spl_autoload_unregister(array('YiiBase','autoload'));
					require_once 'PHPExcel/PHPExcel.php';
					//对转存后的文件进行处理
					
					
					
					//re-register in Yii
					spl_autoload_register(array('YiiBase','autoload'));
					
					//显示上传成功
					$this->redirect('upload',array('model',$model));
				}
			}
				
		}
		
		$this->render('upload',array('model'=>$model));
		
	}
	
	/**
	 * 初始化数据库
	 */
	public function actionInitDB(){
		//header("Content-Type:text/html;charset=utf-8");
		//这里可以用yii dao来初始化数据库
		//yii dao 允许一条sql语句执行多次query
		
		$conn = Yii::app()->db; //继承自CDbConnection类，connectString来自配置文件/config/main.php
		
		$sql = "DROP DATABASE IF EXISTS `$this->excel_db`;
				CREATE DATABASE IF NOT EXISTS `$this->excel_db` DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci;
				CREATE TABLE `$this->excel_db`.`$this->excel_files` (
  				`ID` int(10) NOT NULL auto_increment,
  				`fileName` nvarchar(50) NOT NULL,
  				`filePath` varchar(50) NOT NULL,
  				`uploadTime` varchar(18) NOT NULL default '0000-00-00 00:00',
  				`userIp` varchar(16) NOT NULL default '0.0.0.0',
  				`fileType` varchar(5) NOT NULL default 'xlsx',
  				`lastModifyTime` varchar(18) NOT NULL default '0000-00-00 00:00',
  				`lastModifyUserIp` varchar(16) NOT NULL default '0.0.0.0',
  				PRIMARY KEY  (`ID`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci;
				CREATE TABLE `$this->excel_db`.`$this->excel_sheets` (
  				`ID` int(10) NOT NULL auto_increment,
  				`fileID` int(10) NOT NULL,
  				`sheetTitle` nvarchar(50) NOT NULL,
  				`sheetTableName` varchar(25) NOT NULL,
  				PRIMARY KEY  (`ID`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci;
				CREATE TABLE `$this->excel_db`.`$this->excel_columns` (
  				`ID` int(10) NOT NULL auto_increment,
  				`tableID` int(10) NOT NULL,
  				`columnTitle` nvarchar(50) NOT NULL,
  				`columnName` varchar(25) NOT NULL,
  				PRIMARY KEY  (`ID`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci;";
		
		try {
			$command = $conn->createCommand($sql);  //继承自CDbCommand,准备执行sql语句的命令
			$command->execute();  //执行no-query sql
		} catch (Exception $e) {
			echo "初始化数据库出错:","<br />";
			print_r($e->getMessage());
			exit();
		}
		
		
		//$result = $command->queryAll();  //执行会返回若干行数据的sql语句，成功返回一个CDbDataReader实例，就是一个结果集
		//var_dump($result);
		
	}
	

}