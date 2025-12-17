package src.exo2;


abstract class Commande {

    protected int price;

    final void steps(){

        verifyCommande();
        calculatePrice();
        pay();
    }

    void verifyCommande(){
        System.out.println("commande en cours de verification");
    }

    abstract int calculatePrice();

    abstract void pay();

    
}
