<?php

namespace service\Integration; 

class Ftppushfile
{
    protected $putFileIntoGithub;

    /**
     * @var string
     */
    protected $putFileIntoBitbucket;

    /**
     * @var string
     */
    protected $putFileInFtpServer;

    /**
     * @var string
     */
    protected $putFileInS3;

    /**
     * Ftppushfile constructor.
     */
    public function __construct(PutFileIntoGithub $putFileIntoGithub, PutFileIntoBitbucket $putFileIntoBitbucket, PutFileInFtpServer $putFileInFtpServer, PutFileInS3 $putFileInS3;)
    {

    	$this->putFileIntoGithub = $putFileIntoGithub; 
    	$this->putFileIntoBitbucket = $putFileIntoBitbucket; 
    	$this->pushDataToFTPService = $pushDataToFTPService; 
    	$this->putFileInS3 = $PutFileInS3; 
    }

     public function pushDataToFTPService()
    {
        $response = [];

        switch ($this ->servicename) {
            case "github":
                $response = $this->putFileIntoGithub->putFileIntoGithub();
                break;

            case "bitbucket":
                $response = $this->putFileIntoBitbucket->putFileIntoBitbucket();
                break;

            case "ftp":
                $response = $this ->putFileInFtpServer->putFileInFtpServer();
                break;

            case "awss3":
                $response = $this ->putFileInS3->putFileInS3();
                break;
        }

        return $response;
    }

}


/**
 *  
 */


class PutFileIntoGithub
{
	protected $github; 
	protected $user; 


	function __construct(Github $github)
	{
		$this->github = $github; 
	}

	public function putFileIntoGithub()
    {
        $githubSettings = $this->github->getSettings();

        $sourceFileInfo = $this->getFileRealName($this->getFile());
        $sourceFile = fisle_get_contents($this -> file);
        $sourceFile = base64_encode($sourceFile);

        $url = sprintf(
            "https://api.github.com/repos/%s/%s/contents/%s/%s",
            $githubSettings->userName,
            $githubSettings->repoName,
            $githubSettings->folder,
            $sourceFileInfo['realName']
        );
        $base64 = base64_encode($githubSettings->userName.':'.$githubSettings->password);
        $user = \User::init()->getData();

        $commitMessage = $user->real_name. ' has pushed '. $this->getTitle(). ' '. $this->getFormat(). ' '. 'from paligo';
        try {
            $response = \Httpful\Request::put($url)
                ->addHeader('Authorization', "Basic $base64")
                ->addHeaders(
                    array(
                        'Accept-Encoding' => 'gzip, deflate',
                        'Host'  => 'api.github.com',
                        'Cache-Control' => 'no-cache',
                        'Accept' => '*/*',
                    )
                )
                ->body('{
                                "message": "'.$commitMessage.'",
                                "content": "'.$sourceFile.'",
                                "branch":"'.$this->branchname.'"
                                }')
                -> send();
            $response = $this->getMessages($response ->code, 'GitHub', $sourceFileInfo['realName']);
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}

/**
 * 
 */
class PutFileIntoBitbucket 
{
	
	protected $integration; 

	function __construct(BitBucket $integration)
	{
		$this->integration = $integration; 
	}

	private function putFileIntoBitbucket()
    {
   
        $bitbucketSettings = $this->integration->getSettings();
        $sourceFileInfo = $this->getFileRealName($this->getFile());

        $url = sprintf(
            "https://api.bitbucket.org/2.0/repositories/%s/%s/src",
            $bitbucketSettings->userName,
            $bitbucketSettings->repoName
        );
        $user = \User::init()->getData();
        $commitMessage = $user->real_name. ' has pushed '. $this->getTitle(). ' '. $this->getFormat(). ' '. 'from paligo';
        $path = "/{$bitbucketSettings->folder}/{$sourceFileInfo['realName']}";

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                'POST',
                $url,
                [
                    'auth' => [
                        $bitbucketSettings->userName,
                        $bitbucketSettings->password
                    ],
                    'multipart' => [
                        array(
                            'name' => 'message',
                            'contents' => $commitMessage
                        ),
                        array(
                            'name' => 'branch',
                            'contents' => $this->branchname
                        ),
                        array(
                            'name' =>  $path,
                            'contents' => file_get_contents($sourceFileInfo['actualPath'], $sourceFileInfo['realName']),
                            'filename' => $sourceFileInfo['realName']

                        )
                    ]
                ]
            );
            $response = $this->getMessages($response -> getStatusCode(), 'BitBucket', $sourceFileInfo['realName']);
        } catch (ClientException $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}

/**
 * 
 */
class PutFileInFtpServer
{
	
	protected $integration; 

	function __construct(Ftp $integration)
	{
		$this->integration = $integration; 
	}

	public function putFileInFtpServer()
    {

        $FTPSettings = $this->integration->getSettings();
        $server = $FTPSettings -> url ;
        $user_name = $FTPSettings->userName;
        $password = $FTPSettings -> password;
        $sourceFileInfo = $this->getFileRealName($this->getFile()); // all class need this function 
        $dest = '/'. $sourceFileInfo['realName'];
        $source_file = "{$sourceFileInfo['actualPath']}";

        $connection = ftp_connect($server, 21) or die("Couldn't connect to $server");

        // login with username and password
        ftp_login($connection, $user_name, $password) or die("Cannot login");
        ftp_pasv($connection, true);

        // upload a file
        if (ftp_put($connection, $dest, $source_file, FTP_BINARY) or die("Cannot upload")) {
            $response['code'] = 201;
            $response['message'] = "Paligo has successfully pushed <strong>{$sourceFileInfo['realName']}</strong> into your FTP server.";
        } else {
            $response['code'] = 400;
            $response = "Paligo was unable to push the {$sourceFileInfo['realName']} in your FTP server.";
        }

        // close the connection
        ftp_close($connection);
        return $response;
    }
}


/**
 * 
 */
class PutFileInS3
{

	protected $integration; 
	protected $s3;

	function __construct(awss3 $integration, S3Client $s3)
	{
		$this->integration = $integration; 
		$this->s3 = $s3; 
	}



	public function putFileInS3()
    {

        $awsS3Integration = $this->integration->getSettings();
        $sourceFileInfo = $this->getFileRealName($this->getFile());
        try {

            if ($result = $this->s3->putObject([
                'Bucket' => "{$awsS3Integration -> bucketName}",
                'Key' => "{$awsS3Integration -> path}/{$sourceFileInfo['realName']}",
                'SourceFile' => "{$sourceFileInfo['actualPath']}",
            ])) {
                $response['code'] = 201;
                $response['message'] = "Paligo has successfully pushed <strong>{$sourceFileInfo['realName']}</strong> into S3 Service";
            } else {
                $response['code'] = 400;
                $response['message'] = "Paligo was unable to push <strong>{$sourceFileInfo['realName']}</strong> into your S3 Bucket. Please check your S3 integration settings.";
            }
            return $response;
        } catch (S3Exception $e) {
            return $e->getMessage();
            return $response['message']= $e->getMessage();
        }
    }
}


/**
 * 
 */
class S3Client
{
	$protected $credentials; 


	
	function __construct()
	{
	}

	public function credentials($credentials){
		$this->credentials = $credentials; 
	}
}









