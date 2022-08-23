<?
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

require 'vendor/autoload.php';

$authorizationToken = '1234-e4VkqAAAAAAAAAAGnT5_sDDcDRHYeGXHDRZ4Q8MiGWB1Zc4MDxcaBQQcCPB';
$client = new Client($authorizationToken);
$adapter = new DropboxAdapter($client);
$filesystem = new Filesystem($adapter, ['case_sensitive' => false]);

$LOCAL_back_folder = 'backups';
$Dropbox_back_folder = 'my-backups';

function compareByTimeStamp($time1, $time2)
{
    if (strtotime($time1) < strtotime($time2)) 
        return 1; 
    else if (strtotime($time1) > strtotime($time2)) 
        return -1;
    else
        return 0;
}
$response = $filesystem->listContents($Dropbox_back_folder);

// убираю родит. дир-ю
array_shift($response);
$dates = array_column($response, 'basename');

usort($dates, "compareByTimeStamp");
$dates = array_reverse($dates);

//////////// DELETE OLD
if(count($dates) > 7)
	$res = $filesystem->deleteDir($Dropbox_back_folder."/".$dates[0]);

$backup_date_arr = scandir( dirname(__DIR__) . "/$LOCAL_back_folder");
// избавляемся от родит. директорий
$backup_date = $backup_date_arr[2];
$backup_files = scandir(dirname(__DIR__) . "/$LOCAL_back_folder/" . $backup_date);
unset($backup_files[0]);
unset($backup_files[1]);

// создаем дир-ю
$response_dir = $filesystem->createDir("$Dropbox_back_folder/". $backup_date);

foreach ($backup_files as $key => $backup_file) {
	$file = fopen(dirname(__DIR__) ."/$LOCAL_back_folder/" . $backup_date . "/$backup_file", 'r');
	$url = "$Dropbox_back_folder/{$backup_date}/$backup_file";
	$url = str_replace ( ' ', '%20', $url);
	$filesystem->putStream($url, $file);
	fclose($file);
}