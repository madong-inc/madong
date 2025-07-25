
export const  enum taskStatus {
    /**
     * 任务已创建，等待执行
     * - 处于任务队列中但尚未启动
     * - 初始默认状态
     */
    Waiting,
    /** 
    * 任务正在建立连接 
    * - 与服务端建立通信链路（如SSE连接）
    * - 准备接收执行指令的前置状态 
    */
    Connecting,

    /** 
     * 任务正在执行中 
     * - 命令已开始运行 
     * - 实时接收并显示执行日志
     * - 核心运行状态
     */
    Executing,

    /** 
     * 任务正在执行中 
     * - 命令已开始运行 
     * - 实时接收并显示执行日志
     * - 核心运行状态
     */
    Success,

    /** 
    * 任务执行失败 
    * - 命令返回错误或异常中断 
    * - 若配置blockOnFailure将阻塞后续任务
    * - 触发回调函数传递失败状态 
    */
    Failed,

    /** 
     * 任务状态未知 
     * - 连接意外中断或状态丢失
     * - 应用启动时对中断任务的恢复处理 
     * - 按失败状态处理并阻塞后续任务
     */
    Unknown
}
