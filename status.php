<!DOCTYPE html>
<html lang="en">
	<!-- Do not edit this script -->
	<!-- Built by LSmithx2 -->
	<head>
		<title>..:: Server Status ::..</title>
		<meta content="text/html" charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!--
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		-->
		<link rel="stylesheet" href="https://bootswatch.com/4/cosmo/bootstrap.min.css">
	</head>
	<html>
		<div class="credits" style="position:fixed;bottom:0;left:0;padding:10px;">
			Server Status by <a href="https://lsmithx2.uk/" target="_blank">LSmithx2</a>
		</div>
		<div class="container">
			<?php
			$data = "";
			$data .= '
<div class="card my-2">
  <h4 class="card-header text-center">
    Service status
  </h4>
  <div class="card-body pb-0">
';


			//configure script
			$timeout = "1";

			//set service checks
			/* 
The script will open a socket to the following service to test for connection.
Does not test the fucntionality, just the ability to connect
Each service can have a name, port and the Unix domain it run on (default to localhost)
*/
			$services = array();


			$services[] = array("port" => "80",       "service" => "Apache Service",                  "ip" => "google.com") ;
			$services[] = array("port" => "21",       "service" => "FTP Service",                     "ip" => "") ;
			$services[] = array("port" => "3306",     "service" => "MYSQL Service",                   "ip" => "") ;
			$services[] = array("port" => "22",       "service" => "SSH Service",				"ip" => "") ;


			//begin table for status
			$data .= "<small><table  class='table table-striped table-sm '><thead><tr><th>Service</th><th>Port</th><th>Status</th></tr></thead>";
			foreach ($services  as $service) {
				if($service['ip']==""){
					$service['ip'] = "localhost";
				}

				$fp = @fsockopen($service['ip'], $service['port'], $errno, $errstr, $timeout);
				if (!$fp) {
					$data .= "<tr ><td>" . $service['service'] . "</td><td>". $service['port']."</td><td class='table-danger'>Offline </td></tr>";
					//fclose($fp);
				} else {
					$data .= "<tr><td>" . $service['service'] . "</td><td>". $service['port']."</td><td class='table-success'>Online</td></tr>";
					fclose($fp);
				}

			}  
			//close table
			$data .= "</table></small>";
			$data .= '
  </div>
</div>
';
			echo $data;


			/* =====================================================================
//
// ////////////////// SERVER INFORMATION  /////////////////////////////////
//
//
* =======================================================================/*/

			$data1 = "";
			$data1 .= '
<div class="card mb-2">
  <h4 class="card-header text-center">
    Server information
  </h4>
  <div class="card-body">
';


			$data1 .= "<table  class='table table-sm mb-0'>";

			//GET SERVER LOADS
			$loadresult = @exec('uptime');  
			preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);


			//GET SERVER UPTIME
			$uptime = explode(' up ', $loadresult);
			$uptime = explode(',', $uptime[1]);
			$uptime = $uptime[0].', '.$uptime[1];

			//Get the disk space
			function getSymbolByQuantity($bytes) {
				$symbol = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
				$exp = floor(log($bytes)/log(1024));

				return sprintf('%.2f<small>'.$symbol[$exp].'</small>', ($bytes/pow(1024, floor($exp))));
			}
			function percent_to_color($p){
				if($p < 30) return 'success';
				if($p < 45) return 'info';
				if($p < 60) return 'primary';
				if($p < 75) return 'warning';
				return 'danger';
			}
			function format_storage_info($disk_space, $disk_free, $disk_name){
				$str = "";
				$disk_free_precent = 100 - round($disk_free*1.0 / $disk_space*100, 2);
				$str .= '<div class="col p-0 d-inline-flex">';
				$str .= "<span class='mr-2'>" . badge($disk_name,'secondary') .' '. getSymbolByQuantity($disk_free) . '/'. getSymbolByQuantity($disk_space) ."</span>";
				$str .= '
<div class="progress flex-grow-1 align-self-center">
  <div class="progress-bar progress-bar-striped progress-bar-animated ';
				$str .= 'bg-' . percent_to_color($disk_free_precent) .'
  " role="progressbar" style="width: '.$disk_free_precent.'%;" aria-valuenow="'.$disk_free_precent.'" aria-valuemin="0" aria-valuemax="100">'.$disk_free_precent.'%</div>
</div>
</div>		';

				return $str;

			}

			function get_disk_free_status($disks){
				$str="";
				$max = 5;
				foreach($disks as $disk){
					if(strlen($disk["name"]) > $max) 
						$max = strlen($disk["name"]);
				}

				foreach($disks as $disk){
					$disk_space = disk_total_space($disk["path"]);
					$disk_free = disk_free_space($disk["path"]);

					$str .= format_storage_info($disk_space, $disk_free, $disk['name']);

				}
				return $str;
			}
			function badge($str, $type){
				return "<span class='badge badge-" . $type . " ' >$str</span>";
			}

			//Get ram usage
			$total_mem = preg_split('/ +/', @exec('grep MemTotal /proc/meminfo'));
			$total_mem = $total_mem[1];
			$free_mem = preg_split('/ +/', @exec('grep MemFree /proc/meminfo'));
			$cache_mem = preg_split('/ +/', @exec('grep ^Cached /proc/meminfo'));

			$free_mem = $free_mem[1] + $cache_mem[1];


			//Get top mem usage
			$tom_mem_arr = array();
			$top_cpu_use = array();

			//-- The number of processes to display in Top RAM user
			$i = 5;


			/* ps command:
-e to display process from all user
-k to specify sorting order: - is desc order follow by column name
-o to specify output format, it's a list of column name. = suppress the display of column name
head to get only the first few lines 
*/
			exec("ps -e k-rss -ocomm=,rss= | head -n $i", $tom_mem_arr, $status);
			exec("ps -e k-pcpu -ocomm=,pcpu= | head -n $i", $top_cpu_use, $status);


			$top_mem = implode(' KiB <br/>', $tom_mem_arr );
			$top_mem = "<pre class='mb-0'><b>COMMAND\t\tResident memory</b><br/>" . $top_mem . " KiB</pre>";

			$top_cpu = implode(' % <br/>', $top_cpu_use );
			$top_cpu = "<pre class='mb-0'><b>COMMAND\t\tCPU utilization </b><br/>" . $top_cpu. " %</pre>";

			$data1 .= "<tr><td>Average load</td><td><h5>". badge($avgs[1],'secondary'). ' ' .badge($avgs[2], 'secondary') . ' ' . badge( $avgs[3], 'secondary') . " </h5></td>\n";
			$data1 .= "<tr><td>Uptime</td><td>$uptime                     </td></tr>";


			$disks = array();

			/*
* The disks array list all mountpoint you wan to check freespace
* Display name and path to the moutpoint have to be provide, you can 
*/
			$disks[] = array("name" => "local" , "path" => getcwd()) ;
			// $disks[] = array("name" => "Your disk name" , "path" => '/mount/point/to/that/disk') ;


			$data1 .= "<tr><td>Disk free        </td><td>" . get_disk_free_status($disks) . "</td></tr>";

			$data1 .= "<tr><td>RAM free        </td><td>". format_storage_info($total_mem *1024, $free_mem *1024, '') ."</td></tr>";
			$data1 .= "<tr><td>Top RAM user    </td><td><small>$top_mem</small></td></tr>";
			$data1 .= "<tr><td>Top CPU user    </td><td><small>$top_cpu</small></td></tr>";

			$data1 .= "</table>";
			$data1 .= '
  </div>
</div>
';
			echo $data1;  

			/* =============================================================================
*
* DISPLAY BANDWIDTH STATISTIC, REQUIRE VNSTAT INSTALLED AND PROPERLY CONFIGURED.
*
* ===============================================================================s
*/


			if (!isset($_GET['showtraffic']) || $_GET['showtraffic'] ==  false) die();

			$data2 = "";
			$data2 .=  '
<div class="card mb-2">
  <h4 class="card-header text-center">
    vnstat Network traffic
  </h4>
  <div class="card-body text-center">
';


			$data2 .="<span class=' d-block'><pre class='d-inline-block text-left'><small>";
			$traffic_arr = array();
			exec('vnstat -' . $_GET['showtraffic'], $traffic_arr, $status);

			///for testing
			$traffic = "
			enp0s20  /  monthly

			month        rx      |     tx      |    total    |   avg. rate
			------------------------+-------------+-------------+---------------
			Sep '18     36.60 GiB |    7.04 GiB |   43.64 GiB |  144.62 kbit/s
			Oct '18    400.69 GiB |    1.19 TiB |    1.58 TiB |    5.19 Mbit/s
			Nov '18    393.52 GiB |    2.19 TiB |    2.57 TiB |    8.72 Mbit/s
			Dec '18    507.28 GiB |    2.05 TiB |    2.55 TiB |    8.37 Mbit/s
			Jan '19    269.01 GiB |    1.39 TiB |    1.65 TiB |    7.51 Mbit/s
			------------------------+-------------+-------------+---------------
			estimated    371.92 GiB |    1.92 TiB |    2.29 TiB |
			";
			/// for real
			$traffic = implode("\n", $traffic_arr);

			$data2 .="$traffic</small></pre></span>";

			echo $data2;
			?>
		</div>
	</html>
