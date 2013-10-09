<?php
namespace Mailcheck;



/*
 * Mailcheck https://github.com/kcassam/mailcheck
 * Author
 * K (@kaweedo)
 *
 * License
 * Copyright (c) 2013 Headoo
 *
 * Licensed under the MIT License.
 * 
 * v 1.2
 * 
 * Free api : http://headoo.com/api/mailcheck/suggest/
 */

/**
 * php port of https://github.com/Kicksend/mailcheck
 * 
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


class Mailcheck   {

	private $popularDomains = array("yahoo.com", "google.com", "hotmail.com", "gmail.com", "me.com", "mac.com",
      "live.com", "comcast.net", "googlemail.com", "msn.com", "hotmail.co.uk", "yahoo.co.uk",
      "facebook.com", "verizon.net", "mail.com", "outlook.com", "orange.fr", "free.fr", "aol.com", 
      "sfr.fr", "hotmail.fr", "live.fr", "laposte.fr", "gmx.com", "laposte.net", "neuf.fr", "edhec.com", "yahoo.fr", "wanadoo.com", "wanadoo.fr");
	private $popularTlds = array("co.uk", "com", "net", "org", "info", "edu", "gov", "mil", "fr");

	private $mistakenDomains = array("gmail.fr" => "gmail.com");
//	private $mistakenTlds = array("fr" => "com");
	private $mistakenTlds = array();
	private $defaultDomain = "gmail.com";
	private $defaultTld = "com";
	

	private $debug = 0;
	

	public function setPopularTlds($tlds) {
		$this->popularTlds = $tlds;
	}

	public function getPopularDomains() {
		return $this->popularDomains;
	}

	public function setPopularDomains($domains) {
		$this->popularDomains = $domains;
	}

	public function addPopularDomains($domains) {
		foreach ($domains as $domain) {
			$this->popularDomains[] = $domain;
		}
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


	public function suggest($address)
	{

		if ($this->debug) {
			echo PHP_EOL;
			echo "input='".$address."'".PHP_EOL;
		}

		$emailParts = $this->parseEmailAddress($address);
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
			$address = $emailParts->mailbox."@".$emailParts->label.".".$closestTld;
			if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
				// a suggest to far ? return $this->suggest($emailParts->mailbox."@".$emailParts->label.".".$closestTld);
				// test with gooooogle.con. Depends if you want give minimalist correction
				if ($this->debug) {
					echo "case #6".PHP_EOL;
				}
				return $address;
			} else {
				if ($this->debug) {
					echo "case #7".PHP_EOL;
				}
				return $this->suggest($address);
			}
		}
		
		if (isset($emailParts->tld) and (isset($this->mistakenTlds[$emailParts->tld]))) {
			if ($this->debug) {
				echo "case #4".PHP_EOL;
			}
			return $emailParts->mailbox."@".$emailParts->label.".".$this->mistakenTlds[$emailParts->tld];
		}

		
		if ($this->debug) {
			echo "case #8".PHP_EOL;
		}

		return false;
		
	}

	public function parseEmailAddress($address)
	{

		/* We do not use imap_rfc822_parse_adrlist because imap_rfc822_parse_adrlist sanitize email and we don't want sanitization now. 
		 * Sanitazation will be done in suggest function
		 */
		$exploded = explode("@", $address, 2);
		$parsed["mailbox"] = $exploded[0];
		if (isset($exploded[1])) {
			$parsed["host"] = $exploded[1];
		} else {
			$parsed["host"] = "";			
		}
		
		$exploded = explode(".", $parsed["host"], 2);		
		$parsed["label"] = $exploded[0];
		if (isset($exploded[1])) {
			$parsed["tld"] = $exploded[1];
		} else {
			$parsed["tld"] = "";			
		}
		$parsedObject =json_decode(json_encode($parsed));
		return $parsedObject;
	}

    private function findClosest($needle, $haystack)
    {
      $dist = null;
      $minDist = 99;
      $threshold = 3;
      $closest = null;


      foreach ($haystack as $canon) {
        if ($needle == $canon) {
          return $needle;
        }
        $dist = levenshtein($needle, $canon);
		if ($this->debug > 1) {		
	        var_dump(array($canon, $dist));
	    }
        
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

   /**
	* Not exactly closely related to Mailcheck, this function can find a bad email address in an delivery failure email body.
	* You can call this function with 
	* - a body or 
	* - an imap stream and a message number.
    */

	public function searchBadAddressImapAdapter($imapStream, $msgNumber)
	{
		$header = imap_fetchheader($imapStream, $msgNumber, FT_UID);
		$body = imap_body($imapStream, $msgNumber, FT_UID);
		return $this->searchBadAddress($header, $body);
	}

	public function searchBadAddress($header, $body)
	{
		$address = $this->getFailedRecipientsFromHeader($header);
		if ($address === false) {
			$address = $this->getFailedRecipientsFromBody($body);
		}
		return mb_convert_case($address, MB_CASE_LOWER, "UTF-8");
	}

    public function getFailedRecipientsFromHeader($header)
    {
    	$needle = "X-Failed-Recipients";
		$lines = preg_split('/\r\n|\r|\n/', $header);
    
		foreach ($lines as $line) {
        	$exploded = explode(":", $line, 2);
			if (stripos(trim($exploded[0]), $needle) !== false) {
				$address = preg_replace('/\s+/','', $exploded[1]);
				if ($this->debug) {
					echo "header strategy wins for '$address'".PHP_EOL;
				}
				return preg_replace('/\s+/','', $exploded[1]);
			}
		}
		return false;
	}

    public function getFailedRecipientsFromBody($body)
    {

		$body = imap_utf8($body);
		$body = quoted_printable_decode($body);
		$body = trim($body); 
		$body = preg_replace('/\s+|[^a-zA-Z0-9-_@.\+\'"]+/',' ', $body);

		$exploded = explode(' ', $body);
		foreach ($exploded as $candidat) {
			if (strpos($candidat, "@") !== false) {
				if ($this->debug) {
					echo "body strategy wins".PHP_EOL;
				}
				return $candidat;
			}
		}
		return false;
	}
}