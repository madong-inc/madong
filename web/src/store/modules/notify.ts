import type { NotificationItem } from '#/components/common/effects/layouts';

import { computed, h, ref, RendererElement, RendererNode, VNode, VNodeArrayChildren } from 'vue';

import { useUserStore } from '#/components/common/stores';

import { notification } from 'ant-design-vue';
import { defineStore } from 'pinia';
//@ts-ignore
import { Push } from '#/utils/push-vue.js';
import { SystemMessageApi } from '#/api/system/message';


const api = new SystemMessageApi();


export const useNotifyStore = defineStore(
  'app-notify',
  () => {
    /**
     * return才会被持久化 存储全部消息
     */
    const notificationList = ref<NotificationItem[]>([]);

    const userStore = useUserStore();
    const userId = computed(() => {
      return userStore.userInfo?.id || '0';
    });

    const tenantId = computed(() => {
      //@ts-ignore
      return userStore.userInfo?.tenant?.tenant_id || '*';
    });

    const notifications = computed(() => {
      const currentUserId = String(userId.value);
      return notificationList.value.filter(item => String(item.uid) === currentUserId);
    });


    /**
     * 是否已读
     * @param action 
     * @returns 
     */
    const isReadActionCaseInsensitive = function (action: any): boolean {
      return typeof action === 'string' && action.toLowerCase() === 'read';
    };

    /**
     * 将新消息添加到持久化
     * @param data
     * @param uid
     */
    const addUniqueNotification = function (data: any) {
      const currentUserId = String(userId.value);
      const exists = notificationList.value.some((notification) =>
        String(notification.id) === String(data.id) &&
        String(notification.uid) === currentUserId &&
        String(notification.channel === String(data.channel))
      );
      if (!exists) {
        const formattedContent = computed(() => {
          const text = data.content
            .replace(/\\n/g, '\n') // 还原 \\n → \n
            .replace(/\n\n/g, '\n\n') // 保留双换行标记
            .replace(/\n/g, '\n');     // 保留单换行标记

          // 按 \n\n 分割段落
          const paragraphs = text.split('\n\n');
          return h('div',
            paragraphs.map((para: string) =>
              h('p',
                para.split('\n').map((line: string | number | boolean | VNode<RendererNode, RendererElement, { [key: string]: any; }> | VNodeArrayChildren | (() => any) | { [name: string]: unknown; $stable?: boolean; } | undefined) => h('span', { style: { display: 'block' } }, line))
              )
            )
          );
        });

        notification.success({
          message: data.title || '新消息提醒',
          duration: 5,
          description: formattedContent.value,
        });
        notificationList.value.unshift({
          id: String(data.id),
          avatar: data?.sender?.avatar || '',
          date: data.created_date,
          isRead: isReadActionCaseInsensitive(data.status),
          message: data.content || '',
          title: data.title,
          related_id: data.related_id || '',
          uid: data.receiver_id,
          type: data?.type || '',
          channel: data.channel || 'notice'
        });
      }
    }

    /**
     * 开始监听消息
     */
    async function startListeningMessage() {
      /**
       * 未开启 不监听
       */
      const wssUrl = import.meta.env.VITE_GLOB_WSS_URL;
      const appKey = import.meta.env.VITE_GLOB_WSS_APPKEY;
      const sseEnable = import.meta.env.VITE_GLOB_ENABLE_WSS;
      if (!sseEnable) {
        return;
      }

      const connection = new Push({
        url: wssUrl, // websocket地址
        app_key: appKey,
        auth: '/plugin/webman/push/auth' // 订阅鉴权(仅限于私有频道)
      });

      /**
       * 
       * 订阅后端消息通道（格式：backend-{模块}-{租户ID}-{用户ID}）
       * 
       * 通道命名规则：
       * - backend      : 固定前缀，标识后端服务通道（与前端其他通道区分）
       * - admin         : 业务模块标识（此处为管理员模块，可替换为其他模块）
       * - tenantId.value   : 当前租户的唯一ID
       * - userId.value     : 当前用户的唯一ID
       * 示例最终通道名：backend-admin-1-1
       */
      const message_channel = connection.subscribe('backend-' + 'admin-' + tenantId.value + '-' + userId.value);
      message_channel.on('message', function (data: any) {
        const message = data.messages || {}
        if (Array.isArray(message)) {
          message.forEach((data: any) => {
            //添加到列表
            addUniqueNotification(data);
          });
        } else {
          addUniqueNotification(message);
        }
      });
    }

    //


    /**
     * 设置全部已读
     */
    async function setAllRead() {
      const currentUserId = String(userId.value);
      // 公共过滤函数
      const filterUserNotifications = (channel: string) =>
        notificationList.value.filter(
          item => String(item.uid) === currentUserId && item.channel === channel
        );
      try {
        // 处理消息（需要后端同步）
        const messages = filterUserNotifications('message');
        const unreadMessages = messages.filter(item => !item.isRead);

        if (unreadMessages.length > 0) {
          const unreadIds = unreadMessages.map(item => item.id) as Array<String>;
          await api.updateRead(unreadIds); // 等待消息标记完成
          unreadMessages.forEach(item => item.isRead = true);
        }

        // 处理公告通知（无需后端同步）
        const notices = filterUserNotifications('notice');
        notices.forEach(item => item.isRead = true); // 直接标记本地状态

      } catch (error) {
        console.error('标记已读失败:', error);
      }
    }

    /**
     * 设置单条消息已读
     * @param item 通知
     */
    async function setRead(item: NotificationItem) {
      // 提前返回已读项，避免重复处理
      if (item.isRead) return;

      try {
        // 公告通知本地标记
        if (item.channel === 'notice') {
          item.isRead = true;
          return;
        }

        // 消息类型同步标记后端
        await api.updateRead(String(item.id));
        item.isRead = true;
      } catch (error) {
        console.error(`标记消息[${item.id}]为已读失败:`, error);
      }
    }

    /**
     * 清空全部消息
     */
    function clearAllMessage() {
      const currentUserId = String(userId.value);
      notificationList.value = notificationList.value.filter(
        (item) => String(item.uid) !== currentUserId,
      );
    }

    /**
     * 只需要空实现即可
     * 否则会在退出登录清空所有
     */
    function $reset() {
      notificationList.value = [];
    }
    /**
     * 显示小圆点
     */
    const showDot = computed(() =>
      notificationList.value
        .filter((item) => item.uid === userId.value)
        .some((item) => !item.isRead),
    );

    return {
      $reset,
      clearAllMessage,
      notificationList,
      notifications,
      setAllRead,
      setRead,
      showDot,
      startListeningMessage,
    };
  },
  {
    persist: {
      pick: ['notificationList'],
    },
  },
);
