PremekKoch\github-service
=========================
Use this service, if you want to find the last modification date of your repository file on github. Service uses a cache for performace reasons.  

Instalation
-----------

```
composer require premekkoch/github-service
```

Configuration
-------------
### 1. Add section "Github" to `parameters` section in `config.neon`:
```
  parameters:
    GitHub:
      user: GITHUB_USER
      repo: REPOSITORY_NAME
      subdir: REPOSITORY_SUBDIRECTORY
      client_id: CLIENT_ID
      client_secret: CLIENT_SECRET
```

*Item `subdir` can be empty - use it, if you're working only with one of repo directory only.*

### 2. Register a service in `config.neon`:

```
services:
	  githubService: PremekKoch\GitHub\GitHubService(%GitHub.user%, %GitHub.repo%, %GitHub.subdir%, %GitHub.client_id%, %GitHub.client_secret%)
```

How to use
----------

### 1. Simple use:
Inject an service into presenter
```
  /** @var PremekKoch\GitHub\GitHubService @inject */
  public $githubService;
```
and then call service method
```
	:
  $file = 'test.md';
  $this->githubService->getFileLastCommit($file, TRUE);
	:
```

By setting second parameter "useCache" you can decide, if you want to get cashed or "live" data. If this parameter is set to FALSE, GitHub API is called and cache is refreshed automaticaly for this file.
   
### 2. Cache refresh:
Simply call 
```
	$this->githubService->refreshGithubCache();
```
to refresh cache for all files. 
Or you can do something like this for refresh `.md` files cache only:
```
  $tree = $this->gitHubService->getFilesTree();
  foreach ($tree as $file) {
    if (strpos($file->path, '.md')) {
      $this->gitHubService->getFileLastCommit($file->path, FALSE);
  }
```
