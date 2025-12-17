package src.exo2;

public class Numerique extends  Commande{



    public Numerique() {
        super();
    }


   @Override
   int calculatePrice() {
    return 8;
   }


   @Override
   void pay() {
    System.out.println("le client a payee : "+this.price);
   }
    
}
