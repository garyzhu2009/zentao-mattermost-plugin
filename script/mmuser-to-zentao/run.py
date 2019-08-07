#!/usr/bin/env python3
# -*- coding:utf-8 -*-
#
import pymysql
import logging

logger = logging.getLogger(__name__)
logger.setLevel(level = logging.INFO)
handler = logging.FileHandler("log.txt")
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
handler.setFormatter(formatter)
logger.addHandler(handler)

logger.info("===begin===")

# 原始数据的数据连接
db1 = pymysql.connect('10.32.146.70', 'mmuser', 'mmuser_zj', 'mattermost')
cursor1 = db1.cursor()
# 定义查询语句
len1 = cursor1.execute('select t.Id,t.Username,t.Email,t.Nickname,t.FirstName,t.LastName from Users t where t.DeleteAt=0')
data2 = cursor1.fetchall()


# 迁移库1的数据连接
logger.info("###连第1个库 BEGIN###")
db2 = pymysql.connect('172.32.149.214', 'xxxx', 'xxxx', 'zentao')
cursor2 = db2.cursor()
cursor2.execute('truncate table mm_users')
logger.info("###truncate111###")
# 批量插入语句
sql = 'insert into mm_users(Id,Username,Email,Nickname,FirstName,LastName,DeleteAt) value(%s, %s, %s,%s, %s, %s,0)'
cursor2.executemany(sql, data2)
db2.commit()
logger.info("###连第1个库 END###")


# 迁移库2的数据连接
logger.info("###连第2个库 BEGIN###")
db3 = pymysql.connect('172.32.148.78', 'xxxx', 'xxxx', 'zentao')
cursor3 = db3.cursor()
cursor3.execute('truncate table mm_users')
logger.info("###truncate222###")
# 批量插入语句
sql3 = 'insert into mm_users(Id,Username,Email,Nickname,FirstName,LastName,DeleteAt) value(%s, %s, %s,%s, %s, %s,0)'
cursor3.executemany(sql3, data2)
db3.commit()
logger.info("###连第2个库 END###")

# 关闭数据库连接
db1.close()
db2.close()
db3.close()

logger.info("===end===")
