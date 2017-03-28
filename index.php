<?php

// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.jianghuasheng.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 江华生 <jianghuasheng333gmail.com>  2017-03-27
// +----------------------------------------------------------------------
// | Desc: 本类为简单的SSE服务端的处理代码。参考了极客学院的课程代码。
// |       
// +----------------------------------------------------------------------


class SSEController{

	//声明静态变量保存数据库的连接信息
	private static $pdo = NULL;
	// 构造函数，这里保存连接数据库信息的变量值
	public function __construct(){
		// 判断是不是第一次链接数据库
		if (is_null(self::$pdo)) {
			try {
				$pdo = new PDO('mysql:host=localhost;dbname=sse', 'root', '',array(PDO::ATTR_PERSISTENT => true));
				// 设置编码
			    $pdo->query('SET NAMES UTF8');
			    // 保存到定义好的静态变量中
			    self::$pdo = $pdo;
			    // var_dump($pdo);
			} catch (PDOException $e) {
				// print "Error: " . $e->getMessage() . "<br/>";
				die("Content Error!");
			}
		}
	}
	// 主页面
	public function index(){
		// echo "index页面";
		include "./index.html";
	}

	// 获取数据库聊天记录
	public function getMsg(){
		// 表明是事件流
		header('Content-Type:text/event-stream');
		// 不要缓存
		header('Cache-Control:no-cache');
		// sql语句
		$sql = "SELECT * FROM msg ORDER BY id ASC";
		// 查询结果对象
		$result = self::$pdo->query($sql);
		// 结果结构化
		$rows = $result->fetchALL(PDO::FETCH_ASSOC);
		// 
		foreach ($rows as $k => $v) {
			$name = $v['name'];
			$msg = $v['msg'];
			$time = $v['time'];
			if ($k == 0) {
				echo "data:<li><b>&nbsp;&nbsp;&nbsp;{$name} : </b>{$msg}<i> ({$time})</i></li>";
			}else{
				echo "<li><b>&nbsp;&nbsp;&nbsp;{$name} : </b>{$msg}<i> ({$time})</i></li>";
			}
			
		}
		echo "\n\n";

		//立即将数据返回给客户端 ，@：忽略错误
		@ob_flush();@flush();   
	}

	// 新增聊天信息
	public function addMsg()
	{
		if (isset($_POST['name']) && isset($_POST['message'])) {
			$name = $_POST['name'];
		    $message = $_POST['message'];
		    //判断是否为空
		    if (empty($name) || empty($message)) {
		        // 不能为空
		        $ret = array('code' => 401,'msg'=>"亲，昵称或者聊天内容不可以为空哟！"); 
		        echo json_encode($ret);
		    	exit;
		    }else{
		    	$nowTime = date('Y-m-d H:i:s',time());
		    	// 执行插入数据库操作
		    	$Sql = "INSERT INTO msg (name,time,msg) values ('".$name."','".$nowTime."','".$message."');";
				$reslut = self::$pdo->exec($Sql);//返回影响了多少行数据
				if ($reslut) {
					//非法传参
				    $ret = array('code' => 200,'msg'=>"插入成功");  
				    echo json_encode($ret);
				    exit;
				}else{
					//非法传参
				    $ret = array('code' => 401,'msg'=>"401：亲，新增失败！亲您重试下...");  
				    echo json_encode($ret);
				    exit;
				}
		    }
		}else{
		    //非法传参
		    $ret = array('code' => 400,'msg'=>"非法传参");  
		    echo json_encode($ret);
		    exit;
		}
	}
}

$controller = new SSEController;
// 在URL那里传参?a=来控制要执行类的方法，默认为index方法
// 用法eg：?a=getMsg    执行类的getMsg方法
$action = isset($_GET['a']) ? $_GET['a'] : 'index';
$controller->$action();
?>