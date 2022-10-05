# GitPod Setup

Gitpod will download and install all dependencies for both the backend and the frontend. it willGitpod will download and install all dependencies for both the backend and the frontend. It will then connect the two services and open up two separate tabs, one for the frontend, one for the backend. The frontend will be cloned into `frontend` directory and will spin up a hot-reload tab, meaning any change to the component will result in a tab reload.

1. Add your ssh key to [GitPod](https://gitpod.io/variables)
   1. It is recommended to have a password-less ssh key for simplicity.
      1. `ssh-keygen -b 4096`, press enter when asked for the password
      2. Add this ssh public key to the necessary services: Acquia, Github, etc.
   2. Get the base64 string of your ssh key files
      1. `cat id_rsa | base64` for the private key
      2. `cat id_rsa.pub | base64` for the public key.
   3. In GitPod, add the variable named `SSH_PRIVATE_KEY` with the private key
   4. In GitPod, add the variable named `SSH_PUBLIC_KEY` with the public key
   5. In Gitpod, add the variable named `GITCONFIG` with the base64 of your git config: `cat ~/.gitconfig | base64`
2. Recommended, but not required:
   1. install the GitPod browser plugin
   2. Configure your browser settings for an easier experience: https://www.gitpod.io/docs/configure/browser-settings
3. Open a gitpod workspace with [these instructions](https://www.gitpod.io/docs/getting-started#start-your-first-workspace)
