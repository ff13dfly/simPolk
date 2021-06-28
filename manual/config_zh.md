# 更多的模拟链配置
完整的配置数据文件为 <https://github.com/ff13dfly/simPolk/blob/main/api/config.php>

## 模拟链运行配置
* redis配置

## 模拟链基础性能配置
* 模拟链代币名称
* 出块速度
* 经济配置


## 模拟链键值配置
* 模拟链建立时间点
* 已收集交易列表入口
* 已收集存储列表入口
* 已收集智能合约列表入口
* 已写数据的区块高度
* 账户map的入口，用于记录账户的交易记录hash的
* 账户列表的入口，所有账户的列表，使用redis的list实现
## 模拟服务器的配置

* 服务器列表配置
位于config的nodes键值下，数据结构为：

```javascript
{
    'url'      :    'http://localhost/simPolk/network/s_01.php',
    'account'  :    '5r9E41L7tb8PstabiPKsJm56q8XeMqoA43Zmxbn9NwTmvKh75468McNpv2ZYTH8i',
    'sign'     :    '',
}
```
* 服务器白名单