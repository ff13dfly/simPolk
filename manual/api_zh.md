# simPolk的API说明

## 结构设计

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