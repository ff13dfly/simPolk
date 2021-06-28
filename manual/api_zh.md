# simPolk的API说明
simPolk本着简单易懂的原则，其自身也遵循这个原则，通过简单易懂的方式来构建。只使用2个类文件来实现基础的区块链功能，简单的路由方式，将扩展功能轻松的组织起来。
## 结构设计
* 单一入口设计

* 单一配置文件
## 调用方式
通过调用URI参数里的mod和act进行路由，mod对应的是sim目录下对应的类，每个类下都有一个task方法，通过参数act进行内部路由。
## 功能说明
* 自动补块
* 梅克尔树
### lib目录
* core.class.php

* simulator.class.php

### sim目录
* account.class.php

* block.class.php

* chain.class.php

* node.class.php

* storage.class.php