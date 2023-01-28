<?php 
namespace App;

enum AdviceType: int
{
	case Home = 0;
	case Virtual = 1;
	case DirectOrder = 2;
}