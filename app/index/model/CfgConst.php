<?php
namespace app\index\model;

class CfgConst
{

	// +----------------------------------------------------------------------
    // | 系统配置
    // +----------------------------------------------------------------------
	const SystemServerPassword                = '1234567';                 // 服务器访问密码
	


	// +----------------------------------------------------------------------
    // | 邮件配置
    // +----------------------------------------------------------------------
	const MailTypes                           = ['普通邮件'];               // 邮件类型
	const MailTargetTypes                     = [                   
		1 => '达成指定等级',
		2 => '指定登陆天数',
		3 => '指定登陆时间',
		4 => '指定注册时间',
	];																       // 邮件目标类型



	// +----------------------------------------------------------------------
    // | 接口配置
    // +----------------------------------------------------------------------
	const ApiPay							  = 'Pay/payBackstage';        // 支付接口



	// +----------------------------------------------------------------------
    // | 活动类型
    // +----------------------------------------------------------------------
	const AtypeFirstRecharge			      = 1;                         // 首冲奖励
	const AtypeRegisterSign			          = 2;                         // 创角签到
	const AtypeMonthlySign			          = 3;                         // 每月签到
	const AtypeRecruitQuest	                  = 8;                         // 新兵任务
	const AtypeTotalRecharge	              = 9;                         // 累计充值
	const AtypeRotateImage	                  = 10;                        // 轮播图



	// +----------------------------------------------------------------------
    // | 任务类型
    // +----------------------------------------------------------------------
    const QtypeMain                       = 100;              // 主线（暂时没用）
    const QtypeDaily                      = 1;                // 日常
    const QtypeWeekly                     = 2;                // 周常
    const QtypeStory                      = 3;                // 剧情
    const QtypeAchieve                    = 4;                // 成就
    const QtypeDailyActive                = 5;                // 每日活跃
    const QtypeWeeklyActive               = 6;                // 每周活跃
    const QtypeDailyGuild                 = 7;                // 每日公会
    const QtypeRecruitQuest               = 8;                // 新兵考核
    const QtypeTotalRecharge              = 9;                // 累计充值
    const QtypeRecruitQuestNumber         = 11;               // 新兵活动进度


	
}