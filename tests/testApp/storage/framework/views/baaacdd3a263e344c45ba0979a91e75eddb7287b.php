<?php echo Form::open(['url' => Request::url()]); ?>


        <!-- username field -->
<div class="form-group <?php echo FormMessage::getErrorClass('username'); ?>">
    <?php echo Form::label('username', 'Username', ['class' => 'control-label']); ?>

    <?php echo Form::text('username', Request::input('username'), ['class' => 'form-control']); ?>

    <span class="help-block"><?php echo FormMessage::getErrorMessage('username'); ?></span>
</div>

<!-- password field -->
<div class="form-group <?php echo FormMessage::getErrorClass('password'); ?>">
    <?php echo Form::label('password', 'Password', ['class' => 'control-label']); ?>

    <?php echo Form::password('password', ['class' => 'form-control']); ?>

</div>

<!-- remember field -->
<div class="form-group">
    <div class="checkbox">
        <label>
            <?php echo Form::checkbox('remember', 'yes', false); ?>

            Remember Me
        </label>
    </div>
</div>

<?php echo Form::hidden('login_path', Request::input('login_path')); ?>


        <!-- submit button -->
<p><?php echo Form::submit('Login', ['class' => 'btn btn-primary']); ?></p>

<?php echo Form::close(); ?>


<div class="row">
    <div class="col-sm-12 forgot-pw">
        <a href="<?php echo route('coaster.admin.login.password.forgotten'); ?>">Forgotten password?</a>
    </div>
</div>



