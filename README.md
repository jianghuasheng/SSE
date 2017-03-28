# SSE+ajax实现无刷新服务器实时推送功能 #

> 这个小功能主要是大家平时用的聊天软件的底层技术，通过页面无刷新和服务器长连接的技术无刷新地去推送最新的消息。对于实时推的技术，主要有HTML5中的EventSource和Websocket事件。之前做过一个订单提醒功能，最要是客户端运用ajax进行轮询，但是这个对服务器造成的压力很大，浪费内存资源等等。所以，这里探讨下EventSource的简单使用。
> 这里主要用到的技术有php的PDO、SSE（HTML5 服务器发送事件server-sent event，EventSource）、ajax和js框架zepto。下面主要介绍下实现的流程：

> 这里是案例**测试地址**：<a href="http://exam.jianghuasheng.cn/sse/index.html" target="_blank">http://exam.jianghuasheng.cn/sse/index.html</a>

## 实现该功能主要分为二个主要部分： ##

### 一、无刷新取出聊天记录（SSE、EventSource） ###
> 取出聊天记录而且页面无刷新，以前第一想到的就是用ajax去轮询接口拉取最新的数据，但是在HTML5中出了服务器发送事件（server-sent event），就是允许网页自动获得来自服务器的更新。具体的介绍可以看下官方的文档[http://www.w3school.com.cn/html5/html_5_serversentevents.asp](http://www.w3school.com.cn/html5/html_5_serversentevents.asp "http://www.w3school.com.cn/html5/html_5_serversentevents.asp")。
------------------------
> **这里主要看下他的原理和怎样去运用：**
> W3C:HTML5 服务器发送事件（server-sent event）允许网页获得来自服务器的更新。
> 下面主要看下他的应用：

- **关键代码区**

``` javascript
// 检测是否支持EventSource事件
if(typeof(EventSource)!=="undefined"){
	// SSE 服务器推事件
	var es = new EventSource("index.php?a=getMsg");
	// console.log(es);
	// 对返回的数据进行处理
	es.onmessage=function(e){
	  	document.getElementById('ul').innerHTML=e.data;
	  	// 布局定位
	  	var h = document.getElementById('ul').offsetHeight;
	  	var h2 = document.body.offsetHeight;
	  	// 自动滚动到底部
	  	document.getElementById('con').scrollTop=h-h2;
	};
}else{
	alert("亲，您的浏览器暂不支持该功能哟，请您换个浏览器试试哈^^");
}
``` 
> 使用EventSource事件很简单，直接new一个EventSource对象就可以了，但是IE浏览器不支持该事件，所以要判断下不支持的的情况下的提醒或者处理，这里直接是提醒不支持^^，如果是IE浏览器可以直接用户原来的那个ajax轮询的模式。

- **下面是EventSource事件的一些属性截图:**
![EventSource属性](http://on225liw3.bkt.clouddn.com/sse_sse.png)
![EventSource属性](http://on225liw3.bkt.clouddn.com/sse_sse_heaer.png)
![EventSource属性](http://on225liw3.bkt.clouddn.com/sse_sse_ajax.png)
- **再来看看用ajax提交的时候的情况：**
![EventSource属性](http://on225liw3.bkt.clouddn.com/sse_ajax.png)
![EventSource属性](http://on225liw3.bkt.clouddn.com/sse_ajax_header.png)

> 细心的你可能会发现，其实ajax和EventSource事件差不多，就是EventSource事件会比较容易使用。当然，他们还是有区别的。EventSource事件，就是浏览器通过HTTP向服务器发送请求，服务器端拿出数据库中的数据，立即返回给客户端，客户端等待三秒后再次发出下一个请求。其实我个人理解功能上就是可以想成每隔三秒执行下ajax，但是却没有ajax那么复杂的流程。


### 三、后端处理代码(PDO) ###
> 这里后台主要运用了PDO对象来操作数据库。不懂得什么是PDO的可以看下这里[http://blog.csdn.net/j_h_s/article/details/67644330](http://blog.csdn.net/j_h_s/article/details/67644330 "PHP5中PDO的简单使用")和官方文档[http://www.php.net/manual/zh/pdo.connections.php](http://www.php.net/manual/zh/pdo.connections.php "http://www.php.net/manual/zh/pdo.connections.php")。我直接上整个处理类的的代码吧^^

``` php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.jianghuasheng.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 江华生 <jianghuasheng333gmail.com>  2017-03-27
// +----------------------------------------------------------------------
// | Desc: 本类为简单的SSE服务端的处理代码。参考了极客学院的课程代码。
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

``` 

## 总结： ##

- 这个小功能比较简单，主要是对HTML 5 服务器发送事件EventSource的使用和原理的简单介绍以及php的PDO的简单使用。
- 后台的php的代码架构参考了极客学院的课程的封装思想。觉得挺不错的，所以直接贴上去大家交流下。
- 当然，这个功能还有很多小细节要去思考下的。例如以下几点:
 1. 怎样去兼容IE浏览器？其实就是用ajax轮询应该也可以吧。
 2. 聊天输入框不能插入图片和表情包，嗯，这个得好好研究下^^。
 3. 如何去艾特或者回复特定的人的设计等等。

嗯，就写这么多了，下次和大家一起学习下WebSockets的原理和使用^^。<br>

> 这里是案例**测试地址**：<a href="http://exam.jianghuasheng.cn/sse/index.html" target="_blank">http://exam.jianghuasheng.cn/sse/index.html</a>