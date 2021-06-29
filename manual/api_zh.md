# simPolk的API说明
simPolk本着简单易懂的原则，其自身也遵循这个原则，通过简单易懂的方式来构建。只使用2个类文件来实现基础的区块链功能，简单的路由方式，将扩展功能轻松的组织起来。
通过模拟的方式，实现了区块链的基础功能，包括，交易、链上存储、智能合约。

* 交易的实现
simPolk不对账号进行单独密码管理，创建后即可使用，使用配置里的统一密码，模拟验证成功和失败。
交易部分采用栈（使用redis的list进行模拟）的方式进行管理，每笔转账，hash都会压入到用户独立的栈里，每笔交易的原始数据，会以hash为键值（使用redis的hash进行模拟）进行存储，使用后进行删除。

* 链上存储的实现
simPolk对链上存储采用kv方式进行（使用redis的hash进行模拟），使用json格式，字符串化后进行保存。

* 智能合约的实现
simPolk的智能合约，采用javascript实现，字符串化后保存在模拟链上。浏览器获取到智能合约之后，通过eval进行解析，传入对应的参数，生成标准数据格式，返回给服务器端的contact下的exec方法进行数据处理（包括转账和链上数据两部分），完整的模拟智能合约的运行。
## 结构设计
* 单一入口设计
API部分以entry.php为唯一入口，方便进行管理和输出。

* 单一配置文件
simPolk的配置集中到config.php文件，位于API的根目录下，方便调整和使用。
## 调用方式
通过调用URI参数里的mod和act进行路由，mod对应的是sim目录下对应的类，每个类下都有一个task方法，通过参数act进行内部路由。
如图所示：

### lib目录
* core.class.php

* simulator.class.php

### sim目录
* account.class.php

* block.class.php

* chain.class.php

* node.class.php

* storage.class.php