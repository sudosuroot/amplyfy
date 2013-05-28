amplyfy
=======

Social TV backend framework (PHP) 
Can be used for all social interactions in an app. Includes leaderboard code, friend management, recommendation engine, user management etc.

Contents:

1) ws - Web services (Tonic) for various social interations
Eg : GetFriends, InsertComment, AddPoints, GetLeaderboard etc. 

2) libs - Underlying library code for all the interactions. Uses mongoDB and gearman. 

3) gearman - Gearman job scheduling code for async tasks. 

Requires : Mongo, PHP Mongo, PHP gearman, Tonic PHP ws library. 
