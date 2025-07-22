/*
 Navicat Premium Data Transfer

 Source Server         : 127.0.0.1
 Source Server Type    : MySQL
 Source Server Version : 80012
 Source Host           : localhost:3306
 Source Schema         : madong_db

 Target Server Type    : MySQL
 Target Server Version : 80012
 File Encoding         : 65001

 Date: 07/07/2025 16:41:49
*/

SET NAMES utf8mb4;
SET
FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ma_cache
-- ----------------------------
DROP TABLE IF EXISTS `ma_cache`;
CREATE TABLE `ma_cache`
(
    `key`         varchar(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
    `result`      text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
    `expire_time` int(11) NULL DEFAULT 0,
    `create_time` int(11) NULL DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_db_setting
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_db_setting`;
CREATE TABLE `ma_mt_db_setting`
(
    `id`          bigint(20) NOT NULL COMMENT 'id',
    `name`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '链接名',
    `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '描述',
    `driver`      varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '驱动类型',
    `host`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'ip',
    `port`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '端口',
    `database`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '数据库名称',
    `username`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户名',
    `password`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户密码',
    `prefix`      varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'ma_' COMMENT '前缀',
    `variable`    text NULL COMMENT '变量', -- 使用 TEXT 替代 JSON
    `is_default`  tinyint(1) NULL DEFAULT 0 COMMENT '是否默认数据源',
    `enabled`     tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '状态',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `created_by`  bigint(20) NULL DEFAULT NULL COMMENT '创建人',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '更新人',
    `updated_by`  bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `deleted_at`  bigint(20) NULL DEFAULT NULL COMMENT '删除',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '数据中心配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_tenant
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_tenant`;
CREATE TABLE `ma_mt_tenant`
(
    `id`             bigint(20) NOT NULL COMMENT 'id',
    `db_name`        varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '数据源名称',
    `code`           varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '租户编号',
    `type`           tinyint(1) NULL DEFAULT NULL COMMENT '0其他  1 企业',
    `contact_person` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '联系人',
    `contact_phone`  varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '联系电话',
    `company_name`   varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '企业名称',
    `license_number` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '统一社会信用代码',
    `address`        varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '地址',
    `description`    varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '企业简介',
    `domain`         varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '域名',
    `enabled`        tinyint(1) NULL DEFAULT 1 COMMENT '状态（1正常 0停用）',
    `isolation_mode` tinyint(4) NULL DEFAULT 2 COMMENT '隔离模式（1字段隔离  2库隔离）',
    `is_default`     tinyint(1) NULL DEFAULT 0 COMMENT '是否默认',
    `expired_at`     bigint(20) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT '过期时间',
    `deleted_at`     bigint(20) NULL DEFAULT NULL COMMENT '删除标志',
    `created_at`     bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `created_by`     bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`     bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `updated_at`     bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '租户' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_tenant_package
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_tenant_package`;
CREATE TABLE `ma_mt_tenant_package`
(
    `tenant_id`       bigint(20) UNSIGNED NOT NULL COMMENT '租户ID',
    `subscription_id` bigint(20) UNSIGNED NOT NULL COMMENT '套餐ID'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '租户-订阅关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_tenant_session
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_tenant_session`;
CREATE TABLE `ma_mt_tenant_session`
(
    `id`         bigint(20) NOT NULL COMMENT '雪花ID',
    `key`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'key',
    `admin_id`   bigint(20) NOT NULL COMMENT '管理员ID',
    `tenant_id`  bigint(20) NOT NULL COMMENT '租户ID',
    `token`      varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '会话token',
    `expire_at`  bigint(20) NULL DEFAULT NULL COMMENT '过期时间戳',
    `created_at` bigint(20) NULL DEFAULT NULL COMMENT '创建时间戳',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_token`(`token`) USING BTREE,
    INDEX        `idx_admin_tenant`(`admin_id`, `tenant_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '租户-会话表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_tenant_subscription
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_tenant_subscription`;
CREATE TABLE `ma_mt_tenant_subscription`
(
    `id`          bigint(20) UNSIGNED NOT NULL COMMENT '雪花ID',
    `name`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '套餐ID',
    `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '描述',
    `sort`        int(11) NULL DEFAULT 0 COMMENT '排序',
    `start_time`  bigint(20) NULL DEFAULT NULL COMMENT '开始时间戳',
    `end_time`    bigint(20) NULL DEFAULT NULL COMMENT '结束时间戳',
    `enabled`     tinyint(4) NULL DEFAULT 1 COMMENT '状态:0-已过期,1-有效',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间戳',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '更新时间戳',
    `remark`      longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '租户-套餐订阅表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_mt_tenant_subscription_casbin
-- ----------------------------
DROP TABLE IF EXISTS `ma_mt_tenant_subscription_casbin`;
CREATE TABLE `ma_mt_tenant_subscription_casbin`
(
    `subscription_id`        bigint(20) UNSIGNED NOT NULL COMMENT '套餐主键',
    `subscription_casbin_id` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'casb 策略关联ID'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '租户-套餐订阅策略关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin`;
CREATE TABLE `ma_sys_admin`
(
    `id`              bigint(20) UNSIGNED NOT NULL COMMENT '用户ID,主键',
    `user_name`       varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci  NOT NULL COMMENT '账号',
    `real_name`       varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '用户',
    `nick_name`       varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '昵称',
    `password`        varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '密码',
    `is_super`        tinyint(4) NULL DEFAULT 0 COMMENT '用户类型:(1系统用户 0普通用户)',
    `is_tenant_admin` tinyint(4) NULL DEFAULT 0 COMMENT '是否为租户管理员（1是  0否）',
    `mobile_phone`    varchar(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '手机',
    `email`           varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '用户邮箱',
    `avatar`          varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '用户头像',
    `signed`          varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '个人签名',
    `dashboard`       varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '后台首页类型',
    `dept_id`         bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '部门ID',
    `enabled`         smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `login_ip`        varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '最后登陆IP',
    `login_time`      int(11) NULL DEFAULT NULL COMMENT '最后登陆时间',
    `backend_setting` text NULL COMMENT '后台设置数据', -- 使用 TEXT 替代 JSON
    `created_by`      bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`      bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at`      bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`      bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at`      bigint(11) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT '删除时间',
    `sex`             tinyint(1) NULL DEFAULT 0 COMMENT '0=未知  1=男 2=女',
    `remark`          longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '备注',
    `birthday`        varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '生日',
    `tel`             varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '座机',
    `is_locked`       smallint(6) NULL DEFAULT 0 COMMENT '是否锁定（1是 0否）',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `username`(`user_name`) USING BTREE,
    INDEX             `dept_id`(`dept_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户信息表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin_casbin
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin_casbin`;
CREATE TABLE `ma_sys_admin_casbin`
(
    `admin_id`        bigint(20) UNSIGNED NOT NULL COMMENT '管理员主键',
    `admin_casbin_id` varchar(120) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '对应casbin策略表'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户与策略关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin_dept
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin_dept`;
CREATE TABLE `ma_sys_admin_dept`
(
    `admin_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户主键',
    `dept_id`  bigint(20) UNSIGNED NOT NULL COMMENT '角色主键',
    PRIMARY KEY (`admin_id`, `dept_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户与部门关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin_post
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin_post`;
CREATE TABLE `ma_sys_admin_post`
(
    `admin_id` bigint(20) UNSIGNED NOT NULL COMMENT '管理员主键',
    `post_id`  bigint(20) UNSIGNED NOT NULL COMMENT '岗位主键',
    PRIMARY KEY (`admin_id`, `post_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户与岗位关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin_role`;
CREATE TABLE `ma_sys_admin_role`
(
    `admin_id` bigint(20) UNSIGNED NOT NULL COMMENT '管理员主键',
    `role_id`  bigint(20) UNSIGNED NOT NULL COMMENT '角色主键'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '用户与角色关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_admin_tenant
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_admin_tenant`;
CREATE TABLE `ma_sys_admin_tenant`
(
    `id`         bigint(20) NOT NULL COMMENT '雪花ID',
    `admin_id`   bigint(20) NOT NULL COMMENT '管理员ID',
    `tenant_id`  bigint(20) NOT NULL COMMENT '租户ID',
    `is_super`   tinyint(4) NOT NULL DEFAULT 0 COMMENT '角色:1-管理员,0-普通用户',
    `is_default` tinyint(4) NULL DEFAULT 0 COMMENT '是否默认租户',
    `priority`   int(11) NULL DEFAULT 0 COMMENT '优先级(数值越小优先级越高)',
    `created_at` bigint(20) NULL DEFAULT NULL COMMENT '创建时间戳',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uk_admin_tenant`(`admin_id`, `tenant_id`) USING BTREE,
    INDEX        `idx_tenant_id`(`tenant_id`) USING BTREE,
    INDEX        `idx_admin_primary`(`admin_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '租户-管理员关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_casbin_rule
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_casbin_rule`;
CREATE TABLE `ma_sys_casbin_rule`
(
    `id`    bigint(20) UNSIGNED NOT NULL COMMENT 'ID',
    `ptype` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '策略类型',
    `v0`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主体(subject)',
    `v1`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '域(domain)',
    `v2`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '资源(resource)',
    `v3`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '动作(action)',
    `v4`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '扩展字段1',
    `v5`    varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '扩展字段2',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX   `idx_ptype`(`ptype`) USING BTREE,
    INDEX   `idx_v0`(`v0`) USING BTREE,
    INDEX   `idx_v1`(`v1`) USING BTREE,
    INDEX   `idx_v2`(`v2`) USING BTREE,
    INDEX   `idx_v3`(`v3`) USING BTREE,
    INDEX   `idx_v4`(`v4`) USING BTREE,
    INDEX   `idx_v5`(`v5`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Casbin策略规则表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_config
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_config`;
CREATE TABLE `ma_sys_config`
(
    `id`         bigint(20) NOT NULL COMMENT '配置ID',
    `tenant_id`  bigint(20) NULL DEFAULT NULL COMMENT '租户ID',
    `group_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '分组编码',
    `code`       varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '唯一编码',
    `name`       varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置名称',
    `content`    longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置内容',
    `is_sys`     tinyint(1) NULL DEFAULT 0 COMMENT '是否系统',
    `enabled`    tinyint(1) NULL DEFAULT 1 COMMENT '是否启用',
    `created_at` bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `created_by` bigint(20) NULL DEFAULT NULL COMMENT '创建用户',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `updated_by` bigint(20) NULL DEFAULT NULL COMMENT '更新用户',
    `deleted_at` bigint(20) NULL DEFAULT NULL COMMENT '是否删除',
    `remark`     longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX        `idx_config_code`(`code`) USING BTREE,
    INDEX        `idx_config_group_code`(`group_code`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_crontab
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_crontab`;
CREATE TABLE `ma_sys_crontab`
(
    `id`                bigint(20) UNSIGNED NOT NULL,
    `biz_id`            varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '业务id',
    `title`             varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '任务标题',
    `type`              tinyint(1) NOT NULL DEFAULT 1 COMMENT '任务类型1 url,2 eval,3 shell',
    `task_cycle`        tinyint(1) NOT NULL DEFAULT 1 COMMENT '任务周期',
    `cycle_rule`        json NULL DEFAULT NULL COMMENT '任务周期规则(JSON格式)',
    `rule`              longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '任务表达式',
    `target`            longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '调用任务字符串',
    `running_times`     int(11) NOT NULL DEFAULT 0 COMMENT '已运行次数',
    `last_running_time` int(11) NOT NULL DEFAULT 0 COMMENT '上次运行时间',
    `enabled`           tinyint(4) NOT NULL DEFAULT 0 COMMENT '任务状态状态0禁用,1启用',
    `created_at`        int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `created_by`        bigint(20) NULL DEFAULT NULL COMMENT '创建人',
    `deleted_at`        bigint(20) NOT NULL DEFAULT 0 COMMENT '软删除时间',
    `updated_at`        bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `updated_by`        bigint(20) NULL DEFAULT NULL COMMENT '更新人',
    `singleton`         tinyint(1) NULL DEFAULT 1 COMMENT '是否循环执行1 ',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX               `title`(`title`) USING BTREE,
    INDEX               `status`(`enabled`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时器任务表' ROW_FORMAT = DYNAMIC;
-- ----------------------------
-- Table structure for ma_sys_crontab_log
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_crontab_log`;
CREATE TABLE `ma_sys_crontab_log`
(
    `id`           bigint(20) UNSIGNED NOT NULL,
    `crontab_id`   bigint(20) UNSIGNED NOT NULL COMMENT '任务id',
    `target`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '任务调用目标字符串',
    `log`          longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '任务执行日志',
    `return_code`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '执行返回状态,1成功,0失败',
    `running_time` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '执行所用时间',
    `created_at`   bigint(20) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updated_at`   bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX          `create_time`(`created_at`) USING BTREE,
    INDEX          `crontab_id`(`crontab_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '定时器任务执行日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_dept
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_dept`;
CREATE TABLE `ma_sys_dept`
(
    `id`             bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `tenant_id`      bigint(20) NULL DEFAULT NULL COMMENT '租户隔离ID',
    `pid`            bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '父ID',
    `level`          varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '组级集合',
    `code`           varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '部门唯一编码',
    `name`           varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '部门名称',
    `main_leader_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '负责人',
    `phone`          varchar(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '联系电话',
    `enabled`        smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `sort`           smallint(5) UNSIGNED NULL DEFAULT 0 COMMENT '排序',
    `created_by`     bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`     bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at`     bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`     bigint(20) NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at`     bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    `remark`         longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX            `parent_id`(`pid`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '部门信息表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_dept_leader
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_dept_leader`;
CREATE TABLE `ma_sys_dept_leader`
(
    `dept_id`  bigint(20) UNSIGNED NOT NULL COMMENT '部门主键',
    `admin_id` bigint(20) UNSIGNED NOT NULL COMMENT '管理员主键'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '部门领导关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_dict
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_dict`;
CREATE TABLE `ma_sys_dict`
(
    `id`          bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `group_code`  varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典类型',
    `name`        varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典名称',
    `code`        varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典标示',
    `sort`        bigint(20) NULL DEFAULT 0 COMMENT '排序',
    `data_type`   smallint(6) NULL DEFAULT 1 COMMENT '数据类型',
    `description` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '描述',
    `enabled`     smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `created_by`  bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`  bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at`  bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '字典类型表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_dict_item
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_dict_item`;
CREATE TABLE `ma_sys_dict_item`
(
    `id`          bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `dict_id`     bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '字典类型ID',
    `label`       varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典标签',
    `value`       varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典值',
    `code`        varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '字典标示',
    `color`       varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'tag颜色',
    `other_class` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
    `sort`        smallint(5) UNSIGNED NULL DEFAULT 0 COMMENT '排序',
    `enabled`     smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `created_by`  bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`  bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at`  bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    `remark`      longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX         `dict_id`(`dict_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '字典数据表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_login_log
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_login_log`;
CREATE TABLE `ma_sys_login_log`
(
    `id`          bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `tenant_id`   bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `user_name`   varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '用户名',
    `ip`          varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '登录IP地址',
    `ip_location` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'IP所属地',
    `os`          varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '操作系统',
    `browser`     varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '浏览器',
    `status`      smallint(6) NULL DEFAULT 1 COMMENT '登录状态 (1成功 2失败)',
    `message`     longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '提示消息',
    `login_time`  bigint(20) NULL DEFAULT NULL COMMENT '登录时间',
    `key`         longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'key',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `expires_at`  bigint(20) NULL DEFAULT NULL COMMENT '过期时间',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `deleted_at`  bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    `remark`      varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX         `username`(`user_name`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '登录日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_menu
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_menu`;
CREATE TABLE `ma_sys_menu`
(
    `id`         bigint(20) NOT NULL COMMENT '菜单ID',
    `pid`        bigint(20) NOT NULL DEFAULT 0 COMMENT '父ID',
    `app`        varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '应用编码',
    `title`      varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '菜单名称',
    `code`       varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '唯一编码',
    `level`      varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '父ID集合',
    `type`       int(11) NULL DEFAULT NULL COMMENT '菜单类型1=>目录  2>菜单 3=>按钮 4=>接口 5=>内链 6=>外链',
    `sort`       bigint(20) NULL DEFAULT 999 COMMENT '排序',
    `path`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '路由地址',
    `component`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '组件地址',
    `redirect`   varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '重定向',
    `icon`       varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '菜单图标',
    `is_show`    tinyint(1) NULL DEFAULT 1 COMMENT '是否显示 0=>否   1=>是',
    `is_link`    tinyint(1) NULL DEFAULT 0 COMMENT '是否外链 0=>否   1=>是',
    `link_url`   longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '外部链接地址',
    `enabled`    tinyint(1) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `open_type`  int(11) NULL DEFAULT 0 COMMENT '是否外链 1=>是    0=>否',
    `is_cache`   tinyint(1) NULL DEFAULT 0 COMMENT '是否缓存 1=>是    0=>否',
    `is_sync`    tinyint(1) NULL DEFAULT 1 COMMENT '是否同步',
    `is_affix`   tinyint(1) NULL DEFAULT 0 COMMENT '是否固定tags无法关闭',
    `is_global`  tinyint(1) NULL DEFAULT 0 COMMENT '是否全局公共菜单 ',
    `variable`   varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '额外参数JSON',
    `created_at` bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `created_by` bigint(20) NULL DEFAULT NULL COMMENT '创建用户',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `updated_by` bigint(20) NULL DEFAULT NULL COMMENT '更新用户',
    `deleted_at` bigint(20) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT '是否删除',
    `methods`    varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'get' COMMENT '请求方法',
    `is_frame`   tinyint(1) NULL DEFAULT NULL COMMENT '是否外链',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX        `idx_sys_menu_code`(`code`) USING BTREE,
    INDEX        `idx_sys_menu_app_code`(`app`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '菜单' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_message
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_message`;
CREATE TABLE `ma_sys_message`
(
    `id`          bigint(20) NOT NULL COMMENT '主键',
    `type`        tinyint(4) NOT NULL COMMENT '消息类型参考枚举类',
    `title`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '消息标题（日志类消息可为空）',
    `content`     text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '消息内容（支持富文本存储）',
    `sender_id`   bigint(20) NULL DEFAULT 0 COMMENT '发送者ID（0表示系统发送）',
    `receiver_id` bigint(20) NOT NULL COMMENT '接收者ID（可关联用户表）',
    `status`      enum('unread','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unread' COMMENT '消息状态',
    `priority`    tinyint(1) NULL DEFAULT 3 COMMENT '优先级（1紧急 2急迫 3普通）',
    `channel`     varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'message' COMMENT '发送渠道',
    `related_id`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '关联业务ID（如订单号、日志ID等）',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `expired_at`  bigint(20) NULL DEFAULT NULL COMMENT '过期时间',
    `read_at`     bigint(20) NULL DEFAULT NULL COMMENT '已读时间',
    `updated_at`  bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统消息表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_notice
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_notice`;
CREATE TABLE `ma_sys_notice`
(
    `id`           bigint(20) NOT NULL COMMENT '公告ID',
    `tenant_id`    bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `type`         enum('announcement','notice') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'announcement' COMMENT '公告类型（notice=>通知 announcement=>公告）',
    `title`        varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '公告标题',
    `content`      longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '公告内容',
    `sort`         int(11) NULL DEFAULT 10 COMMENT '排序',
    `enabled`      tinyint(1) NULL DEFAULT 0 COMMENT '公告状态（0正常 1关闭）',
    `created_dept` bigint(20) NULL DEFAULT NULL COMMENT '创建部门',
    `created_by`   bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `created_at`   bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_by`   bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `updated_at`   bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `remark`       varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '通知公告表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_operate_log
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_operate_log`;
CREATE TABLE `ma_sys_operate_log`
(
    `id`          bigint(20) NOT NULL COMMENT '主键',
    `tenant_id`   bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `name`        varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '内容',
    `app`         varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '应用名称',
    `ip`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求ip',
    `ip_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求ip归属地',
    `browser`     varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '浏览器',
    `os`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作系统',
    `url`         varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求地址',
    `class_name`  varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '类名称',
    `action`      varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '方法名称',
    `method`      varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '请求方式（GET POST PUT DELETE)',
    `param`       longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求参数',
    `result`      longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '返回结果',
    `created_at`  bigint(20) NULL DEFAULT NULL COMMENT '操作时间',
    `updated_at`  bigint(20) NULL DEFAULT NULL,
    `user_name`   varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作账号',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_post
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_post`;
CREATE TABLE `ma_sys_post`
(
    `id`         bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `tenant_id`  bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `dept_id`    bigint(20) NULL DEFAULT NULL COMMENT '部门id',
    `code`       varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '岗位代码',
    `name`       varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci  NOT NULL COMMENT '岗位名称',
    `sort`       smallint(5) UNSIGNED NULL DEFAULT 0 COMMENT '排序',
    `enabled`    smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `created_by` bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by` bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at` bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at` bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    `remark`     varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '岗位信息表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_rate_limiter
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_rate_limiter`;
CREATE TABLE `ma_sys_rate_limiter`
(
    `id`          bigint(20) NOT NULL,
    `name`        varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则名称',
    `match_type`  enum('allow','exact') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'exact' COMMENT '匹配类型',
    `ip`          varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'ip地址',
    `priority`    int(11) NULL DEFAULT 100 COMMENT '优先级',
    `methods`     varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'GET' COMMENT '请求方法',
    `path`        varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '/' COMMENT '请求路径',
    `limit_type`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'count' COMMENT '限制类型',
    `limit_value` int(11) NULL DEFAULT 0 COMMENT '限制值',
    `period`      int(11) NULL DEFAULT 60 COMMENT '统计周期(秒)',
    `enabled`     tinyint(1) NULL DEFAULT 1 COMMENT '状态',
    `message`     longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '提示信息',
    `created_by`  bigint(20) NULL DEFAULT NULL COMMENT '创建人',
    `updated_by`  bigint(20) NULL DEFAULT NULL COMMENT '修改人',
    `created_at`  bigint(20) NULL DEFAULT NULL,
    `updated_at`  bigint(20) NULL DEFAULT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '限流规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_rate_restrictions
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_rate_restrictions`;
CREATE TABLE `ma_sys_rate_restrictions`
(
    `id`         bigint(20) UNSIGNED NOT NULL,
    `ip`         varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `name`       varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
    `enabled`    tinyint(1) NULL DEFAULT 1 COMMENT '规则状态(0-禁用,1-启用)',
    `priority`   int(10) UNSIGNED NULL DEFAULT 100 COMMENT '规则优先级(数字越小优先级越高)',
    `methods`    varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '0' COMMENT '限制值',
    `path`       varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '/' COMMENT '路径',
    `message`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '提示信息',
    `start_time` bigint(20) NULL DEFAULT NULL,
    `end_time`   bigint(20) NULL DEFAULT NULL,
    `created_at` bigint(20) NULL DEFAULT NULL,
    `updated_at` bigint(20) NULL DEFAULT NULL,
    `created_by` bigint(20) NULL DEFAULT NULL,
    `updated_by` bigint(20) NULL DEFAULT NULL,
    `remark`     longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '限制访问名单' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_recycle_bin
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_recycle_bin`;
CREATE TABLE `ma_sys_recycle_bin`
(
    `id`           bigint(20) UNSIGNED NOT NULL COMMENT 'ID',
    `tenant_id`    bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `original_id`  bigint(20) NULL DEFAULT NULL COMMENT '原始数据ID',
    `data`         json NULL COMMENT '回收的数据',
    `table_name`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '数据表',
    `table_prefix` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '表前缀',
    `enabled`      tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已还原',
    `ip`           varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '操作者IP',
    `operate_by`   bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员',
    `created_at`   bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`   bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '数据回收记录表' ROW_FORMAT = DYNAMIC;



-- ----------------------------
-- Table structure for ma_sys_role
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_role`;
CREATE TABLE `ma_sys_role`
(
    `id`             bigint(20) UNSIGNED NOT NULL COMMENT '主键',
    `tenant_id`      bigint(20) NULL DEFAULT NULL COMMENT '租户id 默认null',
    `pid`            bigint(20) NULL DEFAULT 0 COMMENT '父级id',
    `name`           varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '角色名称',
    `code`           varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '角色代码',
    `is_super_admin` tinyint(1) NULL DEFAULT 0 COMMENT '是否超级管理员 1=是   0=否',
    `role_type`      tinyint(4) NULL DEFAULT NULL COMMENT '角色类型',
    `data_scope`     smallint(6) NULL DEFAULT 1 COMMENT '数据范围(1:全部数据权限 2:自定义数据权限 3:本部门数据权限 4:本部门及以下数据权限 5:本人数据权限)',
    `enabled`        smallint(6) NULL DEFAULT 1 COMMENT '状态 (1正常 0停用)',
    `sort`           smallint(5) UNSIGNED NULL DEFAULT 0 COMMENT '排序',
    `remark`         varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '备注',
    `created_by`     bigint(20) NULL DEFAULT NULL COMMENT '创建者',
    `updated_by`     bigint(20) NULL DEFAULT NULL COMMENT '更新者',
    `created_at`     bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`     bigint(20) NULL DEFAULT NULL COMMENT '修改时间',
    `deleted_at`     bigint(20) NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '角色信息表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_role_casbin
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_role_casbin`;
CREATE TABLE `ma_sys_role_casbin`
(
    `role_id`        bigint(20) UNSIGNED NOT NULL COMMENT '管理员主键',
    `role_casbin_id` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '对应casbin策略表'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '角色与策略关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_role_dept
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_role_dept`;
CREATE TABLE `ma_sys_role_dept`
(
    `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户主键',
    `dept_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色主键',
    PRIMARY KEY (`role_id`, `dept_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '角色与部门关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_role_menu`;
CREATE TABLE `ma_sys_role_menu`
(
    `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色主键',
    `menu_id` bigint(20) UNSIGNED NOT NULL COMMENT '菜单主键'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '角色与菜单关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_role_scope_dept
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_role_scope_dept`;
CREATE TABLE `ma_sys_role_scope_dept`
(
    `role_id` bigint(20) UNSIGNED NOT NULL COMMENT '用户主键',
    `dept_id` bigint(20) UNSIGNED NOT NULL COMMENT '角色主键'
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci COMMENT = '角色与部门关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_route
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_route`;
CREATE TABLE `ma_sys_route`
(
    `id`               bigint(20) NOT NULL,
    `cate_id`          bigint(20) NOT NULL DEFAULT 0 COMMENT '分组id',
    `app_name`         varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'api' COMMENT '应用名',
    `name`             varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '路由名称',
    `describe`         text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '功能描述',
    `path`             varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '路由路径',
    `method`           enum('POST','GET','DELETE','PUT','*') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'GET' COMMENT '路由请求方式',
    `file_path`        varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '文件路径',
    `action`           varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '方法名称',
    `query`            longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'get请求参数',
    `header`           longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'header',
    `request`          longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '请求数据',
    `request_type`     varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '请求类型',
    `response`         longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '返回数据',
    `request_example`  longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '请求示例',
    `response_example` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '返回示例',
    `created_at`       bigint(20) NULL DEFAULT NULL COMMENT '添加时间',
    `updated_at`       bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX              `path`(`path`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_general_ci COMMENT = '路由规则表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_route_cate
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_route_cate`;
CREATE TABLE `ma_sys_route_cate`
(
    `id`         bigint(20) NOT NULL,
    `pid`        bigint(20) NOT NULL DEFAULT 0 COMMENT '上级id',
    `app_name`   varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '应用名',
    `name`       varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
    `sort`       int(11) NULL DEFAULT 0,
    `enabled`    tinyint(1) NULL DEFAULT 1,
    `created_at` bigint(20) NULL DEFAULT 0 COMMENT '添加时间',
    `updated_at` bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX        `app_name`(`app_name`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_general_ci COMMENT = '路由规则分组表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ma_sys_upload
-- ----------------------------
DROP TABLE IF EXISTS `ma_sys_upload`;
CREATE TABLE `ma_sys_upload`
(
    `id`                bigint(20) NOT NULL COMMENT '文件信息ID',
    `tenant_id`         bigint(20) NULL DEFAULT NULL COMMENT '租户id',
    `url`               longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件访问地址',
    `size`              bigint(20) NULL DEFAULT NULL COMMENT '文件大小，单位字节',
    `size_info`         varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件大小，有单位',
    `hash`              varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件hash',
    `filename`          varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件名称',
    `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原始文件名',
    `base_path`         longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '基础存储路径',
    `path`              longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '存储路径',
    `ext`               varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件扩展名',
    `content_type`      varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'MIME类型',
    `platform`          varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '存储平台',
    `th_url`            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '缩略图访问路径',
    `th_filename`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '缩略图大小，单位字节',
    `th_size`           bigint(20) NULL DEFAULT NULL COMMENT '缩略图大小，单位字节',
    `th_size_info`      varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '缩略图大小，有单位',
    `th_content_type`   varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '缩略图MIME类型',
    `object_id`         varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件所属对象id',
    `object_type`       varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件所属对象类型，例如用户头像，评价图片',
    `attr`              text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '附加属性',
    `created_at`        bigint(20) NULL DEFAULT NULL COMMENT '创建时间',
    `updated_at`        bigint(20) NULL DEFAULT NULL COMMENT '更新时间',
    `created_by`        bigint(20) NULL DEFAULT NULL COMMENT '创建用户',
    `updated_by`        bigint(20) NULL DEFAULT NULL COMMENT '更新用户',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件信息' ROW_FORMAT = DYNAMIC;

SET
FOREIGN_KEY_CHECKS = 1;
