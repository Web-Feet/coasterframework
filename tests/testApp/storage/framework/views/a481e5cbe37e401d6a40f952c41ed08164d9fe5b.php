<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8"/>
    <title><?php echo $site_name." | ".$title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Coaster CMS <?php echo e(config('coaster::site.version')); ?>">
    <meta name="_token" content="<?php echo e(csrf_token()); ?>">

    <link href='//fonts.googleapis.com/css?family=Raleway:400,100,300,500,600,700,800,900' rel='stylesheet' type='text/css'>
    <?php echo AssetBuilder::styles(); ?>

    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    <?php echo $__env->yieldContent('styles'); ?>

</head>

<body>

<nav class="navbar navbar-default navbar-fixhed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                    aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="logo" href="<?php echo e(route('coaster.admin')); ?>">
                <img src="<?php echo e(URL::to(config('coaster::admin.public'))); ?>/app/img/logo.png" alt="Coaster CMS"/>
            </a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php if(isset($system_menu)): ?>
                    <?php echo $system_menu; ?>

                <?php endif; ?>
            </ul>
        </div><!--/.nav-collapse -->
    </div><!--/.container-fluid -->
</nav>

<?php if(!empty($sections_menu)): ?>
    <nav class="navbar navbar-inverse subnav navbar-fixedg-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar2"
                        aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div id="navbar2" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <?php echo $sections_menu; ?>

                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </nav>
<?php endif; ?>

<div class="container<?php echo e(empty($sections_menu)?' loginpanel':''); ?>" id="content-wrap">
    <div class="row">
        <div class="<?php echo e(empty($sections_menu)?'col-sm-6 col-sm-offset-3':'col-sm-12'); ?>">
            <div id="cmsNotifications">
                <div class="alert" id="cmsDefaultNotification" style="display: none;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            </div>
            <?php echo $content; ?>

            <br /><br />
        </div>
    </div>
</div>

<?php echo $modals; ?>


<script src="<?php echo e(URL::to(config('coaster::admin.public')).'/app/js/router.js'); ?>"></script>
<script type="text/javascript">
    var dateFormat = '<?php echo e(config('coaster::date.format.jq_date')); ?>';
    var timeFormat = '<?php echo e(config('coaster::date.format.jq_time')); ?>';
    var ytBrowserKey = '<?php echo e(config('coaster::key.yt_browser')); ?>';
    var adminPublicUrl = '<?php echo e(URL::to(config('coaster::admin.public')).'/'); ?>';
    router.addRoutes(<?php echo $coaster_routes; ?>);
    router.setBase('<?php echo e(URL::to('/')); ?>');
</script>

<?php echo AssetBuilder::scripts(); ?>

<?php echo $__env->yieldContent('scripts'); ?>
<?php if(!empty($alerts)): ?>
    <script type="text/javascript">
        $(document).ready(function () {
            <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                cms_alert('<?php echo $alert->class; ?>', '<?php echo $alert->content; ?>');
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        });
    </script>
<?php endif; ?>

</body>

</html>
