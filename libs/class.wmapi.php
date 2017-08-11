<?php
/**
 * Class WMAPI
 * @package AntiAdblock
 * @author A Nice Guy :)
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 * Class for WMAPI
 */
class WMAPI
{
    #region PUBLIC PROPERTIES
    /**
     * @var int
     */
    public $ServerTime = 0;
    #endregion

    #region PRIVATE PROPERTIES
    /**
     * @var int
     */
    private $Id = 0;

    /**
     * @var string
     */
    private $Key = "";

    /**
     * @var bool
     */
    private $Caching = false;
    #endregion

    #region PROTECTED PROPERTIES
    /**
     * @var string
     */
    protected $CacheFileExtension = ".dat";

    /**
     * @var int
     */
    protected $CacheLifeTime = 0;

    /**
     * @var null
     */
    protected $CacheDirectory = null;

    /**
     * @var string
     */
    protected $Endpoint = "http://invoke.z-defense.com/?id=[ID]&key=[KEY]&command=[COMMAND]&url=[URL]";
    #endregion

    #region CONSTRUCTOR(S)
    /**
     * @param $intId
     * @param $strKey
     * @param bool $boolCaching
     * @param int $intCacheLifeTime
     * @throws Exception
     */
    public function __construct($intId, $strKey, $boolCaching = false, $intCacheLifeTime = 300)
    {
        if(empty($strKey))
        {
            throw new Exception("WMAPI: key can't be empty");
        }

        if(empty($intId))
        {
            throw new Exception("WMAPI: id can't be empty");
        }

        $this->ServerTime = time();
        $this->Id = $intId;
        $this->Key = $strKey;
        $this->Caching = $boolCaching;
        $this->CacheLifeTime = $intCacheLifeTime;
    }
    #endregion

    #region PUBLIC METHODS
    /**
     * @param $strDirectory
     * @throws Exception
     */
    public function SetCacheDirectory($strDirectory)
    {
        if(!is_dir($strDirectory))
        {
            throw new Exception("WMAPI: ".$strDirectory." is not a directory");
        }

        $this->CacheDirectory = $strDirectory;
    }

    /**
     * @param $strUrl
     * @return string
     * @throws Exception
     */
    public function GetAdspace($strUrl)
    {
        if($this->Caching && $this->CacheDirectory == null)
        {
            throw new Exception("WMAPI: please set valid cache directory wmapi->SetCacheDirectory");
        }

        $strCacheData = null;
        $strCacheFilename = $this->CacheDirectory."/".md5($strUrl).$this->CacheFileExtension;
        if($this->Caching && $this->CacheExists($strCacheFilename) && $this->CacheValid($strCacheFilename, $strCacheData))
        {
            return $strCacheData;
        }
        else
        {
            $strUrl = urlencode(base64_encode($strUrl));
            $strUrl = str_replace("[URL]", $strUrl, $this->ParseRequest("script"));
            $strResponse = $this->SendRequest($strUrl);

            $this->CreateCacheData($strCacheFilename, array("lifetime" => time(), "data" => $strResponse));

            return $strResponse;
        }
    }

    /**
     * @param $strUrl
     * @return string
     */
    public function GetDirect($strUrl)
    {
        $strUrl = urlencode(base64_encode($strUrl));
        $strUrl = str_replace("[URL]", $strUrl, $this->ParseRequest("direct"));

        return $this->SendRequest($strUrl);
    }
    #endregion

    #region PRIVATE METHODS
    /**
     * @param $strUrl
     * @return string
     */
    private function SendRequest($strUrl)
    {
        $strResponse = "";

        try
        {
            if(function_exists("file_get_contents"))
            {
                $strResponse = file_get_contents($strUrl);
            }
            elseif(function_exists("curl_version"))
            {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $strUrl);
                curl_setopt($ch, CURLOPT_USERAGENT, "wmapi v0.9");
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $output = curl_exec($ch);

                curl_close($ch);

                $strResponse = $output;
            }
            else
            {
                throw new Exception("WMAPI: needed file_get_contents or curl ...");
            }
        }
        catch(Exception $exception)
        {
            print $exception->getMessage();
        }

        return $strResponse;
    }
    #endregion

    #region PROTECTED METHODS
    /**
     * @param $strFilename
     * @return bool
     */
    protected function CacheExists($strFilename)
    {
        if(file_exists($strFilename))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $strFilename
     * @param $strCacheData
     * @return bool
     */
    protected function CacheValid($strFilename, &$strCacheData)
    {
        try
        {
            $strFileData = file_get_contents($strFilename);
            if(!empty($strFileData))
            {
                $arrData = unserialize($strFileData);
                if(is_array($arrData) && array_key_exists("lifetime", $arrData) && is_numeric($arrData["lifetime"]))
                {
                    $strCacheData       = $arrData["data"];
                    $intFileLifeTime    = $arrData["lifetime"];
                    if(($intFileLifeTime + $this->CacheLifeTime) > $this->ServerTime)
                    {
                        return true;
                    }

                    return false;
                }
            }
        }
        catch(Exception $exception)
        {
            return false;
        }
    }

    /**
     * @param $strFilename
     * @param $arrData
     * @return bool
     */
    protected function CreateCacheData($strFilename, $arrData)
    {
        try
        {
            file_put_contents($strFilename, serialize($arrData));
            return true;
        }
        catch(Exception $exception)
        {
            return false;
        }
    }

    /**
     * @param $strCommand
     * @return mixed
     */
    protected function ParseRequest($strCommand)
    {
        $strUrl = str_replace("[ID]", $this->Id, $this->Endpoint);
        $strUrl = str_replace("[KEY]", $this->Key, $strUrl);
        $strUrl = str_replace("[COMMAND]", $strCommand, $strUrl);

        return $strUrl;
    }
    #endregion
}
?>