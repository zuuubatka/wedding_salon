jQuery(document).ready(function($){
    $('#send-email-btn').click(function(){
        var email = $('#email-input').val();
        if(!email){ $('#auth-message').css('color','red').text('Введите email'); return; }

        $.post('<?php echo get_template_directory_uri(); ?>/send_email.php', { email: email, action:'send_code' }, function(res){
            if(res.success){
                $('#auth-message').css('color','green').text(res.message);
                $('#code-input, #verify-code-btn').show();
            } else {
                $('#auth-message').css('color','red').text(res.message);
            }
        }, 'json');
    });

    $('#verify-code-btn').click(function(){
        var code = $('#code-input').val();
        if(!code){ $('#auth-message').css('color','red').text('Введите код'); return; }

        $.post('<?php echo get_template_directory_uri(); ?>/send_email.php', { code: code, action:'check_code' }, function(res){
            if(res.success){
                $('#auth-message').css('color','green').text(res.message);
            } else {
                $('#auth-message').css('color','red').text(res.message);
            }
        }, 'json');
    });

    $('.close-modal').click(function(){ $('#auth-modal').hide(); });
});
