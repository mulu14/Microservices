<?php

/**
 * Gobal namespace for all integrations
 */
namespace Integration;

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use \Exception as Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class Ftppushfile
{
    protected $settings;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $branchname = "";

    /**
     * @var string
     */
    protected $servicename = "";

    /**
     * Ftppushfile constructor.
     */
    public function __construct()
    {
    }

    /**
     * Set the file to upload
     * @param string $filename
     * @return Ftppushfile
     */

    /*
     * this function violate single-responsibility principle: 
     * 
    */
    public function setFile(string $filename, string $format)
    {
        $this -> file = $filename;
        $this -> format = $format;
        return $this;
    }

    /**
     * Set the service
     * @param string $servicename
     * @return Ftppushfile
     */
    public function setService(string $servicename)
    {
        $this -> servicename = strtolower($servicename);
        return $this;
    }

    /**
     * Set the title
     * @param string $title
     * @return Ftppushfile
     */
    public function setTitle(string $title)
    {
        $this -> title = $title;
        return $this;
    }

    /**
     * Set the branch name
     * @param string $branchname
     * @return Ftppushfile
     */
    public function setBranchname(string $branchename)
    {
        $this -> branchname = $branchename;
        return $this;
    }

    /**
     * Returns an array containing file upload services
     * @return array
     */

    /**
    * move this to service class, this is hard coded array: 
    * Read from config file, 
    * Dublicating code 
    */
    public static function getServices() : array
    {
        $integrations = [
            'bitbucket' => [
                'title' => 'BitBucket',
                'has_branches' => true,
                'enabled' => false,
            ],
            'github' => [
                'title' => 'GitHub',
                'has_branches' => true,
                'enabled' => false,
            ],
            'awss3' => [
                'title' => 'AWS S3',
                'has_branches' => false,
                'enabled' => false,
            ],
            'ftp' => [
                'title' => 'FTP',
                'has_branches' => false,
                'enabled' => false,
            ],
        ];

        foreach ($integrations as $name => &$data) {
            $classname = "Integration\\{$name}";
            $data['enabled'] = (new $classname) -> hasData();
        }

        return $integrations;
    }

    /**
     * @return array|mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */

   
    public function pushDataToFTPService()
    {
        $response = [];

        switch ($this -> servicename) {
            case "github":
                $response = $this -> putFileIntoGithub();
                break;

            case "bitbucket":
                $response = $this -> putFileIntoBitbucket();
                break;

            case "ftp":
                $response = $this -> putFileInFtpServer();
                break;

            case "awss3":
                $response = $this -> putFileInS3();
                break;
        }

        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */

    /*Create class and injection to class */
    private function putFileIntoGithub()
    {
        $integration = new Github();
        $githubSettings = $integration->getSettings();

        $sourceFileInfo = $this->getFileRealName($this -> getFile());
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

    /**
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    private function putFileIntoBitbucket()
    {
        $integration = new Bitbucket();
        $bitbucketSettings = $integration->getSettings();
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
            $response = $this -> getMessages($response -> getStatusCode(), 'BitBucket', $sourceFileInfo['realName']);
        } catch (ClientException $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function putFileInFtpServer()
    {
        $integration = new Ftp();
        $FTPSettings = $integration->getSettings();

        $server = $FTPSettings -> url ;
        $user_name = $FTPSettings -> userName;
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

    /**
     * @return string
     * @throws Exception
     */
    private function putFileInS3()
    {
        $integration = new awss3();
        $awsS3Integration = $integration -> getSettings();
        $sourceFileInfo = $this-> getFileRealName($this->getFile());
        try {
            $credentials = new Credentials("{$awsS3Integration->key}", "{$awsS3Integration->secret}");
            $s3 = new S3Client([
                'version' => 'latest',
                'region' => "{$awsS3Integration -> region}",
                'credentials' => $credentials
            ]);
            if ($result = $s3->putObject([
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

    /**
     * @param $source_file
     * @return array
     */
    private function getFileRealName($source_file)
    {
        $path = pathinfo($source_file);
        $data=array();
        $data ['realName'] = $path['basename'];
        $data ['actualPath'] = $source_file;
        return $data;
    }

    /**
     * @param $code
     * @param $service
     * @param $fileName
     * @return mixed
     */
    private function getMessages($code, $service, $fileName)
    {
        if ($code == 201 || $code == 200) {
            $data['code'] = $code;
            $data ['message'] = "Paligo has successfully pushed <strong>{$fileName}</strong> into your {$service} account.";
        } else {
            $data['code'] = $code;
            $data ['message'] = "Paligo was unable to push <strong>{$fileName}</strong> into your {$service} account. Please check your {$service} integration settings.";
        }
        return $data;
    }

    /**
     * @return mixed
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getPid(): string
    {
        return $this->pid;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return strip_tags($this->title);
    }
}
