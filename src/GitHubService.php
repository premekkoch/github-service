<?php

/**
 * This file is part of extension of Nette Framework
 *
 * @license    MIT
 * @author     Premek Koch
 */

namespace PremekKoch;

use Exception;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;


if (!function_exists('curl_init')) {
  throw new gitHubException('Github lib needs the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
  throw new gitHubException('Github lib needs the JSON PHP extension.');
}


class GitHubService extends Object
{
  const GITHUB_URL = 'https://api.github.com/repos';

  private $user;

  private $repo;

  private $subdir;

  private $clientId;

  private $clientSecret;

  private $userAgent;

  /** @var Cache */
  private $cache;


  /**
   * Constructor
   *
   * @param          $user
   * @param          $repo
   * @param          $subdir
   * @param          $clientId
   * @param          $clientSecret
   * @param IStorage $storage
   */
  public function __construct($user, $repo, $subdir, $clientId, $clientSecret, IStorage $storage)
  {
    $this->user = $user;
    $this->repo = $repo;
    $this->subdir = $subdir;
    $this->userAgent = $user;
    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
    $this->cache = new Cache($storage, 'github');
  }


  /**
   * Returns file last commit infos
   *
   * @param string $fileName
   * @param bool   $useCache
   * @return mixed
   * @throws gitHubException
   */
  public function getFileLastCommit($fileName, $useCache = TRUE)
  {
    if (!$useCache) {
      $this->cache->remove($fileName);
    }

    return $this->cache->load($fileName,
      function ($fileName) {
        $path = $this->subdir ? $this->subdir . '/' : '';
        $url = self::GITHUB_URL . '/' . $this->user . '/' . $this->repo . '/commits?path=' . $path . $fileName;
        $tree = $this->run($url);

	      return count($tree) === 0 ? NULL : $tree[0];
      });
  }


  /**
   * Returns github tree
   *
   * @return mixed
   * @throws gitHubException
   */
  public function getFilesTree()
  {
    $master = $this->run(self::GITHUB_URL . '/' . $this->user . '/' . $this->repo . '/git/refs/heads/master');
    $commitUrl = $master->object->url;
    $commit = $this->run($commitUrl);
    $treeUrl = $commit->tree->url;
    $tree = $this->run($treeUrl);

    if ($this->subdir) {
      foreach ($tree->tree as $node) {
        if ($node->type === 'tree' && $node->path === $this->subdir) {
          $treeUrl = $node->url;
          $tree = $this->run($treeUrl);
          break;
        }
      }
    }

    return $tree->tree;
  }


  /**
   * Refresh cache for all github files
   */
  public function refreshGithubCache()
  {
    $tree = $this->getFilesTree();
    foreach ($tree as $file) {
      $this->gitHubService->getFileLastCommit($file->path, FALSE);
    }
  }


  /**
   * Run request
   *
   * @param $url
   * @return mixed
   * @throws gitHubException
   */
  private function run($url)
  {
    $c = curl_init();

    if ($c === FALSE) {
      throw new gitHubException('cURL failed to initialize.');
    }

    if (strpos($url, '?') != 0) {
      $url = $url . '&';
    } else {
      $url = $url . '?';
    }
    $url = $url . 'client_id=' . $this->clientId . '&client_secret=' . $this->clientSecret;

    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($c, CURLOPT_FAILONERROR, FALSE); // to get error messages in response body
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($c, CURLOPT_USERAGENT, $this->userAgent);

    $response = curl_exec($c);
    $info = curl_getinfo($c);

    if ($response === FALSE) {
      throw new gitHubException(sprintf("cURL failed with error #%d: %s", curl_errno($c), curl_error($c)), curl_errno($c));
    }

    curl_close($c);

    if ($info['http_code'] >= 400) {
      throw new gitHubException($response, $info['http_code']);
    }

    return json_decode($response);
  }
}


class gitHubException extends Exception
{
}


