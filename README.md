Bitbucket Deployer
==================

Simple tool for deployment and update repositories on remote servers using Bitbucket post service.

## How it works?

You know that days that you have a lot of things to do and you think that time isn't enought? So, you need to plain, develop, maintain, upgrade and upload a lot of files while you embraces the world. How to optimize this workflow?

At first you should put the bitbucket-refresh.php into the path you need to get the repositories, after you need to configure the Bitbucket service to post when you perform actions.

You can set it accessing `https://bitbucket.org/{user_name}/{repository_name}/admin/services`.

Make sure that your server will execute evil `exec('rm -Rf /')` functions.. Without this we cannot execute the git client (that must be installed on your server).
