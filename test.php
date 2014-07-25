<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>S3 tutorial</title>
    </head>

<body>
   
    <?php
        $link = mysql_connect('myassignment2.cwtroerdyybo.us-east-1.rds.amazonaws.com:3306','assignment2_user','password');
        if(!$link){
            print("Error Connecting to the database");
        }
          
        $db_connect = mysql_select_db("cloud2",$link);
        if(!$db_connect){
            print("Error connecting to the Database Table");
        }
      
        
        //include the S3 class
        if (!class_exists('S3'))require_once('S3.php');
        
        //AWS access info
        if (!defined('awsAccessKey')) define('awsAccessKey', '');
        if (!defined('awsSecretKey')) define('awsSecretKey', '');
        
        //instantiate the class
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $fArray = array();

        if(isset($_GET['fileToDelete1'])){
            $fDel = $_GET['fileToDelete1'];
	     mysql_query("delete from ratings where name='{$fDel}'",$link);
            print("$fDel");
		    if ($s3->deleteObject("myyoutube23", $fDel)) {
            	    	echo "S3::deleteObject(): Deleted file\n";
            	    }
            	    else {
                	echo "S3::deleteObject(): Failed to delete file\n";
            	    }
        }

        if(isset($_POST['submitRate'])){    
            $tempVar = $_POST['group1'];
            $tempVar2 = $_POST['fileNameRate'];         
            $sqlVar = mysql_query("update ratings set rating=(rating+$tempVar)/2 where name = '$tempVar2'", $link);
          
            $rateVar = mysql_query("select rating from ratings where name='$tempVar2'", $link);
            print("The rating of $tempVar2 is $rateVar");  
	       while($row = mysql_fetch_array($rateVar)){
                echo $row['rating'];
            } 
        } 
         
        //check whether a form was submitted
        if(isset($_POST['submit1'])){    
            //retreive post variables
            $fileName = $_FILES['theFile']['name'];
            $fileTempName = $_FILES['theFile']['tmp_name'];
            
            //check for file extensions at server side
            $allowed =  array('mp4','flv');
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            if(!in_array($ext,$allowed) ) {
                echo '<script type="text/javascript">alert("Only mp4 and flv file format uploads allowed")</script>';
            }else{
                //create a new bucket
                $s3->putBucket("myyoutube23", S3::ACL_PUBLIC_READ);
                
                //move the file
                if ($s3->putObjectFile($fileTempName, "myyoutube23", $fileName, S3::ACL_PUBLIC_READ)) {
                    $test = "insert into ratings values('$fileName',now(),0)";
                    $insert = mysql_query($test,$link) or die ('error:'.mysql_error());
        
                    mysql_query($db_connect, "commit");
                    echo "<strong>Successfully uploaded your file.</strong>";
                }else{
                    echo "<strong>Something went wrong while uploading your file... sorry.</strong>";
                }
            }
        } 
        
echo <<<EOD
<h1>Upload a file</h1>
<p>Please select a file by clicking the 'Browse' button and press 'Upload' to start uploading your file.</p>
    <form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
      <input name="theFile" type="file" accept="video/mp4,video/x-flv"/>
      <input name="submit1" type="submit" value="Upload">
    </form>
<h1>All uploaded files</h1>
 <div id="abc">
EOD;

    
    // Get the contents of our bucket
    $fname;
    $contents = $s3->getBucket("myyoutube23");
    $index =0;
	$orderRet = mysql_query('select * from ratings order by rating DESC',$link);
	if(!$orderRet){
		die('Could not get data: '.mysql_error());
	}
	while($rows1 = mysql_fetch_array($orderRet)){
	
    	$fname = $rows1['name'];
        $furl = "http://myyoutube23.s3.amazonaws.com/".$fname;
    	$ratingFile = $rows1['rating'];
        print "<h3>$fname</h3><br/> \n";
    	echo "<a href=\"#file$index\" >Play</a>&nbsp; \n";
    	
        echo "<a href=\"test.php?fileToDelete1={$fname}\" >Delete File</a><br/> \n";
        echo "<a href=\"#dldfile$index\" >Play Download</a><br/> \n";
        print("<form name=\"rating$index\" action=\"\" method=\"POST\">
        <div align=\"left\"><br>
        <input type=\"radio\"  name=\"group1\" value=\"1\"  checked> 1
        <input type=\"radio\"  name=\"group1\" value=\"2\"> 2
        <input type=\"radio\"  name=\"group1\" value=\"3\"> 3
        <input type=\"radio\"  name=\"group1\" value=\"4\">4
        <input type=\"radio\"  name=\"group1\" value=\"5\"> 5
        <input name=\"submitRate\" type=\"submit\" value=\"Rate\">
        <input name = \"fileNameRate\" type=\"hidden\" value =\"$fname\">
        <p> Rating is $ratingFile </p>
        </div>
        </form>"
        );

        //output a link to the file
        print "<div id=\"file$index\" class=\"toggle\" style=\"display:none\"><div id='mediaplayer$index'></div></div> \n";
        print "<div id=\"dldfile$index\" class=\"toggle\" style=\"display:none\"><div id='dldmediaplayer$index'></div></div> \n";


        $index++;
    }
    echo "</div>";
    $index =0;
     
    foreach ($contents as $file){
        $fname = $file['name'];
        $fArray['file'.$index] = $fname;
        $index++;
    }

    if (($info = $s3->getObjectInfo("myyoutube23", "trailer.mp4")) !== false) {
        print_r($info);
    }
    $flink = "rtmp://sp2qb1x4h321f.cloudfront.net/cfx/st/".$fname;
?>




<script type='text/javascript' src='https://d3mrr666mxbr2v.cloudfront.net/jwplayer.js'></script>
<script type='text/javascript' src="jquery-1.10.2.min.js"></script>
<script type="text/javascript">
<?php
    $js_array = json_encode($fArray);
?>

$(document).ready(function () {
   
  // Function code here.
    $("#abc a").click(function(e){
    
        e.preventDefault();
        $(".toggle").hide();
        var toShow = $(this).attr('href');
        var toShow_copy = toShow;
        var filename = toShow_copy.replace('#','');
        if(filename[0]!='d'){
            var vlink = "rtmp://sp2qb1x4h321f.cloudfront.net/cfx/st/"+javascript_array[filename];
            filename = filename[filename.length-1];
            $(toShow).show();   
            jwplayer('mediaplayer'+filename).setup({file: vlink,width: "720",height: "480"});
        }
        if(filename.slice(0,3)=='dld'){
            var toShow = "#"+filename;
            filename = filename.slice(3,filename.length);
	        var dlink = "http://s3.amazonaws.com/myyoutube23/"+javascript_array[filename];
	        alert(dlink);
            filename = filename[filename.length-1];   
            jwplayer('dldmediaplayer'+filename).setup({file: dlink,width: "720",height: "480"});
	       alert('dldmediaplayer'+filename);
            $(toShow).show(); 
        }
        else{
        /*
        var deleteIndex = filename.slice(3,filename.length);
        var fileToDelete = javascript_array[deleteIndex];
        var delform = document.getElementById('deleteFileform');
        delform.action = "";
        document.getElementById('DFileName').value = fileToDelete;
        alert(document.getElementById('DFileName').value);
        window.location.href = "test.php?delete123=fileToDelete;
        */
        }

});
});

</script>
</body>
</html>