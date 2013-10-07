<?php
namespace Mailcheck;


/*
 * Mailcheck https://github.com/Kicksend/mailcheck
 * Author
 * Derrick Ko (@derrickko)
 *
 * License
 * Copyright (c) 2012 Receivd, Inc.
 *
 * Licensed under the MIT License.
 *
 * v 1.1
 */

/*
 * Mailcheck https://github.com/kcassam/mailcheck
 * Author
 * K (@kaweedo)
 *
 * License
 * Copyright (c) 2012 Receivd, Inc.
 *
 * Licensed under the MIT License.
 *
 * v 1.1
 */


/**
* php port of https://github.com/Kicksend/mailcheck

*/

class Mailcheck   {

	private $popularDomains = array("yahoo.com", "google.com", "hotmail.com", "gmail.com", "me.com", "mac.com",
      "live.com", "comcast.net", "googlemail.com", "msn.com", "hotmail.co.uk", "yahoo.co.uk",
      "facebook.com", "verizon.net", "mail.com", "outlook.com");
	private $popularTlds = array("co.uk", "com", "net", "org", "info", "edu", "gov", "mil");

	private $mistakenDomains = array("gmail.fr" => "gmail.com");
	private $mistakenTlds = array("fr" => "com");

	private $defaultDomain = "gmail.com";
	private $defaultTld = "com";
	

	private $debug = 0;
	

	public function setPopularTlds($tlds) {
		$this->popularTlds = $tlds;
	}

	public function setPopularDomains($domains) {
		$this->popularDomains = $domains;
	}

	public function setMistakenDomains($domains) {
		$this->popularDomains = $domains;
	}


	public function setdefaultDomains($domains) {
		$this->mistakenDomains = $domains;
	}

	public function setdefaultTld($tld) {
		$this->defaultTld = $tld;
	}


	public function setDebug($debug) {
		$this->debug = $debug;
	}


	public function suggest($email) {

		if ($this->debug) {
			echo PHP_EOL;
			echo "input='".$email."'".PHP_EOL;
		}
		$email = $this->sanitize($email).PHP_EOL;

		$emailParts = $this->splitEmail($email);
		if (strlen(trim($emailParts->host)) == 0) {
			// is this too far ?? return $emailParts->mailbox."@".$this->defaultDomain;
			if ($this->debug) {
				echo "case #1".PHP_EOL;
			}
			return false;
		}
		if (isset($emailParts->host) and (isset($this->mistakenDomains[$emailParts->host]))) {
			if ($this->debug) {
				echo "case #2".PHP_EOL;
			}

			return $emailParts->mailbox."@".$this->mistakenDomains[$emailParts->host];
		}
		if (!isset($emailParts->tld)) {
			if ($this->debug) {
				echo "case #3".PHP_EOL;
			}

			// is this too far ?? return $emailParts->mailbox."@".$this->defaultDomain;
			return $emailParts->mailbox."@".$emailParts->label.".".$this->defaultTld;
		}

		if (isset($emailParts->tld) and (isset($this->mistakenTlds[$emailParts->tld]))) {
			if ($this->debug) {
				echo "case #4".PHP_EOL;
			}
			return $emailParts->mailbox."@".$emailParts->label.".".$this->mistakenTlds[$emailParts->tld];
		}

		$closestDomain = $this->findClosest($emailParts->host, $this->popularDomains);
		if ($closestDomain and $closestDomain != $emailParts->host) {
			// The email address closely matches one of the supplied domains; return a suggestion
			if ($this->debug) {
				echo "case #5".PHP_EOL;
			}
			return $emailParts->mailbox."@".$closestDomain;
		}
    	// The email address does not closely match one of the supplied domains
		$closestTld = $this->findClosest($emailParts->tld, $this->popularTlds);
		if ($closestTld and $closestTld != $emailParts->tld) {
			// The email address may have a mispelled top-level domain; return a suggestion
			$email = $emailParts->mailbox."@".$emailParts->label.".".$closestTld;
			if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
				// a suggest to far ? return $this->suggest($emailParts->mailbox."@".$emailParts->label.".".$closestTld);
				// test with gooooogle.con. Depends if you want give minimalist correction
				if ($this->debug) {
					echo "case #6".PHP_EOL;
				}
				return $email;
			} else {
				if ($this->debug) {
					echo "case #7".PHP_EOL;
				}
				return $this->suggest($email);
			}
		}
		if ($this->debug) {
			echo "case #8".PHP_EOL;
		}

		return false;
		
	}

	public function sanitize($email) {

		$email = mb_convert_case($email, MB_CASE_LOWER, "UTF-8");
	    $email =  filter_var($email, FILTER_SANITIZE_EMAIL);
		$explodedEmail = explode("@", $email, 3);

		if ($this->debug > 1) {		
			echo "exploded";
			var_dump($explodedEmail);
		}
		if (isset($explodedEmail[1])) {
			$email = $explodedEmail[0]."@".$explodedEmail[1];
		} else {
			$email = $explodedEmail[0];
		}
		$email = trim($email,"@");
		if ($this->debug > 0) {		
			echo "sanitize='".$email."'".PHP_EOL;
		}
		return $email;
	}

	public function splitEmail($email) {
		// if email == test@, Notice: Unknown: Missing or invalid host name after @ (errflg=3) in Unknown on line 0
		// if multiple @, Notice: Unknown: Unexpected characters at end of address: @om (errflg=3) in Unknown on line 0

		$parsed = imap_rfc822_parse_adrlist($email, "");
		$parsed = reset($parsed);
		$exploded = explode(".", $parsed->host, 2);
		if (isset ($exploded[1])) {
			$parsed->tld = $exploded[1];
		}
		if (isset ($exploded[0])) {
			$parsed->label = $exploded[0];
		}
		if ($this->debug > 1) {		
			echo "parsed='";
			var_dump($parsed);
		}
		return $parsed;
	}


    private function findClosest($needle, $haystack) {
      $dist = null;
      $minDist = 99;
      $threshold = 3;
      $closest = null;


      foreach ($haystack as $canon) {
        if ($needle == $canon) {
          return $needle;
        }
        $dist = $this->sift3Plus($needle, $canon);
        if ($dist < $minDist) {
          $minDist = $dist;
          $closest = $canon;
        }
      }

      if ($minDist <= $threshold and $closest !== null) {
        return $closest;
      } else {
        return false;
      }
    }

	//siderite.blogspot.com/2007/04/super-fast-and-accurate-string-distance.html
	public function sift3Plus($s1, $s2) {
	    $s1Length = strlen($s1); 
	    $s2Length = strlen($s2);
	    if (empty($s1)) {
	        return (empty($s2) ? 0 : $s2Length);
	    }
	    if (empty($s2)) {
	        return $s1Length;
	    }
	    $c1 = $c2 = $lcs = 0;
	
		$maxOffset = 5;
	
	    while (($c1 < $s1Length) && ($c2 < $s2Length)) {
	        if (($d = $s1{$c1}) == $s2{$c2}) {
	            $lcs++;
	        } else {
	            for ($i = 1; $i < $maxOffset; $i++) {
	                if (($c1 + $i < $s1Length) && (($d = $s1{$c1 + $i}) == $s2{$c2})) {
	                    $c1 += $i;
	                    break;
	                }
	                if (($c2 + $i < $s2Length) && (($d = $s1{$c1}) == $s2{$c2 + $i})) {
	                    $c2 += $i;
	                    break;
	                }
	            }
	        }
	        $c1++;
	        $c2++;
	    }
	    return (($s1Length + $s2Length) / 2 - $lcs);
	}




}

