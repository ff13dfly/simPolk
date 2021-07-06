# simPolk , 10 minutes to join Polkadot development

## 初衷
## Make Dapp Easy
即使像波卡这样把区块链开发弄得很简单和模块化了，但是使用门槛还是很高。因为需要熟悉很多全新的概念、搭建一个可用的区块链网络是一项巨大的工程，更不用说维护这个网络到新版本，在去开发更多的功能。

Even if the blockchain development is simple and modular like Polkadot, the barrier to use is still very high. Because you need to be familiar with many new concepts and building a usable blockchain network is a huge project, not to mention maintaining the network to a new version and developing more functions.

自己作为一个想应用区块链技术的开发者，经历了这个耗时耗力的痛苦过程，迎接了巨大的挑战，从学习Rust到调试substrate，从增加Pallet到升级substrate版本，真的是花费了大量的时间和精力，然而，问题并没有得到很好的解决，请看[虚块世界](https://github.com/ff13dfly/VirtualBlockWorld)，如此，没法专注在应用程序的开发。

As a developer who wants to apply blockchain technology, I have experienced this painful process of time-consuming and labor-intensive and met great challenges. From learning Rust to debugging substrate, from adding Pallet to upgrading the version of substrate, it is really costly. A lot of time and energy, however, the problem has not been solved well, please see [Virtual Block World](https://github.com/ff13dfly/VirtualBlockWorld), so it is impossible to focus on application development.

***
一直在思考，怎么解决这个问题呢？
How to solve this problem?

某天，豁然开朗，就诞生了这个项目，simPolk(波卡模拟器)，抛弃掉建立私链所需要的多种技能，只需要简单的拷贝代码，配置系统，就可以模拟大部分的私链行为的单机模拟器。把应用开发者从区块链的底层解救出来，专注于应用程序的开发。

One day, it suddenly became clear that this project was born, simPolk (Polka Simulator), abandoning the various skills needed to establish a private chain, just simply copy the code and configure the system to simulate most of the private chain. A stand-alone simulator of behavior. Rescue application developers from the bottom of the blockchain and focus on application development.

自己深深的相信，随着区块链技术的成熟，应用开发者无需再去理解复杂的区块链技术是如何实现的，而是更关注应用本身是否能够解决用户的需求，就像关联数据库技术一样，如今，只需要会使用SQL即可，无需去理解各种数据库是如何实现的。

I deeply believe that with the maturity of blockchain technology, application developers no longer need to understand how complex blockchain technology is implemented, but pay more attention to whether the application itself can solve the needs of users, just like a relational database The technology is the same. Now, you only need to be able to use SQL, without understanding how various databases are implemented.

## simPolk功能简介
## simPolk Introduction 
simPolk是一套完整的区块链模拟器，可以在单台主机上模拟区块链的行为，模拟的部分包括区块的生成、区块的结构、交易的结构、链上存储的结构、智能合约的结构。这些结构以JSON的方式进行解析，方便阅读和使用。这些都来自于simPolk的设计理念，快速的阅读，快速的理解，快速的使用，在10分钟之内，就可以初步理解区块链涉及到的重要技术点，着手构建你自己的应用程序。

simPolk is a complete block chain simulator that can simulate the behavior of the block chain on a single host. The simulation part includes block generation, block structure, transaction structure, chain storage structure, and intelligence The structure of the contract. These structures are parsed in JSON, which is easy to read and use. These all come from the design concept of simPolk, fast reading, fast understanding, and fast use. Within 10 minutes, you can initially understand the important technical points involved in the blockchain and start to build your own application.


基于简单使用的考虑，simPolk使用php语言开发，使用redis作为数据存储引擎，调用部分使用jsonp方式进行跨域访问。

Based on the consideration of simple use, simPolk is developed in php language, redis is used as the data storage engine, and the calling part uses jsonp for cross-domain access.

simPolk用模拟的方式返回和Polkadot一致的数据结构，这样，对于理解UXTO、区块结构、Merkle树等核心概念，提供了直观的数据结果。您也可以通过JSONP的方式，直接调用simPlok的API部分，尝试快速的进入Polkadot的开发，边用边学，渐入佳境。

simPolk returns the data structure consistent with Polkadot in a simulated way. In this way, it provides intuitive data results for understanding core concepts such as UXTO, block structure, and Merkle tree. You can also directly call the API part of simPlok through JSONP, try to quickly enter the development of Polkadot, learn while using, and get better.

simPolk用单一的php文件来模拟服务器，您可以快速的搭建一个虚拟网络，来观察挖矿过程，直观的体验到每一笔coinbase的生成。

## 快速部署simPolk（约10分钟）
## Rapid deployment of simPolk (about 10 minutes)

1. 使用以下命令拷贝simPolk到支持php运行的目录。
Use the following command to copy simPolk to a directory that supports PHP.

```shell
git clone https://github.com/ff13dfly/simPolk
```

如您不熟悉php开发，请看这里[XAMPP,最受欢迎的PHP开发环境](https://www.apachefriends.org/index.html)
If you are not familiar with PHP development, please see here [XAMPP, the most popular PHP development environment](https://www.apachefriends.org/index.html)

2. 配置redis运行环境。
Configure the Redis.

如您未对redis进行配置的话，默认参数即可以正常的运行。
或者，您可以编辑配置文件，输入正确的redis运行参数。
If you have not configured redis, the default parameters can run normally.
Or, you can edit the configuration file and enter the correct redis operating parameters.

3. 现在，您就可以在浏览器里查看模拟链的运行情况啦
Now, you can view the operation of the simchain in the browser.

请在浏览器里输入以下网址：
Please enter the following URL in your browser:

```text
http://localhost/simPolk/ui
```

4. 尽情使用UI提供的功能，尝试simPlok的模拟链吧。
Enjoy the functions provided by the UI and try the simchain of simPlok.

不用担心，只需点击“Reset Network”，全新的模拟链就建成了。
Don't worry, just click "Reset Network" and a brand new analog chain is built.
## 更多功能

* [simPolk功能](manual/ui_zh.md)

* [模拟链配置](manual/config_zh.md)

* [模拟链节点配置](manual/node_zh.md)

* [模拟链开发](manual/api_zh.md)
