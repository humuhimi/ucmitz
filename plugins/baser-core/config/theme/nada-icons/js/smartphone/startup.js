/**
 * 共通スタートアップ処理
 */
$(function(){
	$('#ToTop a').click(function(){
            $(this).blur();
            $('html,body').animate({ scrollTop: 0 }, 'fast');
            return false;
	});
});
