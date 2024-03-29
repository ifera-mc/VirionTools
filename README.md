<img src="https://github.com/ifera-mc/VirionTools/blob/master/meta/VirionTools.png" height="250" width="250"/>

# VirionTools

| Discord | License | Poggit | Release |
|:--:|:--:|:--:|:--:|
|[![Chat](https://img.shields.io/badge/chat-on%20discord-7289da.svg)](https://discord.gg/urQt6ETgYu)|[![GitHub license](https://img.shields.io/github/license/ifera-mc/VirionTools.svg)](https://github.com/ifera-mc/VirionTools/blob/master/LICENSE)|[![Poggit-CI](https://poggit.pmmp.io/ci.shield/ifera-mc/VirionTools/VirionTools)](https://poggit.pmmp.io/ci/ifera-mc/VirionTools/VirionTools)|[![](https://poggit.pmmp.io/shield.state/VirionTools)](https://poggit.pmmp.io/p/VirionTools)|

### A handy plugin for developers who wish to compile and inject virions without using Poggit. 

### Features

- Compile a virion to virion.phar.
- Inject a virion into another plugin.
- Works **cross-platform** i.e. it works on both Linux and Windows systems

### Setup

- Get the [.phar](https://poggit.pmmp.io/ci/ifera-mc/VirionTools/VirionTools) of this plugin from [poggit](https://poggit.pmmp.io/ci/ifera-mc/VirionTools/VirionTools)
- Put into your plugins folder.
- Restart the server.
- Enjoy..

### Compile a Virion

- To **compile** a virion folder to virion.phar, you will need to put the virion in the `virions` folder.
- The `virions` folder should be located in the folder where `PocketMine-MP.phar` exists.
- Next run the command `/bv [string:virion]`. The `[string:virion]` is the name of the virion located in the `virions` folder which you want to compile.
- The compiled (phared) virion will appear in `plugin_data\VirionTools\builds` folder.

### Inject a Virion

- To **inject** a virion to a plugin, you will need to put a compiled virion i.e. a `virion.phar` in `plugin_data\VirionTools\builds` folder.
- You will also need to put a compiled plugin i.e a `plugin.phar` in `plugin_data\VirionTools\plugins` folder.
- Next run the command `/iv [string:virion] [string:plugin]`. 
- The `[string:virion]` should be the name of the virion located in `plugin_data\VirionTools\builds` folder. 
- The `[string:plugin]` should be the name of the plugin located in `plugin_data\VirionTools\plugins` folder.
- Note: Adding the `.phar` extension doesn't matter. The plugin will add it itself.
- After successful virion injection, the injected plugin would be present in `plugin_data\VirionTools\plugins` folder.

### Inject all Virions

- To **inject all virions** into the plugin you need to use `injectall [string:plugin]` command.
- Make sure the virions required by the plugin are already compiled.
- Most importantly make a `virions` key in `plugin.yml` of the plugin.
- List all the virions required by your plugin under it.
- Next run the command `/injectall [string:plugin]` Alias for `injectall` are `ia`.
- The `[string:plugin]` should be the name of the plugin located in `plugin_data\VirionTools\plugins` folder.
- After successful virions injection, the injected plugin would be present in `plugin_data\VirionTools\plugins` folder.

### Commands and Permissions

|Description|Command|Aliases|Permission|Default|
|:--:|:--:|:--:|:--:|:--:|
|Compile a virion|`/compilevirion [string:virion]`|`cv`, `bv`, `buildvirion`|`vt.cmd.cv`|`op`|
|Inject a virion|`/injectvirion [string:virion] [string:plugin]`|`iv`|`vt.cmd.iv`|`op`|
|Inject all virions|`/injectall [string:plugin]`|`ia`|`vt.cmd.ia`|`op`|

### Disclaimer

This plugin is designed to be used only by PocketMine-MP developers who wish to compile a virion without using Poggit. Normal users should'nt be using it.

### Credits:

- [DevTools](https://poggit.pmmp.io/p/DevTools/1.13.0) by PMMP Team for providing the `ConsoleScript.php`
- [Poggit](https://poggit.pmmp.io) by Poggit Team for providing `virion.php` and `virion_stub.php`.
