<?php

class FmController extends Zend_Controller_Action {

    public function init() {
        $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
        $filePath = $config->toArray();
        $this->_downloadsDir = $filePath['sharedFilePath'];
        
        $this->_users = new Application_Model_User;
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        if (!Zend_Auth::getInstance()->getIdentity()) {
            $this->_helper->redirector('login', 'user');
        }
        $this->_fm = new Application_Model_Fm;
    }
    
    public function indexAction() {
        $this->view->categories = $this->_fm->getCategory();        
    }
   
    /*public function downloadAction() {        
         $file = $this->_fm->getFileById($this->_getParam('id'));
         $path = $this->_downloadsDir . DIRECTORY_SEPARATOR . $file['category_id']. DIRECTORY_SEPARATOR. $file['url'];        
         header('Content-Type:  application/octet-stream');	
         header("Content-Disposition: attachment; filename={$file['url']}");
	 readfile($path);
 
	 // disable layout and view
	 $this->view->layout()->disableLayout();
	 $this->_helper->viewRenderer->setNoRender(true);
    }*/
    
    public function downloadAction() {        
         // disable layout and view
	 $this->view->layout()->disableLayout();
	 $this->_helper->viewRenderer->setNoRender(true);
         
         if (!in_array('download_files', unserialize($this->_auth->permission))) {
            echo 'You do not have permission to download this file!';
            return;
         } 
         $file = $this->_fm->getFileById($this->_getParam('id'));
         $path = $this->_downloadsDir . DIRECTORY_SEPARATOR . $file['category_id']. DIRECTORY_SEPARATOR. $file['url'];      
         $thisFile = new Application_Service_Upload_PsendUploadFile;
         $fileSize = $thisFile->get_real_size($path);
        
         if (file_exists($path)) {
            while (ob_get_level())
                ob_end_clean();
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($path));
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Cache-Control: private', false);
                header('Content-Length: ' . $fileSize);
                header('Connection: close');
                readfile($path);           
        } else {
            header("HTTP/1.1 404 Not Found");							
        }
    }
    
    public function previewAction() {
        
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        if (!in_array('download_files', unserialize($this->_auth->permission))) {
            echo 'You do not have permission to preview this file!';
            return;
        } 
        $mimeType = array('.jpg' => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.gif' => 'image/gif', '.png' => 'image/png', '.pdf' => 'application/pdf', '.mp3' => 'audio/mpeg',
            '.mp4' => 'video/mp4');
        $file = $this->_fm->getFileById($this->_getParam('id'));
        $ext = strrpos($file['url'], '.');
        $fileExt = substr($file['url'], $ext);
        $path = $this->_downloadsDir . DIRECTORY_SEPARATOR . $file['category_id'] . DIRECTORY_SEPARATOR . $file['url'];
        $thisFile = new Application_Service_Upload_PsendUploadFile;
        $fileSize = $thisFile->get_real_size($path);        
        
       /* if (file_exists($path)) {
            while (ob_get_level())
                ob_end_clean();
                header('Content-Type:' . $mimeType[$fileExt]);               
              //  header('Expires: 0');
               // header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
               // header('Pragma: public');
               // header('Cache-Control: private', false);
                header('Content-Length: ' . filesize($file));
                header('Connection: close');
                readfile($path);           
        } else {
            header("HTTP/1.1 404 Not Found");							
        }*/
        
        if (file_exists($path)) {
             while (ob_get_level())
                ob_end_clean();
                header('Content-Type:' . $mimeType[$fileExt]);
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Cache-Control: private', false);
                header('Content-Length: ' . filesize($path));
                readfile($path);   
        }else {
            header("HTTP/1.1 404 Not Found");							
        }
      
    }

    public function uploadAction() {
        $this->view->flashMessage = $this->_helper->flashMessenger->getMessages();
        //first check if the user has the prividlege
        if(!in_array('upload_files', unserialize($this->_auth->permission))){
            $this->view->forbidden = 'You do not have permission to upload files!';
        }
        //$this->view->headLink()->appendStylesheet('/public/css/base.css');
    }

    public function ajaxKeepAliveAction() {
        $random = rand(1, 1000000);
        $timestamp = preg_replace('/[^0-9]/', '', $_GET['timestamp']);
        echo $timestamp . '-' . $random;
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function processUploadAction() {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // Settings
        $targetDir = $this->_downloadsDir . DIRECTORY_SEPARATOR;

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds

        @set_time_limit(5 * 60); //5 mins execution time
        // Uncomment this one to fake upload time
        //usleep(5000);
        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
        $originalFileName = $fileName;

        $thisFile = new Application_Service_Upload_PsendUploadFile;

        // Rename the file
        $fileName = $thisFile->safe_rename($fileName);
        // Make sure the fileName is unique but only if chunking is disabled
        $fileExists = $this->_fm->getFileByUrl($fileName);
      //  echo '<pre>';
       // echo "chunks size: $chunks";
        //var_dump($fileExists);
        //if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
        if ($fileExists) {
           
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
           // while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
           // echo '--<br>';
            //var_dump($this->_fm->getFileByUrl($fileName_a . '_' . $count . $fileName_b));
            while ($this->_fm->getFileByUrl($fileName_a . '_' . $count . $fileName_b) )
                $count++;

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;


// Create target dir
        if (!file_exists($targetDir))
            @mkdir($targetDir);

// Remove old temp files	
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }

            closedir($dir);
        }
        else
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');


// Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

        if (isset($_SERVER["CONTENT_TYPE"]))
            $contentType = $_SERVER["CONTENT_TYPE"];

// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
              //  chmod("{$filePath}.part", 0755);
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    }
                    else
                        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    fclose($in);
                    fclose($out);

                    @unlink($_FILES['file']['tmp_name']);
                }
                else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
            else
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
        } else {

            // Open temp file
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                }
                else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

                fclose($in);
                fclose($out);
            }
            else
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

// Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off 
            rename("{$filePath}.part", $filePath);
            //get file size
            $fileSize = $thisFile->get_real_size($filePath);
            $size = $thisFile->format_file_size($fileSize);
         
            //save the uploaded file to database
            $data = array('url' => $fileName, 'title' => $originalFileName, 'size' => $size, 'uploader' => $this->_auth->firstname . ' ' . $this->_auth->lastname);
            // var_dump($data);
            $this->_fm->saveSharedFiles($data);
        }


// Return JSON-RPC response
        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id", "NewFileName" : "' . $fileName . '","originalFileName" : "' . $originalFileName . '","filePath" : "' . $targetDir . DIRECTORY_SEPARATOR . '"}');
//die('{"jsonrpc" : "2.0", "result" : {"cleanFileName": "'.$fileName.'"}, "id" : "id"}');

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function categorizeAction() {
       // $this->view->headLink()->appendStylesheet('/public/css/base.css');
        if (isset($_POST['uploader_count']) & $_POST['uploader_count'] > 0) {
            $this->view->categories = $this->_fm->getCategory();

            for ($i = 0; $i < $_POST['uploader_count']; ++$i) {
                if ($_POST['uploader_' . $i . '_status'] == 'done') {
                    $file[] = $this->_fm->getFileByName($_POST['uploader_' . $i . '_name']);
                }
            }
            $this->view->files = $file;
        }
    }
    
    public function deleteAction() {
        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        if (isset($_POST['id'])) {            
            //first check if the user has the prividlege
            if(!in_array('delete_files', unserialize($this->_auth->permission))){
                echo 'You do not have permission to delete this file!';                
                return;
            }            
            //first delete the physical file
            $thisFile =  $this->_fm->getFileById($_POST['id']);
            $filePath =  $this->_downloadsDir . DIRECTORY_SEPARATOR . $thisFile['category_id'] .  DIRECTORY_SEPARATOR  .$thisFile['url'];
           
            if ($thisFile && file_exists($filePath)) {
                if (unlink($filePath)) {
                    //delete from database
                    if ($this->_fm->deleteFile($_POST['id']))
                        echo 'success';
                } 
            }         
        }
       
    }

    public function saveCategoryAction() {
               
        foreach ($_POST['file'] as $file) {
            $data = array('title' => $file['title'], 'category_id' => $file['category'], 'description' => $file['description']);
            $update = $this->_fm->updateSharedFiles($data, $file['id']);
            if ($update) {
                $currentFile = $this->_fm->getFileById($file['id']);
                //move them to the category dir
                $from = $this->_downloadsDir . DIRECTORY_SEPARATOR. $currentFile['url'];
                $toDirectory = $this->_downloadsDir . DIRECTORY_SEPARATOR. $data['category_id'];
                if (!file_exists($toDirectory))
                    @mkdir($toDirectory);
                 
                $to = $toDirectory.DIRECTORY_SEPARATOR. $currentFile['url'];
                rename($from, $to);
                
                /*if (rename($from, $to)) {
			chmod($this->path, 0644);			
		}*/
            }
        }
        
        $this->_helper->flashMessenger->addMessage("File(s) uploaded successfully.");
        $this->_helper->redirector('upload', 'fm');
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function fileListAction() {
       // $this->view->targetDir = $this->_downloadsDir . DIRECTORY_SEPARATOR;
        $this->view->categories = $this->_fm->getCategory();  
         $this->view->categoryId = $this->_getParam('id');
        $this->view->files = $this->_fm->getFiles($this->_getParam('id'));
       
    }
    
    public function salesFileListAction() {
       // $this->view->targetDir = $this->_downloadsDir . DIRECTORY_SEPARATOR;
        $this->view->categories = $this->_fm->getCategory();  
        $this->view->categoryId = $this->_getParam('id');
        $this->view->files = $this->_fm->getSalesFiles($this->_getParam('id')); 
        
        
        $ns = new Zend_Session_Namespace('Default');
        $this->view->selectedFiles = array();
        if(isset($ns->item)) {            
            $this->view->selectedFiles = $ns->item;
        }    
       // var_dump($this->view->selectedFiles);
    }
    public function createSharedListAction() {
      
        $ns = new Zend_Session_Namespace('Default');
        if (isset($_POST['action']) && $_POST['action'] == 'reset') {
           $ns->unsetAll();
        } 
        if (isset($_POST['action']) && $_POST['action'] == 'change') {
            if ($_POST['item'] && $_POST['item'] == "true") {               
                if (!isset($ns->item) || !in_array($_POST['fileId'], $ns->item)) {                    
                    $ns->item[] = $_POST['fileId'];
                }
            }
            if ($_POST['item'] == "false") {                
                $key = array_search("{$_POST['fileId']}", $ns->item);                
                unset($ns->item[$key]);                
            }            
        } 
             
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function checkoutAction() {        
        $ns = new Zend_Session_Namespace('Default');
      //  var_dump($ns->item);
        foreach ($ns->item as $id) {            
           $files[] = $this->_fm->getSalesFileById($id);    
        }
        $this->view->files = $files;
        $this->view->repEmail = $this->_auth->email;
    }
    
    public function categoryAction() {        
        if (!in_array('manage_file_category', unserialize($this->_auth->permission))) {
             $this->view->forbidden =  'You do not have permission to change categories!';               
        }
        $this->view->categories = $this->_fm->getCategory();  
        if ($this->getRequest()->isPost()){
           $data = array("parent_id" => $_POST['category_id'], 'name' => $_POST['name']);
           if (is_numeric($this->_fm->saveCategory($data))) {
               $this->view->message = "Category created successfully!";
               $this->_helper->redirector('category', 'fm');
           } else {
              $this->view->message = "Category created failed!";
           }
        }
    }
    
    public function editCategoryAction() {
        $this->view->categories = $this->_fm->getCategory();  
        if ($this->getRequest()->isPost()){            
            if (isset($_POST['action']) && $_POST['action'] == 'delete') {
                $data = array ('category_id' => $_POST['category_id'], 'status' => 0, 'date_modified' => date("Y-m-d H:i:s") );            
                if ($this->_fm->removeCategory($data, $_POST['category_id'])) {
                    echo 'success';
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(TRUE);
                }
            }
            if ( isset($_POST['action']) && $_POST['action'] == 'rename') {
                if(trim($_POST['name']) == '') {
                    echo 'Category name is required';
                } else {
                    $data = array ('category_id' => $_POST['category_id'], 'name' => $_POST['name'],
                    'date_modified' => date("Y-m-d H:i:s"));
                    if ($this->_fm->updateCategory($data, $_POST['category_id'])) {
                        echo 'success';  
                        $this->_helper->layout()->disableLayout();
                        $this->_helper->viewRenderer->setNoRender(TRUE);
                    }
                }
            }
                            
            //$this->_helper->layout()->disableLayout();
            //$this->_helper->viewRenderer->setNoRender(TRUE);
        }
    }
    
    public function editCategoryNameAction() {
        $this->view->category = $this->_fm->getCategoryById($_POST['category_id']);  
        $this->_helper->layout()->disableLayout();
    }
    
    public function publicAction() {          
         $this->_helper->layout()->disableLayout();  
         if (!in_array('download_files', unserialize($this->_auth->permission))) {
            echo 'You do not have permission to download this file!';
            die();
         } 
         $this->view->id = $this->_getParam('id');
         $this->view->token = $this->_fm->getToken($this->_getParam('id'));         
               
    }
    public function sendSalesFileAction() {
        
       // echo '<pre>';
        //var_dump($_POST);
        
        //SMTP server configuration
        $smtpHost = 'smtp.gmail.com';
        $smtpConf = array(
            'auth' => 'login',
            'ssl' => 'ssl',
            'port' => '465',
            'username' => 'jzeng8@gmail.com',
            'password' => 'google777'
        );
        $transport = new Zend_Mail_Transport_Smtp($smtpHost, $smtpConf);
        $mail = new Zend_Mail();
        $mail->setBodyHtml(nl2br($_POST['message']));
        $mail->setFrom($_POST['from']);
        
        $mail->addTo($_POST['to']);
        $mail->setSubject($_POST['subject']);
               
        
        $ns = new Zend_Session_Namespace('Default');
      //  var_dump($ns->item);
        foreach ($ns->item as $id) {            
           $file = $this->_fm->getSalesFileById($id); 
           $path = $this->_downloadsDir . DIRECTORY_SEPARATOR . $file['category_id']. DIRECTORY_SEPARATOR. $file['url']; 
       
           if (file_exists($path)) {
               try {
            $content = file_get_contents($path); // e.g. ("attachment/abc.pdf")
           
            $attachment = new Zend_Mime_Part($content);
            // $attachment->type = 'application/pdf';
            $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = Zend_Mime::ENCODING_BASE64;
            $attachment->filename = $file['title'];
            
            $mail->addAttachment($attachment);
           
               } catch (Exception $e) {
                   echo $e->getMessage();
               }
           }
        }       
       
        try {
         //   $mail->send($transport);            
            $mail->send();
            echo 'success';
        } catch (Exception $e) {             
            echo 'Unable to Send Files. '.$e->getMessage();
        }        
           
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
}

